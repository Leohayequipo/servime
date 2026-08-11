<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * De dónde vino cada mensaje: lo tipeó el cliente o lo armó un botón.
 *
 * Importa porque los widgets de texto del panel (palabras más buscadas, preguntas
 * frecuentes, intención) usan el scope `escritas()` para excluir lo que generó la interfaz.
 * Sin marcar los atajos, "¿Qué me recomendás?" terminaba siendo la palabra más buscada del
 * comercio: medido, 21 de 103 mensajes eran botones y una sola frase de chip ocupaba cuatro
 * casilleros del top.
 */
class OrigenDeLosMensajesTest extends TestCase
{
    use RefreshDatabase;

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

    public static function tiposDeAtajo(): array
    {
        return [
            'chip de la portada' => ['chip'],
            'filtro de tags' => ['tag'],
            'chip de categoría' => ['categoria'],
            'botón ver más' => ['ver_mas'],
        ];
    }

    /**
     * El riesgo del cambio estaba acá: si la validación no acepta el tipo, el request
     * falla con 422 y el chat deja de responder.
     *
     * @dataProvider tiposDeAtajo
     */
    public function test_el_chat_responde_y_marca_el_origen(string $tipo): void
    {
        $comercio = $this->comercio();

        $this->postJson(route('chat.api', $comercio), [
            'message' => 'algo rico',
            'tipo' => $tipo,
            'scope' => 'algo rico',
            'session_id' => 'sess_'.$tipo,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame($tipo, ChatConversation::latest('id')->first()->origen);
    }

    public function test_lo_que_escribe_el_cliente_queda_como_escrito(): void
    {
        $comercio = $this->comercio();

        $this->postJson(route('chat.api', $comercio), [
            'message' => '¿tienen milanesa?',
            'session_id' => 'sess_libre',
        ])->assertOk();

        $this->assertSame('escrito', ChatConversation::latest('id')->first()->origen);
    }

    public function test_un_tipo_desconocido_se_rechaza_sin_dejar_al_cliente_colgado(): void
    {
        $comercio = $this->comercio();

        // Si alguien suma un atajo nuevo en el front y se olvida de la validación, el
        // mensaje se rechaza — pero el controller lo captura y el cliente igual recibe una
        // respuesta legible, no un error crudo.
        $this->postJson(route('chat.api', $comercio), [
            'message' => 'hola',
            'tipo' => 'inventado',
            'session_id' => 'sess_x',
        ])->assertStatus(400)->assertJson(['success' => false]);

        $this->assertNull(ChatConversation::latest('id')->first());
    }

    public function test_queda_registrado_que_platos_ofrecio_el_asistente(): void
    {
        $comercio = $this->comercio();

        $this->postJson(route('chat.api', $comercio), [
            'message' => 'algo rico',
            'session_id' => 'sess_platos',
        ])->assertOk();

        // El marcador [[PLATOS: ...]] se borra de la respuesta antes de guardarla, así que
        // sin esta columna el historial del panel no permitía ver qué se ofreció.
        $conv = ChatConversation::latest('id')->first();

        $this->assertSame(['Milanesa'], $conv->platos_ofrecidos);
        $this->assertSame(1, $conv->platos_sugeridos);
        $this->assertStringNotContainsString('[[PLATOS', $conv->bot_response);
    }

    public function test_los_atajos_no_ensucian_las_palabras_mas_buscadas(): void
    {
        $comercio = $this->comercio();

        foreach (range(1, 5) as $i) {
            ChatConversation::create([
                'comercio_id' => $comercio->id, 'session_id' => "s$i",
                'user_message' => '¿Qué me recomendás?', 'origen' => 'chip',
                'bot_response' => 'ok', 'used_openai' => true, 'response_time_ms' => 900,
            ]);
        }
        ChatConversation::create([
            'comercio_id' => $comercio->id, 'session_id' => 'real',
            'user_message' => 'tienen milanesa napolitana', 'origen' => 'escrito',
            'bot_response' => 'ok', 'used_openai' => true, 'response_time_ms' => 900,
        ]);

        $this->assertSame(1, ChatConversation::forComercio($comercio->id)->escritas()->count());
    }
}
