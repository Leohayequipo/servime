<?php

namespace Database\Factories;

use App\Models\Comercio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comercio_id' => Comercio::factory(),
            'nombre' => fake()->words(2, true),
            'formato' => 'pdf',
            'procesado' => true,
            'activo' => false,
        ];
    }

    /**
     * Menú activo.
     */
    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => true,
        ]);
    }
}
