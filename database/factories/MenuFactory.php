<?php

namespace Database\Factories;

use App\Models\Comercio;
use App\Models\Menu;
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
            // Va explícito porque la columna tiene default 'procesando' (ver la migración
            // que la agrega): sin esto el factory devolvía menús procesados y "procesando"
            // a la vez, un estado que no existe, y las pantallas que miran `estado` veían
            // un menú a medio hacer.
            'estado' => Menu::ESTADO_LISTO,
            'activo' => false,
        ];
    }

    /** La IA todavía lo está leyendo. */
    public function procesando(): static
    {
        return $this->state(fn (array $attributes) => [
            'procesado' => false,
            'estado' => Menu::ESTADO_PROCESANDO,
        ]);
    }

    /** El procesamiento falló: el archivo está, el contenido no. */
    public function conError(): static
    {
        return $this->state(fn (array $attributes) => [
            'procesado' => false,
            'estado' => Menu::ESTADO_ERROR,
        ]);
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
