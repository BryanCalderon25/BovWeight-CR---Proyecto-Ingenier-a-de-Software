<?php

namespace Tests\Unit;

use App\Models\Farm;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que el modelo tiene los campos fillable correctos.
     */
    public function test_farm_tiene_campos_fillable_correctos(): void
    {
        $farm = new Farm();

        $camposEsperados = ['user_id', 'nombre', 'ubicacion', 'descripcion', 'area_hectareas'];

        foreach ($camposEsperados as $campo) {
            $this->assertContains(
                $campo,
                $farm->getFillable(),
                "El campo '$campo' debería estar en fillable"
            );
        }
    }

    /**
     * Verifica que Farm usa SoftDeletes.
     */
    public function test_farm_usa_soft_deletes(): void
    {
        $this->assertContains(
            'Illuminate\Database\Eloquent\SoftDeletes',
            class_uses_recursive(Farm::class)
        );
    }

    /**
     * Verifica la relación belongsTo con User (dueño).
     */
    public function test_farm_pertenece_a_un_usuario(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);

        $this->assertInstanceOf(User::class, $finca->user);
        $this->assertEquals($ganadero->id, $finca->user->id);
    }

    /**
     * Verifica la relación hasMany con Animal.
     */
    public function test_farm_tiene_muchos_animales(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);

        Animal::factory()->count(5)->create(['farm_id' => $finca->id]);

        $this->assertCount(5, $finca->animals);
        $this->assertInstanceOf(Animal::class, $finca->animals->first());
    }

    /**
     * Verifica la relación belongsToMany con usuarios compartidos (farm_user).
     */
    public function test_farm_tiene_usuarios_compartidos(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero    = User::factory()->create();
        $veterinario = User::factory()->create();
        $finca       = Farm::factory()->create(['user_id' => $ganadero->id]);

        // Asignar acceso compartido
        $finca->sharedUsers()->attach($veterinario->id);

        $this->assertCount(1, $finca->sharedUsers);
        $this->assertEquals($veterinario->id, $finca->sharedUsers->first()->id);
    }

    /**
     * Verifica que eliminar una finca hace soft delete.
     */
    public function test_eliminar_farm_hace_soft_delete(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $ganadero = User::factory()->create();
        $finca    = Farm::factory()->create(['user_id' => $ganadero->id]);
        $fincaId  = $finca->id;

        $finca->delete();

        $this->assertNull(Farm::find($fincaId));
        $this->assertNotNull(Farm::withTrashed()->find($fincaId));
        $this->assertNotNull(Farm::withTrashed()->find($fincaId)->deleted_at);
    }
}
