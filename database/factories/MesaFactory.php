<?php

namespace Database\Factories;

use App\Models\Comercio;
use App\Models\Mesa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mesa>
 */
class MesaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'comercio_id' => Comercio::factory(),
            'numero' => fake()->unique()->numberBetween(1, 300),
            'token' => Mesa::generarToken(),
            'activa' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn () => ['activa' => false]);
    }
}
