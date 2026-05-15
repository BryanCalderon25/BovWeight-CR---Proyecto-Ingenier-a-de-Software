<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\WeightRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MLIntegrationController extends Controller
{
    /**
     * Procesar imagen y obtener estimación de peso desde el microservicio.
     */
    public function predictWeight(Request $request)
    {
        $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'imagen' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        $animal = Animal::findOrFail($request->animal_id);

        if ($animal->farm->user_id !== $request->user()->id) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        // Subir la imagen al servidor (storage)
        $path = $request->file('imagen')->store('animal_images', 'public');

        // Guardar registro de imagen en la base de datos
        $animalImage = $animal->images()->create([
            'ruta_imagen' => $path,
            'tipo' => 'lateral', // Asumido por defecto para la predicción
        ]);

        try {
            // Llamar al microservicio Flask de YOLOv8
            $mlServiceUrl = env('ML_SERVICE_URL', 'http://ml-service:5000');
            
            $response = Http::attach(
                'file', file_get_contents($request->file('imagen')->getRealPath()), $request->file('imagen')->getClientOriginalName()
            )->post("{$mlServiceUrl}/predict");

            if ($response->successful()) {
                $data = $response->json();
                
                $pesoEstimado = $data['peso_estimado'] ?? 0;
                
                // Guardar el registro de pesaje
                $weightRecord = WeightRecord::create([
                    'animal_id' => $animal->id,
                    'peso_estimado' => $pesoEstimado,
                    'fecha_pesaje' => now(),
                    'animal_image_id' => $animalImage->id,
                    'datos_modelo' => $data,
                    'estado_sincronizacion' => 'sincronizado',
                    'notas' => 'Pesaje estimado por modelo YOLOv8',
                ]);

                // Actualizar peso actual del animal
                $animal->update(['peso_actual' => $pesoEstimado]);

                return response()->json([
                    'mensaje' => 'Pesaje estimado exitosamente',
                    'datos' => [
                        'peso_estimado' => $pesoEstimado,
                        'detalles_modelo' => $data,
                        'registro' => $weightRecord
                    ],
                    'disclaimer' => 'Nota: Esta es una estimación basada en visión artificial y puede tener un margen de error.'
                ]);
            }

            return response()->json([
                'mensaje' => 'Error al comunicarse con el servicio de estimación.',
                'detalles' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Excepción al procesar la estimación de peso.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
