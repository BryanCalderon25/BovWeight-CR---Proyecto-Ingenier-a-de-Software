<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Farm;
use App\Models\Animal;
use App\Models\VeterinaryRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VeterinaryRecordTest extends TestCase
{
    use RefreshDatabase;

    protected $ganadero;
    protected $veterinario;
    protected $finca;
    protected $animal;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a ganadero
        $this->ganadero = User::factory()->create();
        $this->ganadero->assignRole('ganadero');

        // Create a farm owned by ganadero
        $this->finca = Farm::create([
            'user_id' => $this->ganadero->id,
            'nombre' => 'Finca Central',
            'ubicacion' => 'San José',
            'area_hectareas' => 10.5
        ]);

        // Create an animal in that farm
        $this->animal = Animal::create([
            'farm_id' => $this->finca->id,
            'arete' => 'CR-1002',
            'nombre' => 'Lola',
            'raza' => 'Holstein',
            'fecha_nacimiento' => '2023-01-10',
            'genero' => 'Hembra',
            'proposito' => 'Lechero',
            'peso_actual' => 420.0
        ]);

        // Create a veterinarian user
        $this->veterinario = User::factory()->create([
            'invited_farm_id' => $this->finca->id,
            'guest_expires_at' => now()->addDays(7)
        ]);
        $this->veterinario->assignRole('veterinario');
        $this->veterinario->assignRole('invitado');
    }

    public function test_veterinario_can_list_veterinary_records()
    {
        // Create a record
        VeterinaryRecord::create([
            'animal_id' => $this->animal->id,
            'veterinario_id' => $this->veterinario->id,
            'fecha_atencion' => '2026-06-10',
            'tipo' => 'observacion',
            'diagnostico' => 'Salud general óptima.'
        ]);

        $response = $this->actingAs($this->veterinario)
            ->getJson("/api/animales/{$this->animal->id}/veterinario");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'mensaje',
                'datos' => [
                    '*' => [
                        'id',
                        'animal_id',
                        'veterinario_id',
                        'fecha_atencion',
                        'tipo',
                        'diagnostico',
                        'veterinario'
                    ]
                ]
            ]);
    }

    public function test_veterinario_can_create_veterinary_record()
    {
        $payload = [
            'tipo' => 'tratamiento',
            'fecha_atencion' => '2026-06-10',
            'diagnostico' => 'Infección leve.',
            'tratamiento' => 'Aplicar antibiótico.',
            'medicamentos' => 'Penicilina',
            'dosis' => '5ml',
            'peso_al_momento' => 425
        ];

        $response = $this->actingAs($this->veterinario)
            ->postJson("/api/animales/{$this->animal->id}/veterinario", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('datos.tipo', 'tratamiento')
            ->assertJsonPath('datos.diagnostico', 'Infección leve.');

        $this->assertDatabaseHas('veterinary_records', [
            'animal_id' => $this->animal->id,
            'tipo' => 'tratamiento',
            'diagnostico' => 'Infección leve.'
        ]);
    }

    public function test_veterinario_can_update_own_veterinary_record()
    {
        $record = VeterinaryRecord::create([
            'animal_id' => $this->animal->id,
            'veterinario_id' => $this->veterinario->id,
            'fecha_atencion' => '2026-06-10',
            'tipo' => 'observacion',
            'diagnostico' => 'Salud general óptima.'
        ]);

        $response = $this->actingAs($this->veterinario)
            ->putJson("/api/veterinario/{$record->id}", [
                'diagnostico' => 'Actualizado: Salud general excelente.'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('datos.diagnostico', 'Actualizado: Salud general excelente.');
    }

    public function test_unauthorized_user_cannot_create_veterinary_record()
    {
        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($otroUsuario)
            ->postJson("/api/animales/{$this->animal->id}/veterinario", [
                'tipo' => 'observacion',
                'fecha_atencion' => '2026-06-10',
                'diagnostico' => 'Intento no autorizado'
            ]);

        $response->assertStatus(403);
    }

    public function test_can_generate_veterinary_report_pdf()
    {
        // Add a record so report has data
        VeterinaryRecord::create([
            'animal_id' => $this->animal->id,
            'veterinario_id' => $this->veterinario->id,
            'fecha_atencion' => '2026-06-10',
            'tipo' => 'observacion',
            'diagnostico' => 'Vacunado contra fiebre aftosa.'
        ]);

        $response = $this->actingAs($this->veterinario)
            ->get("/api/animales/{$this->animal->id}/reporte-veterinario");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_ganadero_cannot_create_veterinary_record()
    {
        $response = $this->actingAs($this->ganadero)
            ->postJson("/api/animales/{$this->animal->id}/veterinario", [
                'tipo' => 'observacion',
                'fecha_atencion' => '2026-06-10',
                'diagnostico' => 'Intento por parte del Ganadero'
            ]);

        $response->assertStatus(403);
    }

    public function test_ganadero_cannot_update_veterinary_record()
    {
        $record = VeterinaryRecord::create([
            'animal_id' => $this->animal->id,
            'veterinario_id' => $this->veterinario->id,
            'fecha_atencion' => '2026-06-10',
            'tipo' => 'observacion',
            'diagnostico' => 'Original'
        ]);

        $response = $this->actingAs($this->ganadero)
            ->putJson("/api/veterinario/{$record->id}", [
                'diagnostico' => 'Intento de edicion por Ganadero'
            ]);

        $response->assertStatus(403);
    }

    public function test_ganadero_cannot_delete_veterinary_record()
    {
        $record = VeterinaryRecord::create([
            'animal_id' => $this->animal->id,
            'veterinario_id' => $this->veterinario->id,
            'fecha_atencion' => '2026-06-10',
            'tipo' => 'observacion',
            'diagnostico' => 'Original'
        ]);

        $response = $this->actingAs($this->ganadero)
            ->deleteJson("/api/veterinario/{$record->id}");

        $response->assertStatus(403);
    }

    public function test_veterinario_can_view_assigned_farm()
    {
        $response = $this->actingAs($this->veterinario)
            ->getJson("/api/fincas/{$this->finca->id}");

        $response->assertStatus(200);
    }

    public function test_veterinario_can_list_animals_of_assigned_farm()
    {
        $response = $this->actingAs($this->veterinario)
            ->getJson("/api/fincas/{$this->finca->id}/animales");

        $response->assertStatus(200);
    }
}

