<?php

namespace Database\Factories;

use App\Models\Comercio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    public function definition(): array
    {
        $event = fake()->randomElement(['page_view', 'menu_choice', 'promo_click', 'dish_want']);

        return [
            'comercio_id' => Comercio::factory(),
            'session_id' => 'session_'.fake()->uuid(),
            'event' => $event,
            'data' => match ($event) {
                'menu_choice' => ['choice' => fake()->randomElement(['ia', 'classic'])],
                'promo_click' => ['promo' => fake()->randomElement(['2x1 Pizzas', 'Happy hour', 'Postre gratis'])],
                'dish_want' => ['nombre' => fake()->words(2, true), 'price' => '$'.fake()->numberBetween(2, 20).'000'],
                default => ['view' => fake()->randomElement(['choose', 'chat', 'classic'])],
            },
            'user_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
