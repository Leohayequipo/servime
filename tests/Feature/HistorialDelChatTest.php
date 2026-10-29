<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Al salir del chat y volver a entrar, la charla de la misma visita se vuelve a mostrar.
 *
 * Antes el chat aparecía vacío mientras el asistente recordaba todo (y sin límite de
 * tiempo). Ahora las dos puntas usan la misma regla: ChatConversation::deLaVisita().
 */
class HistorialDelChatTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConMenu(): Comercio
    {
        $comercio = Comercio::factory()->create(['enable_ai_responses' => true]);
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Empanadas', 'platos' => [
                    ['nombre' => 'Empanada de Hongos', 'precio' => '$3.500'],
                    ['nombre' => 'Empanada Pollo', 'precio' => '$3.500'],
                ]],
            ]]),
        ]);

        return $comercio;
    }

    private function mensaje(Comercio $comercio, string $sesion, int $hace, string $texto, array $extra = []): ChatConversation
    {
        $c = new ChatConversation(array_merge([
            'comercio_id' => $comercio->id,
            'session_id' => $sesion,
            'user_message' => $texto,
            'bot_response' => 'Respuesta a '.$texto,
            'used_openai' => true,
        ], $extra));
        $c->created_at = now()->subMinutes($hace);
        $c->save();

        return $c;
    }

    private function historial(Comercio $comercio, ?string $sesion): array
    {
        $headers = $sesion ? ['X-Session-ID' => $sesion] : [];

        return $this->getJson(route('chat.historial', $comercio), $headers)
            ->assertOk()
            ->json('mensajes');
    }

    public function test_devuelve_la_charla_de_la_visita_con_sus_tarjetas(): void
    {
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', 20, 'tienen empanadas?', [
            'platos_ofrecidos' => ['Empanada de Hongos', 'Empanada Pollo'],
        ]);
        $this->mensaje($comercio, 'sess_a', 5, 'algo dulce?');

        $mensajes = $this->historial($comercio, 'sess_a');

        $this->assertCount(2, $mensajes);
        $this->assertSame('tienen empanadas?', $mensajes[0]['cliente']);
        $this->assertSame('Respuesta a tienen empanadas?', $mensajes[0]['respuesta']);
        $this->assertSame(['Empanada de Hongos', 'Empanada Pollo'], array_column($mensajes[0]['platos'], 'nombre'));
        $this->assertSame('$3.500', $mensajes[0]['platos'][0]['precio']);
        $this->assertSame('algo dulce?', $mensajes[1]['cliente']);
    }

    public function test_un_boton_muestra_lo_que_vio_el_cliente_y_no_el_texto_interno(): void
    {
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', 5, 'Mostrame TODAS las opciones de la categoría Empanadas, sin limitarte a unas pocas.', [
            'origen' => 'categoria',
            'metadata' => ['etiqueta' => 'Ver más opciones'],
        ]);

        $this->assertSame('Ver más opciones', $this->historial($comercio, 'sess_a')[0]['cliente']);
    }

    public function test_una_visita_terminada_no_vuelve(): void
    {
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', (ChatConversation::HORAS_VISITA * 60) + 10, 'lo de ayer');

        $this->assertSame([], $this->historial($comercio, 'sess_a'));
    }

    public function test_una_charla_larga_pero_activa_no_se_corta_a_la_mitad(): void
    {
        // Arrancó hace 5 horas pero nunca pasaron 3 sin escribir: es una sola visita.
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', 300, 'primero');
        $this->mensaje($comercio, 'sess_a', 170, 'segundo');
        $this->mensaje($comercio, 'sess_a', 30, 'tercero');

        $this->assertSame(['primero', 'segundo', 'tercero'], array_column($this->historial($comercio, 'sess_a'), 'cliente'));
    }

    public function test_lo_de_la_visita_anterior_no_se_mezcla_con_la_actual(): void
    {
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', 60 * 24, 'la semana pasada');
        $this->mensaje($comercio, 'sess_a', 10, 'hoy');

        $this->assertSame(['hoy'], array_column($this->historial($comercio, 'sess_a'), 'cliente'));
    }

    public function test_no_muestra_la_charla_de_otro_dispositivo_ni_de_otro_comercio(): void
    {
        $comercio = $this->comercioConMenu();
        $otro = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_b', 5, 'de otro celular');
        $this->mensaje($otro, 'sess_a', 5, 'en otro local');

        $this->assertSame([], $this->historial($comercio, 'sess_a'));
    }

    public function test_sin_id_de_dispositivo_no_devuelve_nada(): void
    {
        // El chat adivina la sesión por IP + navegador cuando falta el id. Para el
        // historial eso mostraría la charla de otro cliente del mismo wifi.
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', 5, 'privado', ['user_ip' => '127.0.0.1']);

        $this->assertSame([], $this->historial($comercio, null));
    }

    public function test_un_plato_que_ya_no_esta_en_la_carta_no_reaparece(): void
    {
        $comercio = $this->comercioConMenu();
        $this->mensaje($comercio, 'sess_a', 5, 'empanadas', [
            'platos_ofrecidos' => ['Empanada de Hongos', 'Empanada de Choclo'],
        ]);

        $this->assertSame(['Empanada de Hongos'], array_column($this->historial($comercio, 'sess_a')[0]['platos'], 'nombre'));
    }

    public function test_el_chat_guarda_la_etiqueta_del_boton(): void
    {
        // OpenAI caído: alcanza para que la consulta se registre sin llamar a nadie.
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*/chat/completions' => Http::response(['error' => ['message' => 'x']], 429)]);
        $comercio = $this->comercioConMenu();

        $this->postJson(route('chat.api', $comercio), [
            'message' => 'Mostrame todas las opciones que coincidan con lo que te pregunté, sin limitarte a unas pocas.',
            'tipo' => 'ver_mas',
            'scope' => 'empanadas',
            'etiqueta' => 'Ver más opciones',
        ], ['X-Session-ID' => 'sess_a']);

        $this->assertSame('Ver más opciones', $this->historial($comercio, 'sess_a')[0]['cliente']);
    }
}
