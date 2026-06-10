<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generar un reporte PDF del historial de peso de un animal específico.
     */
    public function generateAnimalReport(Request $request, $animalId)
    {
        $animal = Animal::with('farm', 'weightRecords')->findOrFail($animalId);

        if ($animal->farm->user_id !== $request->user()->id && !$request->user()->hasSharedAccess($animal->farm_id)) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        $html = '<h1>Reporte de Pesaje - BovWeight CR</h1>';
        $html .= '<p><strong>Animal:</strong> ' . $animal->nombre . ' (Arete: ' . $animal->arete . ')</p>';
        $html .= '<p><strong>Raza:</strong> ' . $animal->raza . '</p>';
        $html .= '<p><strong>Finca:</strong> ' . $animal->farm->nombre . '</p>';
        $html .= '<p><strong>Fecha de Generación:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>';

        $html .= '<h2>Historial de Pesajes</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%">';
        $html .= '<tr><th>Fecha</th><th>Peso Estimado (kg)</th><th>Estado</th></tr>';

        foreach ($animal->weightRecords as $record) {
            $html .= '<tr>';
            $html .= '<td>' . $record->fecha_pesaje->format('d/m/Y') . '</td>';
            $html .= '<td>' . $record->peso_estimado . ' kg</td>';
            $html .= '<td>' . $record->estado_sincronizacion . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        $pdf = Pdf::loadHTML($html);
        return $pdf->download('reporte_animal_' . $animal->arete . '.pdf');
    }

    /**
     * Generar un reporte PDF unificado según el tipo solicitado.
     * Tipos soportados: general, pesajes, finca.
     */
    public function generateReport(Request $request)
    {
        $user       = $request->user();
        $type       = $request->query('tipo', 'general');
        $fincaId    = $request->query('finca_id');

        // ── Período mensual ──────────────────────────────────────────────────
        $mesActual   = (int) now()->month;
        $anioActual  = (int) now()->year;
        $nombreMes   = now()->locale('es')->isoFormat('MMMM');
        $periodoTexto = ucfirst($nombreMes) . ' ' . $anioActual;

        // ── Usuario generador (seguro) ────────────────────────────────────────
        $nombreUsuario = $user ? ($user->name ?? 'No disponible') : 'No disponible';

        // ── Rol / Invitado ────────────────────────────────────────────────────
        $isGuest = $user && $user->invited_farm_id
            && (!$user->guest_expires_at || now()->lt($user->guest_expires_at));

        if ($isGuest) {
            $fincaId = $user->invited_farm_id;
        }

        // ── Nombre de finca ───────────────────────────────────────────────────
        $fincaNombre = 'Todas las fincas';
        if ($fincaId) {
            $finca = $isGuest
                ? \App\Models\Farm::findOrFail($fincaId)
                : \App\Models\Farm::where('id', $fincaId)->where('user_id', $user->id)->firstOrFail();
            $fincaNombre = $finca->nombre;
        }

        // ── Consulta base de animales ─────────────────────────────────────────
        $queryAnimales = Animal::query();

        if ($isGuest) {
            $queryAnimales->where('farm_id', $user->invited_farm_id);
        } else {
            $queryAnimales->whereHas('farm', fn($q) => $q->where('user_id', $user->id));
        }

        if ($fincaId && !$isGuest) {
            $queryAnimales->where('farm_id', $fincaId);
        }

        $animales = $queryAnimales->with('farm', 'weightRecords')->get();

        // ── CSS unificado (compatible DomPDF) ─────────────────────────────────
        $css = '
        <style>
            * { box-sizing: border-box; }
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                color: #2B2D2F; line-height: 1.5; padding: 24px; font-size: 13px;
            }

            /* ── Encabezado ── */
            .encabezado {
                border-bottom: 3px solid #656D4A;
                padding-bottom: 14px; margin-bottom: 6px;
            }
            .encabezado-titulo {
                color: #414833; font-size: 24px; font-weight: bold; margin: 0 0 2px 0;
            }
            .encabezado-subtitulo {
                color: #8B8E83; font-size: 13px; margin: 0;
            }
            .encabezado-periodo {
                color: #656D4A; font-size: 12px; font-weight: bold;
                margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px;
            }

            /* ── Aviso técnico ── */
            .aviso-tecnico {
                border: 2px solid #C8A951;
                background-color: #FDFBF3;
                border-radius: 6px;
                padding: 12px 16px;
                margin: 16px 0;
            }
            .aviso-tecnico-titulo {
                color: #8A6D1A; font-size: 11px; font-weight: bold;
                text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
            }
            .aviso-tecnico-texto {
                color: #5A4A1A; font-size: 11.5px; line-height: 1.5; margin: 0;
            }

            /* ── Sección de metadata ── */
            .meta-section {
                background-color: #F4F6F0; border-radius: 6px;
                padding: 14px 16px; margin-bottom: 20px;
            }
            .meta-grid { width: 100%; border-collapse: collapse; }
            .meta-grid td { padding: 5px 10px; font-size: 12px; vertical-align: top; }
            .meta-label { font-weight: bold; color: #414833; width: 30%; }

            /* ── Bloque de métricas ── */
            .metricas-titulo {
                color: #414833; font-size: 15px; font-weight: bold;
                margin: 20px 0 10px 0; border-left: 4px solid #656D4A; padding-left: 10px;
            }
            .metricas-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .metrica-celda {
                width: 50%; padding: 10px 12px;
                background-color: #F9FAF7;
                border: 1px solid #E3E5D7;
                vertical-align: top;
            }
            .metrica-etiqueta {
                font-size: 10px; color: #8B8E83;
                text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;
            }
            .metrica-valor {
                font-size: 18px; font-weight: bold; color: #414833;
            }
            .metrica-valor-sm {
                font-size: 14px; font-weight: bold; color: #414833;
            }

            /* ── Mensaje sin datos ── */
            .sin-datos {
                text-align: center; padding: 30px 20px;
                background-color: #F9FAF7; border: 1px dashed #C2C5AA;
                border-radius: 6px; margin: 12px 0; color: #8B8E83;
                font-size: 13px;
            }
            .sin-datos-icono { font-size: 28px; margin-bottom: 8px; }

            /* ── Tabla de datos ── */
            .tabla-titulo {
                color: #414833; font-size: 15px; font-weight: bold;
                margin: 20px 0 8px 0; border-left: 4px solid #656D4A; padding-left: 10px;
            }
            .data-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            .data-table th {
                background-color: #656D4A; color: white;
                padding: 10px 8px; font-size: 11px;
                text-transform: uppercase; letter-spacing: 0.5px; text-align: left;
            }
            .data-table td {
                padding: 10px 8px; border-bottom: 1px solid #E3E5D7; font-size: 12px;
            }
            .data-table tr:nth-child(even) td { background-color: #F9FAF7; }
            .badge {
                background-color: #C2C5AA; color: #2B2D2F;
                padding: 2px 7px; border-radius: 3px;
                font-size: 10px; font-weight: bold;
            }
            .badge-ia { background-color: #D0E8D0; color: #2E5C2E; }
            .badge-pendiente { background-color: #F0EDD8; color: #6B5A1A; }

            /* ── Pie de página ── */
            .footer {
                text-align: center; font-size: 10px; color: #8B8E83;
                margin-top: 40px; border-top: 1px solid #E3E5D7;
                padding-top: 12px;
            }
        </style>';

        $html = '<html><head>' . $css . '</head><body>';

        // ── Aviso técnico (bloque reutilizable) ──────────────────────────────
        $avisoTecnico = '
        <div class="aviso-tecnico">
            <p class="aviso-tecnico-titulo"> Advertencia Técnica — Estimaciones de IA</p>
            <p class="aviso-tecnico-texto">
                Las estimaciones presentadas en este reporte son generadas mediante BovWeight CR
                como apoyo para la gestión ganadera. <strong>No sustituyen una báscula oficial certificada</strong>
                ni deben utilizarse como único respaldo para transacciones comerciales o legales.
            </p>
        </div>';

        // ════════════════════════════════════════════════════════════════
        // REPORTE DE PESAJES
        // ════════════════════════════════════════════════════════════════
        if ($type === 'pesajes') {

            $queryPesajes = \App\Models\WeightRecord::query();

            if ($isGuest) {
                $queryPesajes->whereHas('animal', fn($q) => $q->where('farm_id', $user->invited_farm_id));
            } else {
                $queryPesajes->whereHas('animal.farm', fn($q) => $q->where('user_id', $user->id));
            }

            if ($fincaId && !$isGuest) {
                $queryPesajes->whereHas('animal', fn($q) => $q->where('farm_id', $fincaId));
            }

            $pesajes = $queryPesajes->with(['animal.farm'])->orderBy('fecha_pesaje', 'desc')->get();

            // ── Métricas del mes actual ────────────────────────────────────
            $pesajesMes = $pesajes->filter(function ($p) use ($mesActual, $anioActual) {
                $fecha = ($p->fecha_pesaje instanceof Carbon)
                    ? $p->fecha_pesaje
                    : Carbon::parse($p->fecha_pesaje);
                return $fecha->month === $mesActual && $fecha->year === $anioActual;
            });

            $totalMes       = $pesajesMes->count();
            $promMes        = $totalMes > 0 ? round($pesajesMes->avg('peso_estimado'), 1) : null;
            $minMes         = $totalMes > 0 ? round($pesajesMes->min('peso_estimado'), 1) : null;
            $maxMes         = $totalMes > 0 ? round($pesajesMes->max('peso_estimado'), 1) : null;
            $pesajesIA      = $pesajesMes->filter(fn($p) => str_contains($p->notas ?? '', 'YOLOv8'))->count();
            $pesajesManuales = $totalMes - $pesajesIA;

            $ultimoPesajeMes = $pesajesMes->sortByDesc(fn($p) =>
                ($p->fecha_pesaje instanceof Carbon)
                    ? $p->fecha_pesaje
                    : Carbon::parse($p->fecha_pesaje)
            )->first();

            $ultimaFechaMes = $ultimoPesajeMes
                ? (($ultimoPesajeMes->fecha_pesaje instanceof Carbon)
                    ? $ultimoPesajeMes->fecha_pesaje->format('d/m/Y')
                    : Carbon::parse($ultimoPesajeMes->fecha_pesaje)->format('d/m/Y'))
                : null;

            // ── HTML: Encabezado ──────────────────────────────────────────
            $html .= '
            <div class="encabezado">
                <p class="encabezado-titulo">BovWeight CR</p>
                <p class="encabezado-subtitulo">Bitácora de Pesaje e Inteligencia Artificial</p>
                <p class="encabezado-periodo">Período: ' . $periodoTexto . '</p>
            </div>';

            $html .= $avisoTecnico;

            // ── HTML: Metadata ────────────────────────────────────────────
            $html .= '
            <div class="meta-section">
                <table class="meta-grid">
                    <tr>
                        <td class="meta-label">Tipo de Reporte:</td>
                        <td>Historial y Bitácora de Pesajes</td>
                        <td class="meta-label">Finca:</td>
                        <td>' . $fincaNombre . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Período Analizado:</td>
                        <td>' . $periodoTexto . '</td>
                        <td class="meta-label">Total Pesajes (histórico):</td>
                        <td>' . $pesajes->count() . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Generado por:</td>
                        <td>' . htmlspecialchars($nombreUsuario) . '</td>
                        <td class="meta-label">Fecha de Generación:</td>
                        <td>' . now()->format('d/m/Y H:i:s') . '</td>
                    </tr>
                </table>
            </div>';

            // ── HTML: Métricas del mes ────────────────────────────────────
            $html .= '<p class="metricas-titulo">Métricas del Período — ' . $periodoTexto . '</p>';

            if ($totalMes === 0) {
                $html .= '
                <div class="sin-datos">
                    <p class="sin-datos-icono">📋</p>
                    <p><strong>No hay pesajes registrados durante el período mensual analizado.</strong></p>
                    <p style="font-size:11px;margin-top:6px">
                        Los registros históricos completos se muestran en la tabla a continuación.
                    </p>
                </div>';
            } else {
                $html .= '
                <table class="metricas-grid">
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Pesajes del mes</p>
                            <p class="metrica-valor">' . $totalMes . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Peso promedio estimado</p>
                            <p class="metrica-valor">' . ($promMes !== null ? $promMes . ' kg' : '—') . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Peso mínimo del mes</p>
                            <p class="metrica-valor-sm">' . ($minMes !== null ? $minMes . ' kg' : '—') . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Peso máximo del mes</p>
                            <p class="metrica-valor-sm">' . ($maxMes !== null ? $maxMes . ' kg' : '—') . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Pesajes manuales</p>
                            <p class="metrica-valor-sm">' . $pesajesManuales . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Pesajes estimados por IA (YOLOv8)</p>
                            <p class="metrica-valor-sm">' . $pesajesIA . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="metrica-celda" colspan="2">
                            <p class="metrica-etiqueta">Último pesaje del mes</p>
                            <p class="metrica-valor-sm">' . ($ultimaFechaMes ?? '—') . '</p>
                        </td>
                    </tr>
                </table>';
            }

            // ── HTML: Tabla histórica ─────────────────────────────────────
            $html .= '<p class="tabla-titulo">Registros Históricos de Pesaje</p>';

            if ($pesajes->isEmpty()) {
                $html .= '
                <div class="sin-datos">
                    <p class="sin-datos-icono">⚖️</p>
                    <p><strong>No hay pesajes registrados para el alcance seleccionado.</strong></p>
                </div>';
            } else {
                $html .= '
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Animal</th>
                            <th>Arete</th>
                            <th>Raza</th>
                            <th>Finca</th>
                            <th>Peso Estimado</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($pesajes as $p) {
                    $fechaFormato = ($p->fecha_pesaje instanceof Carbon)
                        ? $p->fecha_pesaje->format('d/m/Y')
                        : Carbon::parse($p->fecha_pesaje)->format('d/m/Y');

                    $esIA = str_contains($p->notas ?? '', 'YOLOv8');
                    $badgeClase = $esIA ? 'badge badge-ia' : 'badge';
                    $badgeTexto = $esIA ? 'Estimado IA' : 'Manual';

                    $html .= '
                        <tr>
                            <td>' . $fechaFormato . '</td>
                            <td>' . htmlspecialchars($p->animal->nombre) . '</td>
                            <td>' . htmlspecialchars($p->animal->arete) . '</td>
                            <td>' . htmlspecialchars($p->animal->raza) . '</td>
                            <td>' . htmlspecialchars($p->animal->farm->nombre) . '</td>
                            <td><strong>' . round($p->peso_estimado) . ' kg</strong></td>
                            <td><span class="' . $badgeClase . '">' . $badgeTexto . '</span></td>
                        </tr>';
                }

                $html .= '
                    </tbody>
                </table>';
            }

        // ════════════════════════════════════════════════════════════════
        // REPORTE GENERAL / POR FINCA
        // ════════════════════════════════════════════════════════════════
        } else {

            $tipoNombre = ($type === 'finca') ? 'Detallado por Finca' : 'Inventario General de Hato';

            // ── Métricas generales ─────────────────────────────────────────
            $totalAnimales = $animales->count();
            $conPeso       = $animales->filter(fn($a) => $a->peso_actual !== null && $a->peso_actual > 0)->count();
            $sinPeso       = $totalAnimales - $conPeso;

            $animalesConPeso = $animales->filter(fn($a) => $a->peso_actual !== null && $a->peso_actual > 0);
            $pesoPromedio = $conPeso > 0 ? round($animalesConPeso->avg('peso_actual'), 1) : null;
            $pesoMin      = $conPeso > 0 ? round($animalesConPeso->min('peso_actual'), 1) : null;
            $pesoMax      = $conPeso > 0 ? round($animalesConPeso->max('peso_actual'), 1) : null;

            // Pesajes del mes entre todos los animales
            $todosRegistros = $animales->flatMap(fn($a) => $a->weightRecords);
            $pesajesMes = $todosRegistros->filter(function ($r) use ($mesActual, $anioActual) {
                $fecha = ($r->fecha_pesaje instanceof Carbon)
                    ? $r->fecha_pesaje
                    : Carbon::parse($r->fecha_pesaje);
                return $fecha->month === $mesActual && $fecha->year === $anioActual;
            });

            $totalPesajesMes = $pesajesMes->count();

            $ultimoRegistro = $todosRegistros->sortByDesc(fn($r) =>
                ($r->fecha_pesaje instanceof Carbon)
                    ? $r->fecha_pesaje->timestamp
                    : Carbon::parse($r->fecha_pesaje)->timestamp
            )->first();

            $ultimaFechaRegistro = $ultimoRegistro
                ? (($ultimoRegistro->fecha_pesaje instanceof Carbon)
                    ? $ultimoRegistro->fecha_pesaje->format('d/m/Y')
                    : Carbon::parse($ultimoRegistro->fecha_pesaje)->format('d/m/Y'))
                : null;

            // ── HTML: Encabezado ──────────────────────────────────────────
            $html .= '
            <div class="encabezado">
                <p class="encabezado-titulo">BovWeight CR</p>
                <p class="encabezado-subtitulo">' . $tipoNombre . '</p>
                <p class="encabezado-periodo">Período: ' . $periodoTexto . '</p>
            </div>';

            $html .= $avisoTecnico;

            // ── HTML: Metadata ────────────────────────────────────────────
            $html .= '
            <div class="meta-section">
                <table class="meta-grid">
                    <tr>
                        <td class="meta-label">Tipo de Reporte:</td>
                        <td>' . $tipoNombre . '</td>
                        <td class="meta-label">Finca:</td>
                        <td>' . $fincaNombre . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Período Analizado:</td>
                        <td>' . $periodoTexto . '</td>
                        <td class="meta-label">Total Cabezas Activas:</td>
                        <td>' . $totalAnimales . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Generado por:</td>
                        <td>' . htmlspecialchars($nombreUsuario) . '</td>
                        <td class="meta-label">Fecha de Generación:</td>
                        <td>' . now()->format('d/m/Y H:i:s') . '</td>
                    </tr>
                </table>
            </div>';

            // ── HTML: Métricas técnicas ───────────────────────────────────
            $html .= '<p class="metricas-titulo">Métricas Técnicas — ' . $periodoTexto . '</p>';

            if ($totalAnimales === 0) {
                $html .= '
                <div class="sin-datos">
                    <p class="sin-datos-icono">🐄</p>
                    <p><strong>No hay animales registrados para el alcance seleccionado.</strong></p>
                </div>';
            } else {
                $html .= '
                <table class="metricas-grid">
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Total animales activos</p>
                            <p class="metrica-valor">' . $totalAnimales . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Con peso registrado</p>
                            <p class="metrica-valor">' . $conPeso . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Sin peso registrado</p>
                            <p class="metrica-valor-sm">' . $sinPeso . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Peso promedio estimado</p>
                            <p class="metrica-valor-sm">' . ($pesoPromedio !== null ? $pesoPromedio . ' kg' : '—') . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Peso mínimo registrado</p>
                            <p class="metrica-valor-sm">' . ($pesoMin !== null ? $pesoMin . ' kg' : '—') . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Peso máximo registrado</p>
                            <p class="metrica-valor-sm">' . ($pesoMax !== null ? $pesoMax . ' kg' : '—') . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Pesajes del mes (' . $periodoTexto . ')</p>
                            <p class="metrica-valor-sm">' . $totalPesajesMes . '</p>
                        </td>
                        <td class="metrica-celda">
                            <p class="metrica-etiqueta">Último pesaje registrado</p>
                            <p class="metrica-valor-sm">' . ($ultimaFechaRegistro ?? '—') . '</p>
                        </td>
                    </tr>
                </table>';

                if ($totalPesajesMes === 0) {
                    $html .= '
                    <div class="sin-datos" style="margin-top:4px">
                        <p style="margin:0;font-size:12px">
                            📋 No hay pesajes registrados durante el período mensual analizado.
                        </p>
                    </div>';
                }
            }

            // ── HTML: Tabla de ganado ─────────────────────────────────────
            $html .= '<p class="tabla-titulo">Ganado Registrado</p>';

            if ($animales->isEmpty()) {
                $html .= '
                <div class="sin-datos">
                    <p class="sin-datos-icono">🐄</p>
                    <p><strong>No hay animales registrados para el alcance seleccionado.</strong></p>
                </div>';
            } else {
                $html .= '
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Arete</th>
                            <th>Raza</th>
                            <th>Género</th>
                            <th>Finca</th>
                            <th>Peso Actual</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($animales as $animal) {
                    $tienePeso  = $animal->peso_actual !== null && $animal->peso_actual > 0;
                    $pesoBadge  = $tienePeso ? 'badge badge-ia' : 'badge badge-pendiente';
                    $pesoEstado = $tienePeso ? 'Con peso' : 'Pendiente';
                    $pesoTexto  = $tienePeso ? round($animal->peso_actual) . ' kg' : 'Sin pesar';

                    $html .= '
                        <tr>
                            <td>' . htmlspecialchars($animal->nombre) . '</td>
                            <td>' . htmlspecialchars($animal->arete) . '</td>
                            <td>' . htmlspecialchars($animal->raza) . '</td>
                            <td>' . htmlspecialchars($animal->genero) . '</td>
                            <td>' . htmlspecialchars($animal->farm->nombre) . '</td>
                            <td><strong>' . $pesoTexto . '</strong></td>
                            <td><span class="' . $pesoBadge . '">' . $pesoEstado . '</span></td>
                        </tr>';
                }

                $html .= '
                    </tbody>
                </table>';
            }
        }

        // ── Pie de página ────────────────────────────────────────────────────
        $html .= '
            <div class="footer">
                BovWeight CR &mdash; Impulsando la Ganadería de Precisión.<br>
                <span style="font-size:9px">
                    Reporte generado el ' . now()->format('d/m/Y \a \l\a\s H:i:s') . '
                    &bull; Las estimaciones no reemplazan una báscula oficial certificada.
                </span>
            </div>
        </body></html>';

        $pdf = Pdf::loadHTML($html);
        return $pdf->download('reporte_' . $type . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
