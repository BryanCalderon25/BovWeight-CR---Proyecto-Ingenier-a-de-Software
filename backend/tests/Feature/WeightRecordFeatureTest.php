<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\User;
use App\Models\WeightRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightRecordFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $ganadero;
    protected Farm $finca;
    protected Animal $animal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->ganadero = User::factory()->create();
        $this->ganadero->assignRole('ganadero');

        $this->finca  = Farm::factory()->create(['user_id' => $this->ganadero->id]);
        $this->animal = Animal::factory()->create(['farm_id' => $this->finca->id]);
    }

    // ─────────────────────────────────────────────
    // LISTAR PESAJES
    // ─────────────────────────────────────────────

    /**
     * Ganadero puede listar todos sus pesajes (endpoint general).
     */
    public function test_ganadero_puede_listar_todos_sus_pesajes(): void
    {
        WeightRecord::factory()->count(3)->create([
            'animal_id' => $this->animal->id,
        ]);

        $response = $this->actingAs($this->ganadero)
            ->getJson('/api/pesajes');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('datos') ?? []));
    }

    /**
     * Ganadero puede listar los pesajes de un animal específico.
     */
    public function test_ganadero_puede_listar_pesajes_de_su_animal(): void
    {
        WeightRecord::factory()->count(4)->create([
            'animal_id' => $this->animal->id,
        ]);

        $response = $this->actingAs($this->ganadero)
            ->getJson("/api/animales/{$this->animal->id}/pesajes");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'datos');
    }

    /**
     * Usuario no autenticado no puede acceder a los pesajes.
     */
    public function test_usuario_no_autenticado_no_puede_listar_pesajes(): void
    {
        $response = $this->getJson('/api/pesajes');
        $response->assertStatus(401);
    }

    /**
     * Ganadero no puede ver pesajes de animal en finca ajena.
     */
    public function test_ganadero_no_puede_ver_pesajes_de_animal_ajeno(): void
    {
        $otroGanadero = User::factory()->create();
        $otroGanadero->assignRole('ganadero');
        $fincaAjena   = Farm::factory()->create(['user_id' => $otroGanadero->id]);
        $animalAjeno  = Animal::factory()->create(['farm_id' => $fincaAjena->id]);

        WeightRecord::factory()->count(2)->create([
            'animal_id' => $animalAjeno->id,
        ]);

        $response = $this->actingAs($this->ganadero)
            ->getJson("/api/animales/{$animalAjeno->id}/pesajes");

        // Debe rechazar el acceso (403) o devolver lista vacía (200 con datos=[])
        $this->assertTrue(
            in_array($response->status(), [200, 403]),
            "El status debería ser 200 (datos vacíos) o 403 (prohibido)"
        );

        if ($response->status() === 200) {
            $this->assertEmpty($response->json('datos'));
        }
    }

    // ─────────────────────────────────────────────
    // ESTRUCTURA DE RESPUESTA
    // ─────────────────────────────────────────────

    /**
     * La respuesta de pesajes tiene la estructura correcta.
     */
    public function test_respuesta_pesajes_tiene_estructura_correcta(): void
    {
        WeightRecord::factory()->create([
            'animal_id'      => $this->animal->id,
            'peso_estimado'  => 300.0,
            'fecha_pesaje'   => '2026-06-01',
            'datos_modelo'   => ['confianza' => 0.92],
            'estado_sincronizacion' => 'sincronizado',
        ]);

        $response = $this->actingAs($this->ganadero)
            ->getJson("/api/animales/{$this->animal->id}/pesajes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'mensaje',
                'datos' => [
                    '*' => [
                        'id',
                        'animal_id',
                        'peso_estimado',
                        'fecha_pesaje',
                        'estado_sincronizacion',
                    ]
                ]
            ]);
    }

    // ─────────────────────────────────────────────
    // SINCRONIZACIÓN OFFLINE
    // ─────────────────────────────────────────────

    /**
     * El endpoint de sincronización offline existe y requiere autenticación.
     */
    public function test_endpoint_sincronizacion_requiere_autenticacion(): void
    {
        $response = $this->postJson('/api/sincronizacion/pesajes', []);
        $response->assertStatus(401);
    }

    /**
     * Ganadero puede acceder al endpoint de sincronización offline.
     */
    public function test_ganadero_puede_acceder_a_sincronizacion_offline(): void
    {
        $payload = [
            'registros' => [
                [
                    'animal_id'             => $this->animal->id,
                    'peso_estimado'         => 310.0,
                    'fecha_pesaje'          => '2026-06-10',
                    'estado_sincronizacion' => 'pendiente',
                ]
            ]
        ];

        $response = $this->actingAs($this->ganadero)
            ->postJson('/api/sincronizacion/pesajes', $payload);

        // Acepta 200 (éxito) o 422 (validación) — solo no debe ser 401 o 403
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }
}
