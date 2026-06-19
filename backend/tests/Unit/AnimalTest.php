<?php

namespace Tests\Unit;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\User;
use App\Models\WeightRecord;
use App\Models\VeterinaryRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que el modelo tiene los campos fillable correctos.
     */
    public function test_animal_tiene_campos_fillable_correctos(): void
    {
        $animal = new Animal();

        $camposEsperados = [
            'farm_id', 'arete', 'nombre', 'raza',
            'fecha_nacimiento', 'genero', 'proposito',
            'estado', 'peso_actual', 'notas',
        ];

        foreach ($camposEsperados as $campo) {
            $this->assertContains(
                $campo,
                $animal->getFillable(),
                "El campo '$campo' debería estar en fillable"
            );
        }
    }

    /**
     * Verifica que Animal usa SoftDeletes.
     */
    public function test_animal_usa_soft_deletes(): void
    {
        $this->assertContains(
            'Illuminate\Database\Eloquent\SoftDeletes',
            class_uses_recursive(Animal::class)
        );
    }

    /**
     * Verifica la relación belongsTo con Farm.
     */
    public function test_animal_pertenece_a_una_finca(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal = Animal::factory()->create(['farm_id' => $finca->id]);

        $this->assertInstanceOf(Farm::class, $animal->farm);
        $this->assertEquals($finca->id, $animal->farm->id);
    }

    /**
     * Verifica la relación hasMany con WeightRecord.
     */
    public function test_animal_tiene_muchos_registros_de_pesaje(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal   = Animal::factory()->create(['farm_id' => $finca->id]);

        WeightRecord::factory()->count(3)->create(['animal_id' => $animal->id]);

        $this->assertCount(3, $animal->weightRecords);
        $this->assertInstanceOf(WeightRecord::class, $animal->weightRecords->first());
    }

    /**
     * Verifica la relación hasMany con VeterinaryRecord.
     */
    public function test_animal_tiene_muchos_registros_veterinarios(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero     = User::factory()->create();
        $veterinario  = User::factory()->create();
        $finca        = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal       = Animal::factory()->create(['farm_id' => $finca->id]);

        VeterinaryRecord::factory()->count(2)->create([
            'animal_id'      => $animal->id,
            'veterinario_id' => $veterinario->id,
        ]);

        $this->assertCount(2, $animal->veterinaryRecords);
        $this->assertInstanceOf(VeterinaryRecord::class, $animal->veterinaryRecords->first());
    }

    /**
     * Verifica que el soft delete no elimina físicamente el registro.
     */
    public function test_eliminar_animal_hace_soft_delete(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $animal   = Animal::factory()->create(['farm_id' => $finca->id]);

        $animalId = $animal->id;
        $animal->delete();

        // Ya no aparece en consultas normales
        $this->assertNull(Animal::find($animalId));

        // Pero sí existe en la BD con deleted_at
        $this->assertNotNull(Animal::withTrashed()->find($animalId));
        $this->assertNotNull(Animal::withTrashed()->find($animalId)->deleted_at);
    }
}
