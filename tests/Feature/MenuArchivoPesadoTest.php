<?php

namespace Tests\Feature;

use App\Jobs\ProcessMenuJob;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use App\Services\GeminiPdfProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El tope de cada archivo de la carta (`Menu::MAX_KB_ARCHIVO`) sale de Gemini: el archivo va
 * en base64 dentro del pedido, y el pedido tiene un tope de ~20 MB. Un PDF más pesado lo
 * achica el navegador antes de subirlo (public/js/achicar-pdf.js); acá va lo del servidor.
 */
class MenuArchivoPesadoTest extends TestCase
{
    use RefreshDatabase;

    private function subirPdfDe(int $kilobytes)
    {
        Queue::fake();
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->create(['user_id' => $user->id]);

        return $this->actingAs($user)->post(route('menus.store', $comercio), [
            'nombre' => 'Carta pesada',
            'archivos' => [UploadedFile::fake()->create('carta.pdf', $kilobytes, 'application/pdf')],
        ]);
    }

    public function test_un_pdf_del_tope_se_acepta(): void
    {
        $this->subirPdfDe(Menu::MAX_KB_ARCHIVO)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('pdf', Menu::sole()->formato);
        Queue::assertPushed(ProcessMenuJob::class, 1);
    }

    public function test_un_pdf_que_pasa_el_tope_se_rechaza(): void
    {
        $this->subirPdfDe(Menu::MAX_KB_ARCHIVO + 1)
            ->assertSessionHasErrors(['archivos.0' => 'Cada archivo puede pesar hasta 14 MB.']);

        $this->assertSame(0, Menu::count());
        Queue::assertNothingPushed();
    }

    /** Un PDF de relleno del tamaño pedido. A Gemini no llega: el pedido está fingido. */
    private function pdfDe(int $bytes): string
    {
        Storage::fake('public');
        Storage::disk('public')->put('carta.pdf', '%PDF-1.7'.str_repeat('0', $bytes - 8));

        return Storage::disk('public')->path('carta.pdf');
    }

    public function test_el_pdf_mas_pesado_que_se_puede_subir_entra_en_el_pedido_a_gemini(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode(['categorias' => [[
                        'nombre' => 'Cafetería',
                        'platos' => [['nombre' => 'Espresso', 'precio' => '$3200', 'descripcion' => '']],
                    ]]])]]],
                    'finishReason' => 'STOP',
                ]],
            ]),
        ]);

        $resultado = (new GeminiPdfProcessorService)->processPdfToMenu($this->pdfDe(Menu::MAX_KB_ARCHIVO * 1024));

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');
        Http::assertSentCount(1);
    }

    public function test_un_pdf_que_no_entra_en_el_pedido_no_se_manda(): void
    {
        // 15 MB: antes pasaba el control (se comparaba crudo contra 20 MB) y en base64 son 20.
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake();

        $resultado = (new GeminiPdfProcessorService)->processPdfToMenu($this->pdfDe(15 * 1024 * 1024));

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('demasiado grande', $resultado['message']);
        Http::assertNothingSent();
    }
}
