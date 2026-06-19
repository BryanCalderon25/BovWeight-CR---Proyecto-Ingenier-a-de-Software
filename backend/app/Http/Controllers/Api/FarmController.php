<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Listar fincas del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $farms = Farm::with('animals')->get();
        } else if ($user->hasRole('veterinario')) {
            $farms = $user->sharedFarms()->with('animals')->get();
        } else {
            $isGuest = $user->invited_farm_id && (!$user->guest_expires_at || now()->lt($user->guest_expires_at));

            if ($isGuest) {
                $farms = Farm::where('id', $user->invited_farm_id)->with('animals')->get();
            } else {
                $farms = $user->farms()->with('animals')->get();
            }
        }

        return response()->json([
            'mensaje' => 'Fincas obtenidas exitosamente',
            'datos' => $farms
        ]);
    }

    /**
     * Crear una nueva finca.
     */
    public function store(Request $request)
    {
        if ($request->user()->hasRole('veterinario') || $request->user()->hasRole('invitado')) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'area_hectareas' => 'nullable|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->all();

        if (!$request->user()->hasRole('admin')) {
            $data['user_id'] = $request->user()->id;
        } else if (empty($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        $farm = Farm::create($data);

        return response()->json([
            'mensaje' => 'Finca creada exitosamente',
            'datos' => $farm
        ], 201);
    }

    /**
     * Mostrar una finca específica.
     */
    public function show(Request $request, Farm $finca)
    {
        if (!$request->user()->hasRole('admin') && (int)$finca->user_id !== (int)$request->user()->id && !$request->user()->hasSharedAccess($finca->id)) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        $finca->load('animals');

        return response()->json([
            'mensaje' => 'Finca obtenida exitosamente',
            'datos' => $finca
        ]);
    }

    /**
     * Actualizar una finca.
     */
    public function update(Request $request, Farm $finca)
    {
        if ($request->user()->hasRole('veterinario') || $request->user()->hasRole('invitado')) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        if (!$request->user()->hasRole('admin') && (int)$finca->user_id !== (int)$request->user()->id) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'ubicacion' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'area_hectareas' => 'nullable|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->all();
        if (!$request->user()->hasRole('admin')) {
            unset($data['user_id']);
        }

        $finca->update($data);

        return response()->json([
            'mensaje' => 'Finca actualizada exitosamente',
            'datos' => $finca
        ]);
    }

    /**
     * Eliminar una finca.
     */
    public function destroy(Request $request, Farm $finca)
    {
        if ($request->user()->hasRole('veterinario') || $request->user()->hasRole('invitado')) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        if (!$request->user()->hasRole('admin') && (int)$finca->user_id !== (int)$request->user()->id) {
            return response()->json(['mensaje' => 'No autorizado'], 403);
        }

        $finca->delete();

        return response()->json([
            'mensaje' => 'Finca eliminada exitosamente'
        ]);
    }
}
