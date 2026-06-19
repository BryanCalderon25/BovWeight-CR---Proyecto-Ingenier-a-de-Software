<?php

namespace Tests\Unit;

use App\Models\WeightRecord;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica los campos fillable del modelo.
     */
    public function test_weight_record_tiene_campos_fillable_correctos(): void
    {
        $record = new WeightRecord();

        $camposEsperados = [
            'animal_id', 'peso_estimado', 'fecha_pesaje',
            'animal_image_id', 'datos_modelo', 'estado_sincronizacion', 'notas',
        ];

        foreach ($camposEsperados as $campo) {
            $this->assertContains(
                $campo,
                $record->getFillable(),
                "El campo '$campo' debería estar en fillable"
            );
        }
    }

    /**
     * Verifica que 'datos_modelo' se castea a array.
     */
    public function test_datos_modelo_se_castea_a_array(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal   = Animal::factory()->create(['farm_id' => $finca->id]);

        $record = WeightRecord::create([
            'animal_id'            => $animal->id,
            'peso_estimado'        => 350.5,
            'fecha_pesaje'         => now()->format('Y-m-d'),
            'datos_modelo'         => ['confianza' => 0.95, 'modelo' => 'YOLOv8n-seg'],
            'estado_sincronizacion' => 'sincronizado',
        ]);

        $fresh = WeightRecord::find($record->id);
        $this->assertIsArray($fresh->datos_modelo);
        $this->assertEquals(0.95, $fresh->datos_modelo['confianza']);
    }

    /**
     * Verifica que 'fecha_pesaje' se castea a Carbon (date).
     */
    public function test_fecha_pesaje_se_castea_a_fecha(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal   = Animal::factory()->create(['farm_id' => $finca->id]);

        $record = WeightRecord::create([
            'animal_id'            => $animal->id,
            'peso_estimado'        => 250.0,
            'fecha_pesaje'         => '2026-01-15',
            'estado_sincronizacion' => 'sincronizado',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $record->fresh()->fecha_pesaje);
        $this->assertEquals('2026-01-15', $record->fresh()->fecha_pesaje->format('Y-m-d'));
    }

    /**
     * Verifica la relación belongsTo con Animal.
     */
    public function test_weight_record_pertenece_a_un_animal(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal   = Animal::factory()->create(['farm_id' => $finca->id]);
        $record   = WeightRecord::factory()->create(['animal_id' => $animal->id]);

        $this->assertInstanceOf(Animal::class, $record->animal);
        $this->assertEquals($animal->id, $record->animal->id);
    }

    /**
     * Verifica que se pueden crear múltiples pesajes para el mismo animal.
     */
    public function test_animal_puede_tener_multiples_pesajes(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal   = Animal::factory()->create(['farm_id' => $finca->id]);

        WeightRecord::factory()->count(4)->create(['animal_id' => $animal->id]);

        $this->assertCount(4, $animal->fresh()->weightRecords);
    }
}
