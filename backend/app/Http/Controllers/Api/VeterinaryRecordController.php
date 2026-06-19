<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\VeterinaryRecord;
use Illuminate\Http\Request;

class VeterinaryRecordController extends Controller
{
    /**
     * Listar los registros veterinarios de un animal.
     * Accesible por el dueño de la finca o un veterinario con acceso compartido.
     */
    public function index(Request $request, $animalId)
    {
        $animal = Animal::with('farm')->findOrFail($animalId);
        $user   = $request->user();

        if (!$user->hasRole('admin') && (int)$animal->farm->user_id !== (int)$user->id && !$user->hasSharedAccess($animal->farm_id)) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        $registros = $animal->veterinaryRecords()
            ->with('veterinario:id,name')
            ->get();

        return response()->json([
            'mensaje' => 'Historial veterinario obtenido exitosamente',
            'datos'   => $registros,
        ]);
    }

    /**
     * Crear un nuevo registro veterinario para un animal.
     * Accesible únicamente por un veterinario invitado asignado a esa finca o administrador.
     */
    public function store(Request $request, $animalId)
    {
        $animal = Animal::with('farm')->findOrFail($animalId);
        $user   = $request->user();

        // Solo un veterinario asignado a esta finca o un administrador puede registrar atenciones veterinarias
        $esVeterinarioAutorizado = ($user->hasRole('veterinario') && (int)$user->invited_farm_id === (int)$animal->farm_id)
                                || $user->hasRole('admin');

        if (!$esVeterinarioAutorizado) {
            return response()->json(['mensaje' => 'No autorizado para registrar atenciones veterinarias. Esta acción es exclusiva para veterinarios autorizados.'], 403);
        }

        $validated = $request->validate([
            'tipo'            => 'required|in:observacion,tratamiento,vacuna,cirugia,revision',
            'fecha_atencion'  => 'required|date|before_or_equal:today',
            'diagnostico'     => 'nullable|string|max:1000',
            'tratamiento'     => 'nullable|string|max:1000',
            'medicamentos'    => 'nullable|string|max:500',
            'dosis'           => 'nullable|string|max:255',
            'proxima_cita'    => 'nullable|date|after:fecha_atencion',
            'observaciones'   => 'nullable|string|max:2000',
            'recomendaciones' => 'nullable|string|max:2000',
            'peso_al_momento' => 'nullable|numeric|min:0|max:3000',
        ]);

        $validated['animal_id']      = $animal->id;
        $validated['veterinario_id'] = $user->id;

        $registro = VeterinaryRecord::create($validated);
        $registro->load('veterinario:id,name');

        return response()->json([
            'mensaje' => 'Registro veterinario creado exitosamente',
            'datos'   => $registro,
        ], 201);
    }

    /**
     * Actualizar un registro veterinario existente.
     * Accesible únicamente por el veterinario creador del registro o administrador.
     */
    public function update(Request $request, $id)
    {
        $registro = VeterinaryRecord::with('animal.farm')->findOrFail($id);
        $user     = $request->user();

        $esVeterinarioAutorizado = ($user->hasRole('veterinario') && (int)$user->invited_farm_id === (int)$registro->animal->farm_id)
                                || $user->hasRole('admin');

        if (!$esVeterinarioAutorizado) {
            return response()->json(['mensaje' => 'No autorizado para modificar este registro. Esta acción es exclusiva para el veterinario creador del registro.'], 403);
        }

        $validated = $request->validate([
            'tipo'            => 'sometimes|required|in:observacion,tratamiento,vacuna,cirugia,revision',
            'fecha_atencion'  => 'sometimes|required|date|before_or_equal:today',
            'diagnostico'     => 'nullable|string|max:1000',
            'tratamiento'     => 'nullable|string|max:1000',
            'medicamentos'    => 'nullable|string|max:500',
            'dosis'           => 'nullable|string|max:255',
            'proxima_cita'    => 'nullable|date',
            'observaciones'   => 'nullable|string|max:2000',
            'recomendaciones' => 'nullable|string|max:2000',
            'peso_al_momento' => 'nullable|numeric|min:0|max:3000',
        ]);

        $registro->update($validated);
        $registro->load('veterinario:id,name');

        return response()->json([
            'mensaje' => 'Registro veterinario actualizado exitosamente',
            'datos'   => $registro,
        ]);
    }

    /**
     * Eliminar un registro veterinario.
     * Solo el veterinario creador o administrador puede eliminar.
     */
    public function destroy(Request $request, $id)
    {
        $registro = VeterinaryRecord::with('animal.farm')->findOrFail($id);
        $user     = $request->user();

        $esVeterinarioAutorizado = ($user->hasRole('veterinario') && (int)$user->invited_farm_id === (int)$registro->animal->farm_id)
                                || $user->hasRole('admin');

        if (!$esVeterinarioAutorizado) {
            return response()->json(['mensaje' => 'No autorizado para eliminar este registro. Esta acción es exclusiva para el veterinario creador del registro.'], 403);
        }

        $registro->delete();

        return response()->json([
            'mensaje' => 'Registro veterinario eliminado exitosamente',
        ]);
    }
}
