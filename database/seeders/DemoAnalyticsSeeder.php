<?php

namespace Database\Seeders;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\TrackingEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de demo para el panel de estadísticas (local).
 * Uso: php artisan db:seed --class=DemoAnalyticsSeeder
 * NO corre en producción.
 */
class DemoAnalyticsSeeder extends Seeder
{
    private array $preguntas = [
        '¿Tienen opciones sin TACC?',
        '¿Cuál es el horario?',
        '¿Hacen envíos a domicilio?',
        'Quiero algo vegetariano',
        '¿Cuánto cuesta la milanesa napolitana?',
        '¿Tienen postres?',
        'Recomendame un plato',
        '¿Aceptan tarjeta?',
        '¿Hay menú para celíacos?',
        '¿Tienen pizza muzzarella?',
        '¿Cuál es la promo del día?',
        '¿Tienen cerveza artesanal?',
        '¿Qué entradas hay?',
        '¿Tienen opciones veganas?',
    ];

    private array $promos = ['2x1 Pizzas', 'Happy hour', 'Postre de regalo', 'Combo familiar'];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoAnalyticsSeeder no corre en producción.');

            return;
        }

        $comercios = Comercio::all();
        if ($comercios->isEmpty()) {
            $comercios = collect([Comercio::factory()->create()]);
        }

        foreach ($comercios as $comercio) {
            $this->seedComercio($comercio, 120);
        }

        $this->command?->info('Datos de demo generados para '.$comercios->count().' comercio(s).');
    }

    private function seedComercio(Comercio $comercio, int $sesiones): void
    {
        for ($i = 0; $i < $sesiones; $i++) {
            $when = $this->randomMoment();
            $sessionId = 'demo_'.$comercio->id.'_'.$i.'_'.uniqid();
            $eligeIA = fake()->boolean(62); // 62% elige IA

            $this->event($comercio, $sessionId, 'page_view', ['view' => 'choose'], $when);
            $this->event($comercio, $sessionId, 'menu_choice', ['choice' => $eligeIA ? 'ia' : 'classic'], $when->copy()->addSeconds(5));

            if ($eligeIA) {
                $this->event($comercio, $sessionId, 'page_view', ['view' => 'chat'], $when->copy()->addSeconds(7));

                // Conversaciones del chatbot (alimentan palabras, tiempos, IA vs fallback)
                $n = fake()->numberBetween(1, 4);
                for ($m = 0; $m < $n; $m++) {
                    $usaIA = fake()->boolean(85);
                    ChatConversation::create([
                        'comercio_id' => $comercio->id,
                        'session_id' => $sessionId,
                        'user_message' => fake()->randomElement($this->preguntas),
                        'bot_response' => 'Respuesta de demo.',
                        'used_openai' => $usaIA,
                        'response_time_ms' => $usaIA ? fake()->numberBetween(900, 4200) : fake()->numberBetween(20, 120),
                        'user_ip' => fake()->ipv4(),
                        'user_agent' => fake()->userAgent(),
                        'created_at' => $when->copy()->addSeconds(10 + $m * 25),
                        'updated_at' => $when->copy()->addSeconds(10 + $m * 25),
                    ]);
                }
            } else {
                $this->event($comercio, $sessionId, 'page_view', ['view' => 'classic'], $when->copy()->addSeconds(7));
            }

            // Interacciones de contenido
            if (fake()->boolean(35)) {
                $this->event($comercio, $sessionId, 'promo_click', ['promo' => fake()->randomElement($this->promos)], $when->copy()->addSeconds(40));
            }
            if (fake()->boolean(28)) {
                $this->event($comercio, $sessionId, 'dish_want', [
                    'nombre' => fake()->randomElement(['Milanesa napo', 'Pizza muzza', 'Ensalada César', 'Flan casero']),
                    'price' => '$'.fake()->numberBetween(4, 18).'000',
                ], $when->copy()->addSeconds(55));
            }
        }
    }

    private function event(Comercio $comercio, string $sessionId, string $event, array $data, Carbon $at): void
    {
        TrackingEvent::create([
            'comercio_id' => $comercio->id,
            'session_id' => $sessionId,
            'event' => $event,
            'data' => $data,
            'user_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * Momento aleatorio en los últimos 30 días, con picos de almuerzo y cena.
     */
    private function randomMoment(): Carbon
    {
        $dia = Carbon::now()->subDays(fake()->numberBetween(0, 29));
        $hora = fake()->randomElement([12, 13, 13, 14, 20, 20, 21, 21, 22, 9, 16, 18]);

        return $dia->setTime($hora, fake()->numberBetween(0, 59));
    }
}
