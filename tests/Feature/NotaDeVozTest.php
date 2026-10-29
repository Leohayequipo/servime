<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Las notas de voz del chat: el navegador graba, el servidor transcribe con OpenAI y el
 * texto se manda como un mensaje más. La cuota se descuenta una sola vez, en la respuesta.
 */
class NotaDeVozTest extends TestCase
{
    use RefreshDatabase;

    private function comercio(array $atributos = []): Comercio
    {
        $comercio = Comercio::factory()->create(array_merge(['enable_ai_responses' => true], $atributos));
        Menu::factory()->for($comercio)->create([
            'activo' => true,
            'procesado' => true,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Empanadas', 'platos' => [['nombre' => 'Empanada Capresse', 'precio' => '$3.500']]],
            ]]),
        ]);

        return $comercio;
    }

    private function audio(string $mime = 'audio/webm', string $nombre = 'nota.webm'): UploadedFile
    {
        $audio = UploadedFile::fake()->create($nombre, 40, $mime);
        file_put_contents($audio->getRealPath(), str_repeat('audio', 400));

        return $audio;
    }

    private function transcribir(Comercio $comercio, ?UploadedFile $audio = null)
    {
        return $this->post(route('chat.transcribir', $comercio), ['audio' => $audio ?? $this->audio()], [
            'Accept' => 'application/json',
            'X-Session-ID' => 'sess_voz',
        ]);
    }

    public function test_devuelve_el_texto_del_audio(): void
    {
        config(['services.openai.api_key' => 'test-key', 'services.openai.transcribe_model' => 'modelo-de-prueba']);
        Http::fake(['*/audio/transcriptions' => Http::response(['text' => ' ¿Tienen empanadas? '])]);
        $comercio = $this->comercio();

        $this->transcribir($comercio)->assertOk()->assertJson(['texto' => '¿Tienen empanadas?']);

        Http::assertSent(function (Request $r) {
            $campos = collect($r->data())->pluck('contents', 'name');

            return str_ends_with($r->url(), '/audio/transcriptions')
                && $campos['model'] === 'modelo-de-prueba'
                && $campos['language'] === 'es'
                // La carta va como pista, para que escriba los platos como están en ella
                && str_contains($campos['prompt'], 'Empanada Capresse');
        });
    }

    public function test_el_formato_sale_del_tipo_del_archivo(): void
    {
        // OpenAI decide el formato por la extensión: Safari graba mp4.
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*/audio/transcriptions' => Http::response(['text' => 'hola'])]);

        $this->transcribir($this->comercio(), $this->audio('audio/mp4', 'blob'))->assertOk();

        Http::assertSent(fn (Request $r) => collect($r->data())->firstWhere('name', 'file')['filename'] === 'audio.mp4');
    }

    public function test_transcribir_no_descuenta_cuota(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*/audio/transcriptions' => Http::response(['text' => 'hola'])]);
        $comercio = $this->comercio();
        $antes = $comercio->fresh()->toArray();

        $this->transcribir($comercio)->assertOk();

        $this->assertEquals($antes, $comercio->fresh()->toArray());
        $this->assertSame(0, ChatConversation::count());
    }

    public function test_sin_asistente_no_se_gasta_en_transcribir(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake();

        $this->transcribir($this->comercio(['enable_ai_responses' => false]))
            ->assertStatus(403)->assertJson(['error' => 'no_disponible']);

        Http::assertNothingSent();
    }

    public function test_si_openai_falla_avisa_que_no_entendio(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*/audio/transcriptions' => Http::response(['error' => ['message' => 'x']], 500)]);

        $this->transcribir($this->comercio())->assertStatus(422)->assertJson(['error' => 'no_entendido']);
    }

    public function test_un_audio_en_silencio_no_se_manda_como_mensaje_vacio(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*/audio/transcriptions' => Http::response(['text' => '   '])]);

        $this->transcribir($this->comercio())->assertStatus(422);
    }

    public function test_rechaza_un_audio_vacio(): void
    {
        Http::fake();

        $this->transcribir($this->comercio(), UploadedFile::fake()->create('nota.webm', 0, 'audio/webm'))
            ->assertStatus(422)->assertJsonValidationErrors('audio');

        Http::assertNothingSent();
    }

    public function test_rechaza_lo_que_no_es_audio(): void
    {
        Http::fake();

        $this->transcribir($this->comercio(), UploadedFile::fake()->create('menu.pdf', 40, 'application/pdf'))
            ->assertStatus(422)->assertJsonValidationErrors('audio');

        Http::assertNothingSent();
    }

    public function test_el_mensaje_por_voz_queda_marcado_y_vuelve_en_el_historial(): void
    {
        // OpenAI caído para el chat: alcanza para que la consulta se registre.
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['*/chat/completions' => Http::response(['error' => ['message' => 'x']], 429)]);
        $comercio = $this->comercio();

        $this->postJson(route('chat.api', $comercio), ['message' => 'tienen empanadas?', 'voz' => true], ['X-Session-ID' => 'sess_voz']);

        $conv = ChatConversation::first();
        $this->assertTrue($conv->metadata['voz']);
        // Sigue siendo texto del cliente: cuenta en los widgets de palabras.
        $this->assertSame('escrito', $conv->origen);

        $historial = $this->getJson(route('chat.historial', $comercio), ['X-Session-ID' => 'sess_voz'])->json('mensajes');
        $this->assertTrue($historial[0]['voz']);
    }
}
