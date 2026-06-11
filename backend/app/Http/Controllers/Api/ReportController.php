<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generar un reporte PDF del historial de peso de un animal.
     */
    public function generateAnimalReport(Request $request, $animalId)
    {
        $animal = Animal::with('farm', 'weightRecords')->findOrFail($animalId);

        if ((int)$animal->farm->user_id !== (int)$request->user()->id && !$request->user()->hasSharedAccess($animal->farm_id)) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        // Generar la vista HTML para el PDF (se requiere crear una vista resources/views/reports/animal.blade.php)
        // Por ahora, generaremos HTML básico directo para asegurar funcionalidad.
        
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
     */
    public function generateReport(Request $request)
    {
        $user = $request->user();
        $type = $request->query('tipo', 'general');
        $fincaId = $request->query('finca_id');

        // Determinar si es invitado o administrador
        $isGuest = $user->invited_farm_id && (!$user->guest_expires_at || now()->lt($user->guest_expires_at));

        if ($isGuest) {
            $fincaId = $user->invited_farm_id;
        }

        $fincaNombre = 'Todas las Fincas';
        if ($fincaId) {
            if ($isGuest) {
                $finca = \App\Models\Farm::findOrFail($fincaId);
            } else {
                $finca = \App\Models\Farm::where('id', $fincaId)->where('user_id', $user->id)->firstOrFail();
            }
            $fincaNombre = $finca->nombre;
        }

        // Construir consulta base de animales
        $queryAnimales = Animal::query();

        if ($isGuest) {
            $queryAnimales->where('farm_id', $user->invited_farm_id);
        } else {
            $queryAnimales->whereHas('farm', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($fincaId && !$isGuest) {
            $queryAnimales->where('farm_id', $fincaId);
        }

        $animales = $queryAnimales->with('farm', 'weightRecords')->get();

        // Estilos CSS unificados
        $css = '
        <style>
            body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #2B2D2F; line-height: 1.5; padding: 20px; }
            .header-container { display: flex; justify-content: space-between; border-bottom: 3px solid #656D4A; padding-bottom: 15px; margin-bottom: 25px; }
            .title { color: #656D4A; font-size: 26px; font-weight: bold; margin: 0; }
            .subtitle { color: #8B8E83; font-size: 14px; margin: 5px 0 0 0; }
            .meta-section { background-color: #F4F6F0; border-radius: 8px; padding: 15px; margin-bottom: 30px; }
            .meta-grid { width: 100%; border-collapse: collapse; }
            .meta-grid td { padding: 6px 12px; font-size: 13px; }
            .meta-label { font-weight: bold; color: #414833; }
            .table-title { color: #414833; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .data-table th { background-color: #656D4A; color: white; padding: 12px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
            .data-table td { padding: 12px 10px; border-bottom: 1px solid #E3E5D7; font-size: 13px; }
            .data-table tr:nth-child(even) { background-color: #F9FAF7; }
            .badge { background-color: #A3B19B; color: #2B2D2F; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
            .badge-success { background-color: #D8E2DC; color: #4F5D54; }
            .footer { text-align: center; font-size: 10px; color: #8B8E83; margin-top: 50px; border-top: 1px solid #E3E5D7; padding-top: 15px; }
        </style>';

        $html = '<html><head>' . $css . '</head><body>';

        if ($type === 'pesajes') {
            // Obtener todos los registros de pesaje
            $queryPesajes = \App\Models\WeightRecord::query();

            if ($isGuest) {
                $queryPesajes->whereHas('animal', function ($q) use ($user) {
                    $q->where('farm_id', $user->invited_farm_id);
                });
            } else {
                $queryPesajes->whereHas('animal.farm', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            if ($fincaId && !$isGuest) {
                $queryPesajes->whereHas('animal', function ($q) use ($fincaId) {
                    $q->where('farm_id', $fincaId);
                });
            }

            $pesajes = $queryPesajes->with(['animal.farm'])->orderBy('fecha_pesaje', 'desc')->get();

            $html .= '
            <div class="header-container">
                <div>
                    <h1 class="title">BovWeight CR</h1>
                    <h3 class="subtitle">Bitácora de Pesaje e Inteligencia Artificial</h3>
                </div>
            </div>
            
            <div class="meta-section">
                <table class="meta-grid">
                    <tr>
                        <td class="meta-label">Tipo de Reporte:</td>
                        <td>Historial y Bitácora de Pesajes</td>
                        <td class="meta-label">Finca:</td>
                        <td>' . $fincaNombre . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Fecha de Generación:</td>
                        <td>' . now()->format('d/m/Y H:i:s') . '</td>
                        <td class="meta-label">Total Pesajes Registrados:</td>
                        <td>' . $pesajes->count() . '</td>
                    </tr>
                </table>
            </div>

            <h2 class="table-title">Registros Históricos</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre Animal</th>
                        <th>Arete</th>
                        <th>Raza</th>
                        <th>Finca</th>
                        <th>Peso Estimado</th>
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($pesajes as $p) {
                $html .= '
                    <tr>
                        <td>' . $p->fecha_pesaje->format('d/m/Y') . '</td>
                        <td>' . $p->animal->nombre . '</td>
                        <td>' . $p->animal->arete . '</td>
                        <td>' . $p->animal->raza . '</td>
                        <td>' . $p->animal->farm->nombre . '</td>
                        <td><strong>' . round($p->peso_estimado) . ' kg</strong></td>
                        <td><span class="badge">' . (strpos($p->notas, 'YOLOv8') !== false ? 'Estimado IA' : 'Manual') . '</span></td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>';

        } else {
            // Reporte General o Por Finca (Livestock inventory)
            $html .= '
            <div class="header-container">
                <div>
                    <h1 class="title">BovWeight CR</h1>
                    <h3 class="subtitle">Inventario General y Métricas de Hato</h3>
                </div>
            </div>
            
            <div class="meta-section">
                <table class="meta-grid">
                    <tr>
                        <td class="meta-label">Tipo de Reporte:</td>
                        <td>' . ($type === 'finca' ? 'Detallado por Finca' : 'Inventario General de Hato') . '</td>
                        <td class="meta-label">Finca:</td>
                        <td>' . $fincaNombre . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Fecha de Generación:</td>
                        <td>' . now()->format('d/m/Y H:i:s') . '</td>
                        <td class="meta-label">Total Cabezas Activas:</td>
                        <td>' . $animales->count() . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Peso Promedio Actual:</td>
                        <td><strong>' . ($animales->count() > 0 ? round($animales->avg('peso_actual'), 1) : 0) . ' kg</strong></td>
                        <td class="meta-label">Ganado Brahman:</td>
                        <td>' . $animales->where('raza', 'Brahman')->count() . ' cabezas</td>
                    </tr>
                </table>
            </div>

            <h2 class="table-title">Ganado Registrado</h2>
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
                $html .= '
                    <tr>
                        <td>' . $animal->nombre . '</td>
                        <td>' . $animal->arete . '</td>
                        <td>' . $animal->raza . '</td>
                        <td>' . $animal->genero . '</td>
                        <td>' . $animal->farm->nombre . '</td>
                        <td><strong>' . ($animal->peso_actual ? round($animal->peso_actual) . ' kg' : 'Sin pesar') . '</strong></td>
                        <td><span class="badge badge-success">' . ($animal->peso_actual ? 'Medido' : 'Pendiente') . '</span></td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>';
        }

        $html .= '
            <div class="footer">
                BovWeight CR - Impulsando la Ganadería de Precision.
            </div>
        </body></html>';

        $pdf = Pdf::loadHTML($html);
        return $pdf->download('reporte_' . $type . '_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Generar el Reporte Veterinario PDF de un animal específico.
     * Este método es el único punto de lógica para este reporte —
     * es llamado tanto desde el módulo de Reportes como desde el Historial Veterinario.
     */
    public function generateVeterinaryReport(Request $request, $animalId)
    {
        $animal = \App\Models\Animal::with([
            'farm',
            'weightRecords' => fn($q) => $q->orderBy('fecha_pesaje', 'asc'),
            'veterinaryRecords.veterinario',
        ])->findOrFail($animalId);

        $user = $request->user();

        if ((int)$animal->farm->user_id !== (int)$user->id && !$user->hasSharedAccess($animal->farm_id)) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        // ──────────────────────────────────────────────
        // CSS reutilizando el mismo sistema de estilos
        // ──────────────────────────────────────────────
        $css = '
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");
            body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #2B2D2F; line-height: 1.5; padding: 20px; }
            .header-container { display: flex; justify-content: space-between; border-bottom: 3px solid #656D4A; padding-bottom: 15px; margin-bottom: 20px; }
            .title { color: #656D4A; font-size: 26px; font-weight: bold; margin: 0; }
            .subtitle { color: #8B8E83; font-size: 13px; margin: 4px 0 0 0; }
            .section-title { color: #414833; font-size: 16px; font-weight: bold; margin: 22px 0 10px 0; border-left: 4px solid #656D4A; padding-left: 10px; }
            .meta-section { background-color: #F4F6F0; border-radius: 8px; padding: 14px; margin-bottom: 20px; }
            .meta-grid { width: 100%; border-collapse: collapse; }
            .meta-grid td { padding: 5px 10px; font-size: 12px; }
            .meta-label { font-weight: bold; color: #414833; width: 160px; }
            .data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            .data-table th { background-color: #656D4A; color: white; padding: 10px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
            .data-table td { padding: 10px 8px; border-bottom: 1px solid #E3E5D7; font-size: 12px; vertical-align: top; }
            .data-table tr:nth-child(even) { background-color: #F9FAF7; }
            .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
            .badge-green { background-color: #D8E2DC; color: #4F5D54; }
            .badge-amber { background-color: #FFF3CD; color: #856404; }
            .badge-red   { background-color: #F8D7DA; color: #842029; }
            .badge-blue  { background-color: #D0E4FF; color: #0d47a1; }
            .alert-box { border-radius: 6px; padding: 10px 14px; margin: 6px 0; font-size: 12px; }
            .alert-red    { background: #F8D7DA; border-left: 4px solid #dc3545; color: #842029; }
            .alert-amber  { background: #FFF3CD; border-left: 4px solid #ffc107; color: #856404; }
            .alert-info   { background: #D0E4FF; border-left: 4px solid #0d47a1; color: #0d47a1; }
            .empty-section { color: #8B8E83; font-style: italic; font-size: 12px; padding: 8px 0; }
            .footer { text-align: center; font-size: 10px; color: #8B8E83; margin-top: 40px; border-top: 1px solid #E3E5D7; padding-top: 14px; }
            .disclaimer { background: #FFF3CD; border: 1px solid #ffc107; border-radius: 6px; padding: 10px 14px; font-size: 11px; color: #856404; margin-top: 20px; }
        </style>';

        $html = '<html><head>' . $css . '</head><body>';

        // ──────────────────────────────────────────────
        // ENCABEZADO
        // ──────────────────────────────────────────────
        $html .= '
        <div class="header-container">
            <div>
                <h1 class="title">BovWeight CR</h1>
                <p class="subtitle">Reporte Veterinario · Seguimiento de Salud y Crecimiento</p>
            </div>
            <div style="text-align:right;font-size:11px;color:#8B8E83;">
                <strong>Fecha de Generación</strong><br>' . now()->format('d/m/Y H:i') . '<br>
                <strong>Veterinario</strong><br>' . ($user->name ?? 'Sin especificar') . '
            </div>
        </div>';

        // ──────────────────────────────────────────────
        // SECCIÓN 1: INFORMACIÓN GENERAL DEL ANIMAL
        // ──────────────────────────────────────────────
        $html .= '<div class="section-title">1. Información General del Animal</div>';
        $html .= '<div class="meta-section"><table class="meta-grid">';
        $html .= '<tr>
            <td class="meta-label">Número de Arete:</td><td>' . e($animal->arete) . '</td>
            <td class="meta-label">Nombre:</td><td>' . e($animal->nombre ?? '—') . '</td>
        </tr>
        <tr>
            <td class="meta-label">Raza:</td><td>' . e($animal->raza ?? '—') . '</td>
            <td class="meta-label">Género:</td><td>' . e($animal->genero) . '</td>
        </tr>
        <tr>
            <td class="meta-label">Propósito:</td><td>' . e($animal->proposito ?? '—') . '</td>
            <td class="meta-label">Fecha de Nacimiento:</td><td>' . ($animal->fecha_nacimiento ? \Carbon\Carbon::parse($animal->fecha_nacimiento)->format('d/m/Y') : '—') . '</td>
        </tr>
        <tr>
            <td class="meta-label">Finca:</td><td>' . e($animal->farm->nombre) . '</td>
            <td class="meta-label">Ubicación:</td><td>' . e($animal->farm->ubicacion ?? '—') . '</td>
        </tr>
        <tr>
            <td class="meta-label">Estado:</td>
            <td><span class="badge badge-green">Activo</span></td>
            <td class="meta-label">Fecha de Registro:</td>
            <td>' . $animal->created_at->format('d/m/Y') . '</td>
        </tr>';
        $html .= '</table></div>';

        // ──────────────────────────────────────────────
        // SECCIÓN 2: HISTORIAL DE CRECIMIENTO
        // ──────────────────────────────────────────────
        $html .= '<div class="section-title">2. Información de Crecimiento</div>';
        $weightRecords = $animal->weightRecords->sortBy('fecha_pesaje')->values();

        if ($weightRecords->count() > 0) {
            $pesoInicial  = $weightRecords->first()->peso_estimado;
            $pesoActual   = $animal->peso_actual ?? $weightRecords->last()->peso_estimado;
            $diferencia   = round($pesoActual - $pesoInicial, 2);
            $porcentaje   = $pesoInicial > 0 ? round(($diferencia / $pesoInicial) * 100, 1) : 0;
            $tendencia    = $diferencia >= 0 ? '↑ Aumento' : '↓ Pérdida';
            $badgeClase   = $diferencia >= 0 ? 'badge-green' : 'badge-red';

            $html .= '<div class="meta-section"><table class="meta-grid">
                <tr>
                    <td class="meta-label">Peso Inicial Registrado:</td>
                    <td><strong>' . round($pesoInicial) . ' kg</strong> (' . $weightRecords->first()->fecha_pesaje->format('d/m/Y') . ')</td>
                    <td class="meta-label">Peso Más Reciente:</td>
                    <td><strong>' . round($pesoActual) . ' kg</strong></td>
                </tr>
                <tr>
                    <td class="meta-label">Diferencia de Peso:</td>
                    <td><strong>' . ($diferencia >= 0 ? '+' : '') . $diferencia . ' kg</strong></td>
                    <td class="meta-label">Tendencia:</td>
                    <td><span class="badge ' . $badgeClase . '">' . $tendencia . ' (' . $porcentaje . '%)</span></td>
                </tr>
            </table></div>';

            // Tabla cronológica de pesajes
            $html .= '<p style="font-size:12px;color:#414833;font-weight:600;margin:10px 0 6px 0;">Historial Cronológico de Pesajes</p>';
            $html .= '<table class="data-table"><thead><tr>
                <th>#</th><th>Fecha</th><th>Peso Estimado</th><th>Variación</th><th>Tipo</th>
            </tr></thead><tbody>';

            $pesoAnterior = null;
            foreach ($weightRecords as $idx => $wr) {
                $variacion = $pesoAnterior !== null
                    ? (round($wr->peso_estimado - $pesoAnterior, 1) >= 0 ? '+' : '') . round($wr->peso_estimado - $pesoAnterior, 1) . ' kg'
                    : '—';
                $esIA     = $wr->notas && str_contains($wr->notas, 'YOLOv8');
                $html .= '<tr>
                    <td>' . ($idx + 1) . '</td>
                    <td>' . $wr->fecha_pesaje->format('d/m/Y') . '</td>
                    <td><strong>' . round($wr->peso_estimado) . ' kg</strong></td>
                    <td>' . $variacion . '</td>
                    <td><span class="badge ' . ($esIA ? 'badge-blue' : 'badge-green') . '">' . ($esIA ? 'Estimado IA' : 'Manual') . '</span></td>
                </tr>';
                $pesoAnterior = $wr->peso_estimado;
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty-section">Este animal no posee registros de pesaje.</p>';
        }

        // ──────────────────────────────────────────────
        // SECCIÓN 3: ALERTAS AUTOMÁTICAS
        // ──────────────────────────────────────────────
        $alertas = [];

        if ($weightRecords->count() >= 2) {
            $ultimos = $weightRecords->sortByDesc('fecha_pesaje')->values();
            $ultimo  = $ultimos->get(0)?->peso_estimado ?? 0;
            $penultimo = $ultimos->get(1)?->peso_estimado ?? 0;
            if ($penultimo > 0) {
                $caida = (($penultimo - $ultimo) / $penultimo) * 100;
                if ($caida >= 5) {
                    $alertas[] = ['clase' => 'alert-red', 'texto' => '🔴 Pérdida de peso significativa: el animal perdió ' . round($caida, 1) . '% de su peso entre los dos últimos controles.'];
                }
            }
        }

        if ($weightRecords->count() > 0) {
            $ultimoRegistro = $weightRecords->sortByDesc('fecha_pesaje')->first();
            $diasSinPesaje  = now()->diffInDays($ultimoRegistro->fecha_pesaje);
            if ($diasSinPesaje > 30) {
                $alertas[] = ['clase' => 'alert-amber', 'texto' => '🟡 Sin pesajes en los últimos ' . $diasSinPesaje . ' días. Se recomienda un control de peso.'];
            }
        } else {
            $alertas[] = ['clase' => 'alert-amber', 'texto' => '🟡 El animal no posee ningún registro de pesaje.'];
        }

        if ($animal->veterinaryRecords->count() === 0) {
            $alertas[] = ['clase' => 'alert-info', 'texto' => 'ℹ️ Sin atenciones veterinarias registradas a la fecha.'];
        }

        if (!empty($alertas)) {
            $html .= '<div class="section-title">3. Alertas e Indicadores</div>';
            foreach ($alertas as $alerta) {
                $html .= '<div class="alert-box ' . $alerta['clase'] . '">' . $alerta['texto'] . '</div>';
            }
        }

        // ──────────────────────────────────────────────
        // SECCIÓN 4: HISTORIAL VETERINARIO
        // ──────────────────────────────────────────────
        $html .= '<div class="section-title">4. Historial de Atenciones Veterinarias</div>';
        $vetRecords = $animal->veterinaryRecords->sortByDesc('fecha_atencion')->values();

        if ($vetRecords->count() > 0) {
            $tiposLabel = [
                'observacion' => 'Observación',
                'tratamiento' => 'Tratamiento',
                'vacuna'      => 'Vacuna',
                'cirugia'     => 'Cirugía',
                'revision'    => 'Revisión',
            ];
            $html .= '<table class="data-table"><thead><tr>
                <th>Fecha</th><th>Tipo</th><th>Diagnóstico</th><th>Tratamiento / Medicamentos</th><th>Veterinario</th>
            </tr></thead><tbody>';
            foreach ($vetRecords as $vr) {
                $tratInfo = collect([$vr->tratamiento, $vr->medicamentos ? 'Med: ' . $vr->medicamentos : null, $vr->dosis ? 'Dosis: ' . $vr->dosis : null])
                    ->filter()->implode('<br>');
                $html .= '<tr>
                    <td>' . $vr->fecha_atencion->format('d/m/Y') . '</td>
                    <td><span class="badge badge-blue">' . ($tiposLabel[$vr->tipo] ?? $vr->tipo) . '</span></td>
                    <td>' . e($vr->diagnostico ?? '—') . '</td>
                    <td>' . ($tratInfo ?: '—') . '</td>
                    <td>' . e($vr->veterinario?->name ?? 'Sin registrar') . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty-section">Sin atenciones veterinarias registradas a la fecha.</p>';
        }

        // ──────────────────────────────────────────────
        // SECCIÓN 5: PRÓXIMAS ACCIONES Y SEGUIMIENTO
        // ──────────────────────────────────────────────
        $proximasCitas   = $vetRecords->filter(fn($r) => $r->proxima_cita && $r->proxima_cita->isFuture());
        $recomendaciones = $vetRecords->filter(fn($r) => !empty($r->recomendaciones));
        $observaciones   = $vetRecords->filter(fn($r) => !empty($r->observaciones));

        if ($proximasCitas->count() || $recomendaciones->count() || $observaciones->count()) {
            $html .= '<div class="section-title">5. Próximas Acciones y Seguimiento</div>';
            $html .= '<div class="meta-section">';

            if ($proximasCitas->count()) {
                $html .= '<p style="font-size:12px;font-weight:600;color:#414833;margin-bottom:4px;">📅 Próximas Citas Programadas</p>';
                foreach ($proximasCitas->sortBy('proxima_cita') as $pc) {
                    $html .= '<p style="font-size:12px;margin:2px 0;">• ' . $pc->proxima_cita->format('d/m/Y') . ' — ' . e($pc->diagnostico ?? 'Control general') . '</p>';
                }
            }

            if ($recomendaciones->count()) {
                $html .= '<p style="font-size:12px;font-weight:600;color:#414833;margin:10px 0 4px 0;">💡 Recomendaciones del Veterinario</p>';
                foreach ($recomendaciones->take(3) as $rec) {
                    $html .= '<p style="font-size:12px;margin:2px 0;">• ' . e($rec->recomendaciones) . '</p>';
                }
            }

            if ($observaciones->count()) {
                $html .= '<p style="font-size:12px;font-weight:600;color:#414833;margin:10px 0 4px 0;">📋 Observaciones de Seguimiento</p>';
                foreach ($observaciones->take(3) as $obs) {
                    $html .= '<p style="font-size:12px;margin:2px 0;">• ' . e($obs->observaciones) . ' <span style="color:#8B8E83">(' . $obs->fecha_atencion->format('d/m/Y') . ')</span></p>';
                }
            }

            $html .= '</div>';
        }

        // ──────────────────────────────────────────────
        // DISCLAIMER Y PIE DE PÁGINA
        // ──────────────────────────────────────────────
        $html .= '
        <div class="disclaimer">
            ⚠️ <strong>Advertencia:</strong> Los valores de peso registrados en este reporte son estimaciones generadas mediante inteligencia artificial (modelo YOLOv8) y/o registros manuales. Estas estimaciones <strong>no sustituyen</strong> una medición con báscula certificada. Para decisiones clínicas o comerciales, verifique siempre el peso con equipos calibrados y homologados.
        </div>
        <div class="footer">
            BovWeight CR · Reporte Veterinario · Generado el ' . now()->format('d/m/Y \a \l\a\s H:i') . ' · Uso exclusivo para seguimiento ganadero
        </div>';

        $html .= '</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->download('reporte_veterinario_' . $animal->arete . '_' . now()->format('Ymd') . '.pdf');
    }
}

