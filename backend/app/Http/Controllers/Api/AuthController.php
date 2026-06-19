<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registro de usuario.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        try {
            if (method_exists($user, 'assignRole') && \Spatie\Permission\Models\Role::where('name', 'ganadero')->exists()) {
                $user->assignRole('ganadero');
            }
        } catch (\Throwable $e) {
            \Log::warning('No se pudo asignar el rol ganadero: ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->setAttribute('role', 'ganadero');

        return response()->json([
            'mensaje' => 'Usuario registrado exitosamente',
            'datos' => $user,
            'token_acceso' => $token,
            'tipo_token' => 'Bearer',
        ], 201);
    }

    /**
     * Inicio de sesión.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['mensaje' => 'El usuario no existe en la base de datos.'], 404);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['mensaje' => 'La contraseña es incorrecta para este usuario.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->setAttribute('role', $user->getRoleNames()->first() ?? 'ganadero');

        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso',
            'datos' => $user,
            'token_acceso' => $token,
            'tipo_token' => 'Bearer',
        ]);
    }

    /**
     * Perfil del usuario autenticado.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->setAttribute('role', $user->getRoleNames()->first() ?? 'ganadero');

        return response()->json([
            'mensaje' => 'Perfil obtenido exitosamente',
            'datos' => $user,
        ]);
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada exitosamente y tokens revocados'
        ]);
    }

    /**
     * Solicitar recuperación de contraseña.
     */
    public function forgotPassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Ingrese su correo electrónico.',
            'email.email' => 'Ingrese un correo electrónico válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => $validator->errors()->first(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        $email = $request->email;
        $user = User::where('email', $email)->first();

        if ($user) {
            $codigo = (string) random_int(100000, 999999);

            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => \Illuminate\Support\Facades\Hash::make($codigo),
                    'created_at' => now(),
                ]
            );

            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\CorreoRecuperacion($codigo));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error al enviar correo de recuperación: ' . $e->getMessage());
            }
        }

        return response()->json([
            'mensaje' => 'Si el correo está registrado, recibirá un código para restablecer su contraseña.',
            'message' => 'Si el correo está registrado, recibirá un código para restablecer su contraseña.'
        ]);
    }

    /**
     * Verificar código de recuperación de contraseña.
     */
    public function verifyCode(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string|size:6',
        ], [
            'email.required' => 'Ingrese su correo electrónico.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'token.required' => 'El código es requerido.',
            'token.size' => 'El código debe tener exactamente 6 dígitos.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => $validator->errors()->first(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'mensaje' => 'El código ingresado no es válido.',
                'message' => 'El código ingresado no es válido.'
            ], 400);
        }

        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(10)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            return response()->json([
                'mensaje' => 'El código ha expirado.',
                'message' => 'El código ha expirado.'
            ], 400);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->token, $record->token)) {
            return response()->json([
                'mensaje' => 'El código ingresado no es válido.',
                'message' => 'El código ingresado no es válido.'
            ], 400);
        }

        return response()->json([
            'mensaje' => 'Código verificado correctamente.',
            'message' => 'Código verificado correctamente.'
        ]);
    }

    /**
     * Restablecer contraseña.
     */
    public function resetPassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string|size:6',
            'password' => [
                'required',
                'string',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/'
            ],
        ], [
            'email.required' => 'Ingrese su correo electrónico.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'token.required' => 'El código es requerido.',
            'token.size' => 'El código debe tener exactamente 6 dígitos.',
            'password.required' => 'Ingrese una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => $validator->errors()->first(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'mensaje' => 'El código ingresado no es válido.',
                'message' => 'El código ingresado no es válido.'
            ], 400);
        }

        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(10)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            return response()->json([
                'mensaje' => 'El código ha expirado.',
                'message' => 'El código ha expirado.'
            ], 400);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->token, $record->token)) {
            return response()->json([
                'mensaje' => 'El código ingresado no es válido.',
                'message' => 'El código ingresado no es válido.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'mensaje' => 'No existe una cuenta asociada a este correo.',
                'message' => 'No existe una cuenta asociada a este correo.'
            ], 400);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente. Ya puede iniciar sesión.',
            'message' => 'Contraseña actualizada correctamente. Ya puede iniciar sesión.'
        ]);
    }

    /**
     * Actualizar nombre del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
        ], [
            'name.required' => 'El nombre es requerido.',
            'name.min'      => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'      => 'El nombre no puede superar 255 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => $validator->errors()->first(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();
        $user->name = trim($request->name);
        $user->save();

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente.',
            'datos'   => $user->fresh(),
        ]);
    }

    /**
     * Cambiar contraseña del usuario autenticado.
     */
    public function updatePassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'password_actual'       => 'required|string',
            'password'              => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/'
            ],
            'password_confirmation' => 'required|string',
        ], [
            'password_actual.required'       => 'Ingrese su contraseña actual.',
            'password.required'              => 'Ingrese la nueva contraseña.',
            'password.confirmed'             => 'Las contraseñas no coinciden.',
            'password.min'                   => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.regex'                 => 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.',
            'password_confirmation.required' => 'Confirme la nueva contraseña.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => $validator->errors()->first(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password_actual, $user->password)) {
            return response()->json([
                'mensaje' => 'La contraseña actual no es correcta.',
                'message' => 'La contraseña actual no es correcta.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente.',
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
