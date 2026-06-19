<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PruebaAutenticacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Probar recuperar contraseña con un correo existente.
     */
    public function test_forgot_password_with_existing_email()
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'juan.perez@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Si el correo está registrado, recibirá un código para restablecer su contraseña.'
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'juan.perez@example.com',
        ]);

        Mail::assertSentCount(1);
        Mail::assertSent(\App\Mail\CorreoRecuperacion::class);
    }

    /**
     * Probar recuperar contraseña con un correo no existente no revela su existencia.
     */
    public function test_forgot_password_with_non_existing_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Si el correo está registrado, recibirá un código para restablecer su contraseña.'
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'nonexistent@example.com',
        ]);

        Mail::assertSentCount(0);
    }

    /**
     * Probar restablecer contraseña con un token válido.
     */
    public function test_reset_password_with_valid_token()
    {
        $user = User::create([
            'name' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'juan.perez@example.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'juan.perez@example.com',
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Contraseña actualizada correctamente. Ya puede iniciar sesión.'
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'juan.perez@example.com',
        ]);

        // El login con la nueva contraseña debe ser exitoso
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'juan.perez@example.com',
            'password' => 'NewPassword123!',
        ]);
        $loginResponse->assertStatus(200);
    }

    /**
     * Probar que el restablecimiento de contraseña falla con un token inválido.
     */
    public function test_reset_password_fails_with_invalid_token()
    {
        $user = User::create([
            'name' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'juan.perez@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'juan.perez@example.com',
            'token' => '000000',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'El código ingresado no es válido.'
        ]);
    }

    /**
     * Probar que el restablecimiento de contraseña falla con una contraseña insegura.
     */
    public function test_reset_password_fails_with_insecure_password()
    {
        $user = User::create([
            'name' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'juan.perez@example.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Contraseña insegura (muy corta o sin mayúsculas/números)
        $response = $this->postJson('/api/reset-password', [
            'email' => 'juan.perez@example.com',
            'token' => $token,
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.'
        ]);
    }
}
