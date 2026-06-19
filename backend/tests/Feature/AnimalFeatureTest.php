<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $ganadero;
    protected Farm $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->ganadero = User::factory()->create();
        $this->ganadero->assignRole('ganadero');

        $this->finca = Farm::factory()->create(['user_id' => $this->ganadero->id]);
    }

    // ─────────────────────────────────────────────
    // CREAR ANIMAL
    // ─────────────────────────────────────────────

    /**
     * Un ganadero puede crear un animal en su propia finca.
     */
    public function test_ganadero_puede_crear_animal_en_su_finca(): void
    {
        $payload = [
            'farm_id' => $this->finca->id,
            'arete'   => 'CR-9001',
            'nombre'  => 'Lucero',
            'raza'    => 'Holstein',
            'genero'  => 'Hembra',
            'peso_actual' => 320.5,
        ];

        $response = $this->actingAs($this->ganadero)
            ->postJson('/api/animales', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('datos.arete', 'CR-9001')
            ->assertJsonPath('datos.nombre', 'Lucero');

        $this->assertDatabaseHas('animals', [
            'arete'   => 'CR-9001',
            'farm_id' => $this->finca->id,
        ]);
    }

    /**
     * Un ganadero NO puede crear un animal en una finca ajena.
     */
    public function test_ganadero_no_puede_crear_animal_en_finca_ajena(): void
    {
        $otroGanadero = User::factory()->create();
        $otroGanadero->assignRole('ganadero');
        $fincaAjena = Farm::factory()->create(['user_id' => $otroGanadero->id]);

        $response = $this->actingAs($this->ganadero)
            ->postJson('/api/animales', [
                'farm_id' => $fincaAjena->id,
                'arete'   => 'CR-INTRUSO',
                'genero'  => 'Macho',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Usuario no autenticado recibe 401 al intentar crear un animal.
     */
    public function test_usuario_no_autenticado_no_puede_crear_animal(): void
    {
        $response = $this->postJson('/api/animales', [
            'farm_id' => $this->finca->id,
            'arete'   => 'CR-9002',
            'genero'  => 'Macho',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Validación: arete duplicado retorna 422.
     */
    public function test_crear_animal_con_arete_duplicado_retorna_422(): void
    {
        Animal::factory()->create([
            'farm_id' => $this->finca->id,
            'arete'   => 'CR-DUPLICADO',
        ]);

        $response = $this->actingAs($this->ganadero)
            ->postJson('/api/animales', [
                'farm_id' => $this->finca->id,
                'arete'   => 'CR-DUPLICADO',
                'genero'  => 'Hembra',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['arete']);
    }

    // ─────────────────────────────────────────────
    // LISTAR ANIMALES
    // ─────────────────────────────────────────────

    /**
     * Ganadero puede listar los animales de su finca.
     */
    public function test_ganadero_puede_listar_animales_de_su_finca(): void
    {
        Animal::factory()->count(3)->create(['farm_id' => $this->finca->id]);

        $response = $this->actingAs($this->ganadero)
            ->getJson("/api/fincas/{$this->finca->id}/animales");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'datos');
    }

    /**
     * Ganadero NO puede listar animales de una finca ajena.
     */
    public function test_ganadero_no_puede_listar_animales_de_finca_ajena(): void
    {
        $otro  = User::factory()->create();
        $finca = Farm::factory()->create(['user_id' => $otro->id]);

        $response = $this->actingAs($this->ganadero)
            ->getJson("/api/fincas/{$finca->id}/animales");

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────
    // VER ANIMAL
    // ─────────────────────────────────────────────

    /**
     * Ganadero puede ver el detalle de un animal de su finca.
     */
    public function test_ganadero_puede_ver_detalle_de_su_animal(): void
    {
        $animal = Animal::factory()->create(['farm_id' => $this->finca->id]);

        $response = $this->actingAs($this->ganadero)
            ->getJson("/api/animales/{$animal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('datos.id', $animal->id);
    }

    // ─────────────────────────────────────────────
    // ACTUALIZAR ANIMAL
    // ─────────────────────────────────────────────

    /**
     * Ganadero puede actualizar un animal de su finca.
     */
    public function test_ganadero_puede_actualizar_su_animal(): void
    {
        $animal = Animal::factory()->create([
            'farm_id' => $this->finca->id,
            'nombre'  => 'Nombre Viejo',
        ]);

        $response = $this->actingAs($this->ganadero)
            ->putJson("/api/animales/{$animal->id}", [
                'nombre' => 'Nombre Nuevo',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('datos.nombre', 'Nombre Nuevo');

        $this->assertDatabaseHas('animals', [
            'id'     => $animal->id,
            'nombre' => 'Nombre Nuevo',
        ]);
    }

    /**
     * Ganadero NO puede actualizar un animal de finca ajena.
     */
    public function test_ganadero_no_puede_actualizar_animal_ajeno(): void
    {
        $otro      = User::factory()->create();
        $fincaAjena = Farm::factory()->create(['user_id' => $otro->id]);
        $animal    = Animal::factory()->create(['farm_id' => $fincaAjena->id]);

        $response = $this->actingAs($this->ganadero)
            ->putJson("/api/animales/{$animal->id}", [
                'nombre' => 'Hack',
            ]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────
    // ELIMINAR ANIMAL
    // ─────────────────────────────────────────────

    /**
     * Ganadero puede eliminar (soft delete) un animal de su finca.
     */
    public function test_ganadero_puede_eliminar_su_animal(): void
    {
        $animal   = Animal::factory()->create(['farm_id' => $this->finca->id]);
        $animalId = $animal->id;

        $response = $this->actingAs($this->ganadero)
            ->deleteJson("/api/animales/{$animalId}");

        $response->assertStatus(200);

        // Soft deleted: no aparece en query normal
        $this->assertNull(Animal::find($animalId));
        $this->assertNotNull(Animal::withTrashed()->find($animalId));
    }

    /**
     * Ganadero NO puede eliminar un animal de finca ajena.
     */
    public function test_ganadero_no_puede_eliminar_animal_ajeno(): void
    {
        $otro      = User::factory()->create();
        $fincaAjena = Farm::factory()->create(['user_id' => $otro->id]);
        $animal    = Animal::factory()->create(['farm_id' => $fincaAjena->id]);

        $response = $this->actingAs($this->ganadero)
            ->deleteJson("/api/animales/{$animal->id}");

        $response->assertStatus(403);
    }
}
