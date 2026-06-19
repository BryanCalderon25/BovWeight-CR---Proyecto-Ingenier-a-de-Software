<?php

namespace Database\Factories;

use App\Models\WeightRecord;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeightRecord>
 */
class WeightRecordFactory extends Factory
{
    protected $model = WeightRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id'            => Animal::factory(),
            'peso_estimado'        => fake()->randomFloat(1, 100, 700),
            'fecha_pesaje'         => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'animal_image_id'      => null,
            'datos_modelo'         => [
                'confianza'   => fake()->randomFloat(2, 0.70, 0.99),
                'modelo'      => 'YOLOv8n-seg',
            ],
            'estado_sincronizacion' => fake()->randomElement(['sincronizado', 'pendiente']),
            'notas'                => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Estado: registro ya sincronizado.
     */
    public function sincronizado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_sincronizacion' => 'sincronizado',
        ]);
    }
}
