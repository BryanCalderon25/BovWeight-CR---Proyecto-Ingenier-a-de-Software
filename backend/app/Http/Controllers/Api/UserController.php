<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios.
     */
    public function index(Request $request)
    {
        $users = User::with(['invitedFarm', 'sharedFarms'])->get()->map(function ($user) {
            $user->role = $user->getRoleNames()->first() ?? 'ganadero';
            return $user;
        });

        return response()->json([
            'mensaje' => 'Usuarios obtenidos exitosamente',
            'datos' => $users
        ]);
    }

    /**
     * Registrar un nuevo usuario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,ganadero,trabajador,veterinario,invitado',
            'invited_farm_id' => 'nullable|exists:farms,id',
            'guest_expires_at' => 'nullable|date',
            'farm_ids' => 'nullable|array',
            'farm_ids.*' => 'exists:farms,id',
        ]);

        $farmIds = $request->input('farm_ids', []);
        
        if ($validated['role'] === 'veterinario') {
            if (empty($farmIds) && !empty($validated['invited_farm_id'])) {
                $farmIds = [$validated['invited_farm_id']];
            }
            if (empty($farmIds)) {
                return response()->json([
                    'mensaje' => 'El Veterinario debe estar vinculado al menos a una finca.'
                ], 422);
            }
        }

        if ($validated['role'] === 'invitado' && empty($validated['invited_farm_id'])) {
            return response()->json([
                'mensaje' => 'El Invitado debe estar vinculado a una finca específica.'
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'invited_farm_id' => ($validated['role'] === 'veterinario') ? ($farmIds[0] ?? null) : ($validated['invited_farm_id'] ?? null),
            'guest_expires_at' => $validated['guest_expires_at'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        if ($validated['role'] === 'veterinario') {
            $user->sharedFarms()->sync($farmIds);
        }

        $user->role = $validated['role'];
        $user->load(['invitedFarm', 'sharedFarms']);

        return response()->json([
            'mensaje' => 'Usuario creado exitosamente',
            'datos' => $user
        ], 201);
    }

    /**
     * Mostrar un usuario específico.
     */
    public function show(User $user)
    {
        $user->role = $user->getRoleNames()->first() ?? 'ganadero';
        $user->load(['invitedFarm', 'sharedFarms']);

        return response()->json([
            'mensaje' => 'Usuario obtenido exitosamente',
            'datos' => $user
        ]);
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,ganadero,trabajador,veterinario,invitado',
            'invited_farm_id' => 'nullable|exists:farms,id',
            'guest_expires_at' => 'nullable|date',
            'farm_ids' => 'nullable|array',
            'farm_ids.*' => 'exists:farms,id',
        ]);

        $role = $validated['role'] ?? ($user->getRoleNames()->first() ?? 'ganadero');
        $farmIds = $request->input('farm_ids', []);

        if ($role === 'veterinario') {
            if (empty($farmIds) && !empty($validated['invited_farm_id'])) {
                $farmIds = [$validated['invited_farm_id']];
            }
            if (empty($farmIds)) {
                return response()->json([
                    'mensaje' => 'El Veterinario debe estar vinculado al menos a una finca.'
                ], 422);
            }
        }

        if ($role === 'invitado') {
            $farmId = array_key_exists('invited_farm_id', $validated) ? $validated['invited_farm_id'] : $user->invited_farm_id;
            if (empty($farmId)) {
                return response()->json([
                    'mensaje' => 'El Invitado debe estar vinculado a una finca específica.'
                ], 422);
            }
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        // Actualizar invited_farm_id y guest_expires_at
        if ($role === 'veterinario') {
            $user->invited_farm_id = $farmIds[0] ?? null;
        } else if (array_key_exists('invited_farm_id', $validated)) {
            $user->invited_farm_id = $validated['invited_farm_id'];
        }

        if (array_key_exists('guest_expires_at', $validated)) {
            $user->guest_expires_at = $validated['guest_expires_at'];
        }

        $user->save();

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        if ($role === 'veterinario') {
            $user->sharedFarms()->sync($farmIds);
        } else {
            $user->sharedFarms()->detach();
        }

        $user->role = $user->getRoleNames()->first() ?? 'ganadero';
        $user->load(['invitedFarm', 'sharedFarms']);

        return response()->json([
            'mensaje' => 'Usuario actualizado exitosamente',
            'datos' => $user
        ]);
    }

    /**
     * Eliminar un usuario.
     */
    public function destroy(User $user)
    {
        $user->sharedFarms()->detach();
        $user->delete();

        return response()->json([
            'mensaje' => 'Usuario eliminado exitosamente'
        ]);
    }
}
