<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Support\PublicHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La portada tiene que llegar a public_html, que es de donde el servidor web sirve.
 *
 * En Hostinger `public_html/storage` es un directorio con copias y no un symlink (el
 * symlink se probó y falló). El logo, las fotos de platos y el archivo del menú ya se
 * copiaban al subirse; la portada era la ÚNICA que no, y por eso solo aparecía después
 * de un deploy —y desaparecía si esa copia masiva fallaba a la mitad—.
 */
class PublicarPortadaTest extends TestCase
{
    use RefreshDatabase;

    private string $publicHtml;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Un public_html de mentira, para no depender de la ruta de Hostinger.
        $this->publicHtml = storage_path('framework/testing/public_html_'.uniqid());
        mkdir($this->publicHtml, 0775, true);
        config(['servime.public_html' => $this->publicHtml]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->publicHtml)) {
            exec('rm -rf '.escapeshellarg($this->publicHtml));
        }

        parent::tearDown();
    }

    private function comercioConPortada(): Comercio
    {
        $comercio = Comercio::factory()->create([
            'portada_url' => 'comercios/9/portada/original.jpg',
            'portada_fondo_url' => 'comercios/9/portada/fondo.webp',
            'portada_hero_url' => 'comercios/9/portada/hero.webp',
            'portada_thumb_url' => 'comercios/9/portada/thumb.webp',
            'portada_estado' => 'listo',
        ]);

        foreach ($comercio->archivosPortada() as $ruta) {
            Storage::disk('public')->put($ruta, 'imagen-de-prueba');
        }

        return $comercio;
    }

    public function test_publica_los_cuatro_archivos_de_la_portada(): void
    {
        $comercio = $this->comercioConPortada();

        $this->assertSame(4, PublicHtml::publicarVarias($comercio->archivosPortada()));

        foreach ($comercio->archivosPortada() as $ruta) {
            $this->assertFileExists("{$this->publicHtml}/storage/{$ruta}");
        }
    }

    public function test_el_original_va_incluido(): void
    {
        // Es el que muestra el panel para elegir el encuadre: sin él no se ve la foto y no
        // hay nada sobre lo que arrastrar el marcador. "No la puedo ver ni mover".
        $comercio = $this->comercioConPortada();

        $this->assertContains($comercio->portada_url, $comercio->archivosPortada());

        PublicHtml::publicar($comercio->portada_url);
        $this->assertFileExists("{$this->publicHtml}/storage/{$comercio->portada_url}");
    }

    public function test_no_se_puede_escapar_del_directorio(): void
    {
        // El destino se arma concatenando la ruta relativa: sin la guarda, un ".." dejaría
        // escribir fuera de public_html.
        $this->assertFalse(PublicHtml::publicar('../../../etc/algo.conf'));
        $this->assertFalse(PublicHtml::publicar('comercios/../../fuera.webp'));
    }

    public function test_sin_public_html_no_hace_nada_y_no_rompe(): void
    {
        // Es el caso de local: no existe el directorio y todo tiene que ser un no-op
        // silencioso, nunca una excepción que voltee la subida de la imagen.
        config(['servime.public_html' => '/ruta/que/no/existe']);

        $this->assertFalse(PublicHtml::disponible());
        $this->assertFalse(PublicHtml::publicar('comercios/9/portada/hero.webp'));
    }

    public function test_un_archivo_que_no_esta_en_storage_no_cuenta_como_publicado(): void
    {
        $comercio = Comercio::factory()->create([
            'portada_url' => 'comercios/9/portada/original.jpg',
            'portada_hero_url' => 'comercios/9/portada/hero.webp',
        ]);
        Storage::disk('public')->put($comercio->portada_url, 'solo-el-original');

        // Dos rutas, pero solo una existe: tiene que devolver 1, no 2. De ese número
        // depende que el comando sepa distinguir "no se pudo copiar" de "no está".
        $this->assertSame(1, PublicHtml::publicarVarias($comercio->archivosPortada()));
    }

    public function test_el_comando_publica_las_portadas_existentes(): void
    {
        $comercio = $this->comercioConPortada();

        $this->artisan('portadas:publicar')
            ->expectsOutputToContain($comercio->slug)
            ->assertExitCode(0);

        $this->assertFileExists("{$this->publicHtml}/storage/{$comercio->portada_hero_url}");
    }

    public function test_el_comando_no_explota_sin_public_html(): void
    {
        config(['servime.public_html' => '/ruta/que/no/existe']);

        $this->artisan('portadas:publicar')->assertExitCode(0);
    }
}
