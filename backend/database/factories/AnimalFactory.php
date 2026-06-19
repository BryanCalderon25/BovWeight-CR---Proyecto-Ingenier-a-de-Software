<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
{
    protected $model = Animal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $razas = ['Holstein', 'Brahman', 'Angus', 'Hereford', 'Charolais', 'Simmental', 'Limousin'];

        return [
            'farm_id'          => Farm::factory(),
            'arete'            => 'CR-' . fake()->unique()->numerify('####'),
            'nombre'           => fake()->optional(0.7)->firstName(),
            'raza'             => fake()->randomElement($razas),
            'fecha_nacimiento' => fake()->optional(0.8)->dateTimeBetween('-5 years', '-6 months')?->format('Y-m-d'),
            'genero'           => fake()->randomElement(['Macho', 'Hembra']),
            'proposito'        => fake()->randomElement(['Lechero', 'Cárnico', 'Doble propósito']),
            'estado'           => 'activo',
            'peso_actual'      => fake()->randomFloat(1, 100, 700),
            'notas'            => fake()->optional(0.4)->sentence(),
        ];
    }

    /**
     * Estado: hembra lechera.
     */
    public function hembra(): static
    {
        return $this->state(fn (array $attributes) => [
            'genero'   => 'Hembra',
            'proposito' => 'Lechero',
        ]);
    }

    /**
     * Estado: macho cárnico.
     */
    public function macho(): static
    {
        return $this->state(fn (array $attributes) => [
            'genero'   => 'Macho',
            'proposito' => 'Cárnico',
        ]);
    }
}
