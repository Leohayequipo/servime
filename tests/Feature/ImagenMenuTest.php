<?php

namespace Tests\Feature;

use App\Services\ImagenMenuService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Las fotos de platos se guardaban tal cual las subía el comercio.
 *
 * Medido en la base: 3120x4160 y 1 MB para una tarjeta que en el menú público se muestra a
 * 448 px. El cliente que abre el menú en la mesa se bajaba un mega por foto.
 */
class ImagenMenuTest extends TestCase
{
    private string $dir = 'test-imagenes';

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory($this->dir);
        parent::tearDown();
    }

    /** Genera un JPEG real (no un fake de Laravel: acá se procesan los píxeles de verdad). */
    private function jpeg(int $ancho, int $alto): UploadedFile
    {
        $img = imagecreatetruecolor($ancho, $alto);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
        $ruta = tempnam(sys_get_temp_dir(), 'test').'.jpg';
        imagejpeg($img, $ruta, 90);
        imagedestroy($img);

        return new UploadedFile($ruta, 'foto.jpg', 'image/jpeg', null, true);
    }

    private function medir(string $rutaRelativa): array
    {
        return getimagesize(Storage::disk('public')->path($rutaRelativa));
    }

    public function test_achica_las_fotos_grandes_al_lado_mayor(): void
    {
        $ruta = app(ImagenMenuService::class)->procesar($this->jpeg(3120, 4160), $this->dir);

        [$ancho, $alto] = $this->medir($ruta);

        $this->assertSame(ImagenMenuService::LADO_MAYOR, $alto, 'el lado mayor manda');
        $this->assertSame(900, $ancho, 'la proporción se mantiene');
    }

    public function test_no_agranda_una_foto_mas_chica_que_el_limite(): void
    {
        // Estirarla solo sumaría peso sin agregar un pixel de información.
        $ruta = app(ImagenMenuService::class)->procesar($this->jpeg(300, 200), $this->dir);

        $this->assertSame([300, 200], array_slice($this->medir($ruta), 0, 2));
    }

    public function test_guarda_siempre_en_webp(): void
    {
        // webp pesa ~30% menos que JPEG con la misma calidad; es la parte gratis del cambio.
        $ruta = app(ImagenMenuService::class)->procesar($this->jpeg(2000, 1500), $this->dir);

        $this->assertStringEndsWith('.webp', $ruta);
        $this->assertSame(IMAGETYPE_WEBP, $this->medir($ruta)[2]);
    }

    public function test_si_no_puede_procesar_guarda_el_original_en_vez_de_fallar(): void
    {
        // Que el comercio pueda subir su foto importa más que ahorrar kilobytes.
        $roto = tempnam(sys_get_temp_dir(), 'roto').'.jpg';
        file_put_contents($roto, 'esto no es una imagen');

        $ruta = app(ImagenMenuService::class)
            ->procesar(new UploadedFile($roto, 'roto.jpg', 'image/jpeg', null, true), $this->dir);

        $this->assertTrue(Storage::disk('public')->exists($ruta));
    }

    public function test_la_regla_de_validacion_acepta_webp_y_diez_megas(): void
    {
        // Con 2 MB una foto de celular actual no entraba. Puede ser alto porque lo que se
        // guarda queda chico igual.
        $regla = ImagenMenuService::reglaDeValidacion();

        $this->assertStringContainsString('webp', $regla);
        $this->assertStringContainsString('max:10240', $regla);
        $this->assertStringStartsWith('required|image|mimes:', $regla);
        $this->assertStringStartsWith('nullable|', ImagenMenuService::reglaDeValidacion(false));
    }
}
