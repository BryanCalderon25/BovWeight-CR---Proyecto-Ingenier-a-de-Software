<?php

namespace Database\Factories;

use App\Models\VeterinaryRecord;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VeterinaryRecord>
 */
class VeterinaryRecordFactory extends Factory
{
    protected $model = VeterinaryRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipos = ['observacion', 'tratamiento', 'vacuna', 'cirugia', 'revision'];

        return [
            'animal_id'        => Animal::factory(),
            'veterinario_id'   => User::factory(),
            'fecha_atencion'   => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'tipo'             => fake()->randomElement($tipos),
            'diagnostico'      => fake()->sentence(),
            'tratamiento'      => fake()->optional(0.6)->sentence(),
            'medicamentos'     => fake()->optional(0.5)->word(),
            'dosis'            => fake()->optional(0.5)->numerify('# ml'),
            'proxima_cita'     => fake()->optional(0.4)->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'observaciones'    => fake()->optional(0.4)->sentence(),
            'recomendaciones'  => fake()->optional(0.4)->sentence(),
            'peso_al_momento'  => fake()->optional(0.7)->randomFloat(1, 100, 700),
        ];
    }

    /**
     * Tipo específico: vacunación.
     */
    public function vacuna(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo'         => 'vacuna',
            'diagnostico'  => 'Vacunación preventiva aplicada.',
            'medicamentos' => 'Vacuna triple bovina',
            'dosis'        => '5 ml',
        ]);
    }
}
