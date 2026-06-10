<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test a user can register successfully.
     */
    public function test_user_can_register_successfully()
    {
        $response = $this->postJson('/api/registro', [
            'name' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'mensaje',
            'datos' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
            'token_acceso',
            'tipo_token',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@example.com',
            'name' => 'Juan Perez',
        ]);
    }

    /**
     * Test registration validation fails with short password or mismatch.
     */
    public function test_registration_validation_fails()
    {
        // Missing name and password too short
        $response = $this->postJson('/api/registro', [
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test a user can login successfully.
     */
    public function test_user_can_login_successfully()
    {
        $user = User::create([
            'name' => 'Ganadero Prueba',
            'email' => 'ganadero@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ganadero@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'mensaje',
            'datos',
            'token_acceso',
            'tipo_token',
        ]);
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::create([
            'name' => 'Ganadero Prueba',
            'email' => 'ganadero@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ganadero@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment([
            'mensaje' => 'La contraseña es incorrecta para este usuario.',
        ]);
    }

    /**
     * Test getting user profile.
     */
    public function test_can_get_user_profile()
    {
        $user = User::create([
            'name' => 'Ganadero Prueba',
            'email' => 'ganadero@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        // Test normal profile endpoint
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/perfil');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'mensaje' => 'Perfil obtenido exitosamente',
        ]);
        $response->assertJsonPath('datos.email', 'ganadero@example.com');

        // Test alias user/profile endpoint
        $responseAlias = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/user/profile');

        $responseAlias->assertStatus(200);
        $responseAlias->assertJsonFragment([
            'mensaje' => 'Perfil obtenido exitosamente',
        ]);
        $responseAlias->assertJsonPath('datos.email', 'ganadero@example.com');
    }

    /**
     * Test logout revokes token.
     */
    public function test_user_can_logout()
    {
        $user = User::create([
            'name' => 'Ganadero Prueba',
            'email' => 'ganadero@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'mensaje' => 'Sesión cerrada exitosamente y tokens revocados',
        ]);
    }
}
