<?php

namespace Tests\Feature;

use App\Http\Middleware\BloquearRobots;
use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\TrackingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Los robots no le preguntan al asistente (cada pregunta es una consulta paga a OpenAI y se
 * descuenta del cupo del comercio) ni suman a las estadísticas. Ver BloquearRobots.
 */
class RobotsTest extends TestCase
{
    use RefreshDatabase;

    // El que dejó los mensajes en producción, tal cual.
    private const GOOGLEBOT = 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.8010.52 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private const CUBOT = 'Mozilla/5.0 (Linux; Android 10; CUBOT X30) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

    private function comercio(): Comercio
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Te recomiendo la Milanesa. [[PLATOS: Milanesa]]']]],
        ], 200)]);

        $comercio = Comercio::factory()->create(['enable_ai_responses' => true]);
        Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Principales', 'platos' => [['nombre' => 'Milanesa', 'precio' => '$12.000']]],
            ]], JSON_UNESCAPED_UNICODE),
        ]);

        return $comercio;
    }

    public function test_googlebot_no_le_pregunta_al_asistente_ni_gasta_cupo(): void
    {
        $comercio = $this->comercio();

        // Lo mismo que hacía en producción: el atajo de la portada preguntando solo.
        $this->withHeader('User-Agent', self::GOOGLEBOT)
            ->postJson(route('chat.api', $comercio), [
                'message' => '¿Qué me recomendás?',
                'tipo' => 'chip_portada',
                'session_id' => 'sess_google',
            ])->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, ChatConversation::count());
        $this->assertSame(0, $comercio->fresh()->mensajesIaUsados());
    }

    public function test_el_stream_tampoco(): void
    {
        $comercio = $this->comercio();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.8010.52 Mobile Safari/537.36 (compatible; GoogleOther)')
            ->post(route('chat.stream', $comercio), ['message' => '¿Qué me recomendás?'])
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, ChatConversation::count());
    }

    public function test_los_robots_no_suman_a_las_estadisticas(): void
    {
        $comercio = Comercio::factory()->create();

        $this->withHeader('User-Agent', self::GOOGLEBOT)
            ->postJson(route('track', $comercio), ['event' => 'page_view', 'session_id' => 'sess_google'])
            ->assertForbidden();

        $this->assertSame(0, TrackingEvent::count());
    }

    public function test_un_cliente_con_un_celular_que_dice_bot_en_el_nombre_si_tiene_respuesta(): void
    {
        $comercio = $this->comercio();

        $this->withHeader('User-Agent', self::CUBOT)
            ->postJson(route('chat.api', $comercio), [
                'message' => '¿tienen milanesa?',
                'session_id' => 'sess_cubot',
            ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, ChatConversation::count());
    }

    public static function navegadores(): array
    {
        return [
            'Chrome en un CUBOT' => [self::CUBOT],
            'Safari en iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'],
            'Motorola' => ['Mozilla/5.0 (Linux; Android 14; moto g84 5G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36'],
            'navegador de Instagram' => ['Mozilla/5.0 (Linux; Android 13; SM-A135M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.6099.230 Mobile Safari/537.36 Instagram 315.0.0.29.109 Android (33/13; 450dpi; 1080x2208; samsung; SM-A135M; a13; exynos850; es_US; 553046236)'],
            'navegador de Facebook' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBAV/440.0.0.33.111;FBBV/543209876;FBDV/iPhone14,5;FBMD/iPhone;FBSN/iOS;FBSV/17.1;FBSS/3;FBCR/;FBID/phone;FBLC/es_LA;FBOP/5]'],
        ];
    }

    /** @dataProvider navegadores */
    public function test_los_navegadores_de_verdad_pasan(string $userAgent): void
    {
        $this->assertFalse(BloquearRobots::esRobot($userAgent));
    }

    public static function robots(): array
    {
        return [
            'Googlebot' => [self::GOOGLEBOT],
            'GoogleOther' => ['Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.8010.52 Mobile Safari/537.36 (compatible; GoogleOther)'],
            'Bing' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            'el de ChatGPT' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)'],
            'vista previa de Facebook' => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
            'Chrome automatizado' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/120.0.0.0 Safari/537.36'],
            'sin nombre' => [''],
        ];
    }

    /** @dataProvider robots */
    public function test_los_robots_no_pasan(string $userAgent): void
    {
        $this->assertTrue(BloquearRobots::esRobot($userAgent));
    }

    public function test_robots_txt_cierra_la_api_pero_no_los_menus(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertMatchesRegularExpression('#^Disallow: /api/\s*$#m', $robots);
        // Los menús tienen que seguir apareciendo en Google.
        $this->assertStringNotContainsString('Disallow: /menu', $robots);
    }
}
