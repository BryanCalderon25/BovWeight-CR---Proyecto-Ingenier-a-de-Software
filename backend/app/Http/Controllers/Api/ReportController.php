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

        if ($animal->farm->user_id !== $request->user()->id) {
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
}
