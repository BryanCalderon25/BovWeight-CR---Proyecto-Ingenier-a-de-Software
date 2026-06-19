<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /**
     * hasSharedAccess: retorna true cuando invited_farm_id coincide y NO ha vencido.
     */
    public function test_has_shared_access_retorna_true_con_farm_id_valido(): void
    {
        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);

        $invitado = User::factory()->create([
            'invited_farm_id' => $finca->id,
            'guest_expires_at' => now()->addDays(7),
        ]);

        $this->assertTrue($invitado->hasSharedAccess($finca->id));
    }

    /**
     * hasSharedAccess: retorna false cuando invited_farm_id coincide pero YA venció.
     */
    public function test_has_shared_access_retorna_false_cuando_expiro(): void
    {
        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);

        $invitadoVencido = User::factory()->create([
            'invited_farm_id'  => $finca->id,
            'guest_expires_at' => now()->subDay(), // ayer
        ]);

        $this->assertFalse($invitadoVencido->hasSharedAccess($finca->id));
    }

    /**
     * hasSharedAccess: retorna false si el farm_id no coincide.
     */
    public function test_has_shared_access_retorna_false_con_farm_id_diferente(): void
    {
        $ganadero  = User::factory()->create();
        $finca1    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $finca2    = Farm::factory()->create(['user_id' => $ganadero->id]);

        $invitado = User::factory()->create([
            'invited_farm_id'  => $finca1->id,
            'guest_expires_at' => now()->addDays(7),
        ]);

        // Tiene acceso a finca1, no a finca2
        $this->assertTrue($invitado->hasSharedAccess($finca1->id));
        $this->assertFalse($invitado->hasSharedAccess($finca2->id));
    }

    /**
     * getGuestRoleAttribute: retorna 'veterinario' cuando el usuario tiene ese rol.
     */
    public function test_guest_role_retorna_veterinario_cuando_tiene_ese_rol(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('veterinario');

        $this->assertEquals('veterinario', $usuario->guest_role);
    }

    /**
     * getGuestRoleAttribute: retorna null cuando no tiene rol especial de invitado.
     */
    public function test_guest_role_retorna_null_sin_rol_especial(): void
    {
        $usuario = User::factory()->create();
        // Sin rol asignado → guest_role debe ser null

        $this->assertNull($usuario->guest_role);
    }

    /**
     * hasSharedAccess: veterinario con acceso many-to-many (farm_user) retorna true.
     */
    public function test_veterinario_con_shared_farm_tiene_acceso(): void
    {
        $ganadero    = User::factory()->create();
        $veterinario = User::factory()->create();
        $finca       = Farm::factory()->create(['user_id' => $ganadero->id]);

        $veterinario->assignRole('veterinario');
        // Asociar vía many-to-many
        $finca->sharedUsers()->attach($veterinario->id);

        $this->assertTrue($veterinario->hasSharedAccess($finca->id));
    }

    /**
     * Un usuario puede tener múltiples fincas propias.
     */
    public function test_usuario_puede_tener_multiples_fincas(): void
    {
        $ganadero = User::factory()->create();
        Farm::factory()->count(3)->create(['user_id' => $ganadero->id]);

        $this->assertCount(3, $ganadero->fresh()->farms);
    }
}
