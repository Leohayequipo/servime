<?php

namespace Tests\Feature;

use App\Models\Comercio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Límites de las rutas públicas del menú: el cupo va por dispositivo, no por IP,
 * porque todos los clientes de un local comparten el wifi (ver AppServiceProvider).
 */
class RateLimitPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_dos_dispositivos_en_el_mismo_wifi_no_comparten_el_cupo_del_chat(): void
    {
        $limiter = RateLimiter::limiter('chat-ia');

        $mesa8 = $limiter($this->requestConHeader('dispositivo_mesa_8'));
        $mesa9 = $limiter($this->requestConHeader('dispositivo_mesa_9'));

        // El cupo por dispositivo es propio de cada uno...
        $this->assertNotSame($mesa8[0]->key, $mesa9[0]->key);
        $this->assertSame(20, $mesa8[0]->maxAttempts);

        // ...y el segundo límite, el de contención, sí es compartido por IP
        $this->assertSame($mesa8[1]->key, $mesa9[1]->key);
        $this->assertSame(60, $mesa8[1]->maxAttempts);
    }

    public function test_el_carrito_y_el_tracking_toman_el_session_id_del_body(): void
    {
        foreach (['carrito', 'tracking'] as $nombre) {
            $limiter = RateLimiter::limiter($nombre);

            $uno = $limiter($this->requestConBody('sess_uno'));
            $otro = $limiter($this->requestConBody('sess_otro'));

            $this->assertNotSame($uno[0]->key, $otro[0]->key, "El limitador '$nombre' ignoró el session_id del body");
            $this->assertSame($uno[1]->key, $otro[1]->key);
        }
    }

    public function test_sin_session_id_el_cupo_cae_a_la_ip(): void
    {
        $limiter = RateLimiter::limiter('chat-ia');

        $uno = $limiter(Request::create('/api/chat/x', 'POST', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']));
        $otro = $limiter(Request::create('/api/chat/x', 'POST', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']));

        $this->assertSame($uno[0]->key, $otro[0]->key);

        // Pero desde otra IP el cupo vuelve a ser propio
        $ajeno = $limiter(Request::create('/api/chat/x', 'POST', [], [], [], ['REMOTE_ADDR' => '9.9.9.9']));
        $this->assertNotSame($uno[0]->key, $ajeno[0]->key);
    }

    public function test_las_rutas_publicas_responden_con_el_limite_por_dispositivo(): void
    {
        $comercio = Comercio::factory()->create();

        $this->postJson(route('track', $comercio), [
            'event' => 'page_view',
            'session_id' => 'sess_1',
            'data' => ['view' => 'choose'],
        ])->assertNoContent()->assertHeader('X-RateLimit-Limit', 120);
    }

    public function test_rotar_el_session_id_no_saltea_el_limite(): void
    {
        $comercio = Comercio::factory()->create();

        // El session_id lo elige el cliente, así que el cupo por dispositivo solo no
        // alcanza: sin el segundo límite por IP bastaba con mandar un id nuevo cada vez.
        // Se usa un evento fuera de la whitelist para no insertar 600 filas: igual pasa
        // por el middleware, que es lo que se está probando.
        $bloqueadoEn = null;

        for ($i = 1; $i <= 620; $i++) {
            $status = $this->postJson(route('track', $comercio), [
                'event' => 'evento_raro',
                'session_id' => 'rotado_'.$i,
            ])->getStatusCode();

            if ($status === 429) {
                $bloqueadoEn = $i;
                break;
            }
        }

        $this->assertSame(601, $bloqueadoEn, 'El límite por IP debería cortar en el request 601');
    }

    public function test_el_chat_sin_stream_tambien_esta_limitado(): void
    {
        // Antes esta ruta era la única del chat sin throttle: pública y llamando a OpenAI.
        $middleware = collect(app('router')->getRoutes()->getByName('chat.api')->gatherMiddleware());

        $this->assertContains('throttle:chat-ia', $middleware);
    }

    private function requestConHeader(string $sessionId): Request
    {
        $request = Request::create('/api/chat/x', 'POST', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
        $request->headers->set('X-Session-ID', $sessionId);

        return $request;
    }

    private function requestConBody(string $sessionId): Request
    {
        return Request::create('/api/x', 'POST', ['session_id' => $sessionId], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
    }
}
