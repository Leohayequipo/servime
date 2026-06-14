<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comercio>
 */
class ComercioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.fake()->unique()->numberBetween(1, 99999),
            'descripcion' => fake()->sentence(),
            'direccion' => fake()->address(),
            'horario' => 'Lun a Vie 9 a 18',
        ];
    }
}
