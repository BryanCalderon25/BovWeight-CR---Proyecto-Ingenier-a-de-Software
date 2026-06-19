<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farm>
 */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'nombre'         => fake()->company() . ' Farm',
            'ubicacion'      => fake()->city() . ', Costa Rica',
            'descripcion'    => fake()->sentence(),
            'area_hectareas' => fake()->randomFloat(2, 1, 500),
        ];
    }
}
