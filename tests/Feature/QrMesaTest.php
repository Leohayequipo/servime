<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\MesaService;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El número al centro no puede comerse el QR.
 *
 * Acá no se decodifica el código: eso se prueba escaneando un cartel impreso con un
 * teléfono, que es el único lector que importa (se probó una librería PHP de zxing y
 * falla incluso con QR limpios, así que daba falsos negativos).
 *
 * Lo que sí se puede fijar de forma determinista es la geometría, que es donde están los
 * dos riesgos reales: que alguien agrande el disco del número "para que se vea mejor" y
 * los carteles dejen de escanear cuando ya están pegados en las mesas, y que el número
 * quede corrido dentro del disco (ya pasó una vez, ver el test de centrado).
 */
class QrMesaTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://servime.net/menu/lo-de-tito?m=k3f9qax2';

    /**
     * Píxeles que difieren entre dos PNG del mismo tamaño.
     *
     * @return array{cantidad:int, minX:int, maxX:int, minY:int, maxY:int}
     */
    private function diferencias(string $pngA, string $pngB): array
    {
        $a = imagecreatefromstring($pngA);
        $b = imagecreatefromstring($pngB);

        $ancho = imagesx($a);
        $alto = imagesy($a);

        $cantidad = 0;
        $minX = $ancho;
        $maxX = 0;
        $minY = $alto;
        $maxY = 0;

        for ($y = 0; $y < $alto; $y++) {
            for ($x = 0; $x < $ancho; $x++) {
                if (imagecolorat($a, $x, $y) === imagecolorat($b, $x, $y)) {
                    continue;
                }
                $cantidad++;
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }

        imagedestroy($a);
        imagedestroy($b);

        return compact('cantidad', 'minX', 'maxX', 'minY', 'maxY');
    }

    public function test_el_numero_solo_toca_un_cuadrado_chico_en_el_centro(): void
    {
        $qr = app(QrService::class);
        $size = 600;

        $limpio = $qr->png(self::URL, null, $size);
        $conNumero = $qr->png(self::URL, '148', $size);

        $info = getimagesizefromstring($limpio);
        $lado = $info[0];
        $diff = $this->diferencias($limpio, $conNumero);

        // Todo lo modificado entra en el cuadrado que circunscribe al halo (disco del 20%
        // por 1,16). El margen sobre eso es para el antialias, no para crecer.
        $maxLado = $lado * 0.25;
        $centro = $lado / 2;

        $this->assertGreaterThan(0, $diff['cantidad'], 'El número no se dibujó.');
        $this->assertLessThanOrEqual($maxLado, $diff['maxX'] - $diff['minX'] + 1);
        $this->assertLessThanOrEqual($maxLado, $diff['maxY'] - $diff['minY'] + 1);

        // Y está centrado: si se corriera, taparía un patrón de detección de las esquinas,
        // que es lo único que la corrección de error NO puede reconstruir.
        $this->assertEqualsWithDelta($centro, ($diff['minX'] + $diff['maxX']) / 2, $lado * 0.02);
        $this->assertEqualsWithDelta($centro, ($diff['minY'] + $diff['maxY']) / 2, $lado * 0.02);
    }

    public function test_el_area_tapada_queda_muy_por_debajo_de_lo_que_tolera_la_correccion_de_error(): void
    {
        $qr = app(QrService::class);

        $limpio = $qr->png(self::URL, null, 600);
        $conNumero = $qr->png(self::URL, '148', 600);

        $info = getimagesizefromstring($limpio);
        $areaTotal = $info[0] * $info[1];
        $diff = $this->diferencias($limpio, $conNumero);

        // El nivel H reconstruye hasta ~30% de los módulos. Un disco del 20% del lado con
        // su halo es ~4% del área: queda un orden de magnitud de margen para la impresión,
        // la luz del local y la cámara del cliente.
        $this->assertLessThan(0.06, $diff['cantidad'] / $areaTotal);
    }

    public function test_las_esquinas_con_los_patrones_de_deteccion_quedan_intactas(): void
    {
        $qr = app(QrService::class);

        $limpio = $qr->png(self::URL, null, 600);
        $conNumero = $qr->png(self::URL, '7', 600);

        $info = getimagesizefromstring($limpio);
        $lado = $info[0];
        $diff = $this->diferencias($limpio, $conNumero);

        // Los tres cuadrados de las esquinas ocupan cerca del 30% del lado desde cada
        // borde. Nada de lo modificado puede entrar en esa franja.
        $franja = $lado * 0.30;

        $this->assertGreaterThan($franja, $diff['minX']);
        $this->assertGreaterThan($franja, $diff['minY']);
        $this->assertLessThan($lado - $franja, $diff['maxX']);
        $this->assertLessThan($lado - $franja, $diff['maxY']);
    }

    /**
     * El número tiene que estar centrado DENTRO del disco, no sólo el disco dentro del QR.
     *
     * Se escapó una vez: el centrado ignoraba el offset que devuelve `imagettfbbox` (el
     * bearing de la fuente), así que cada dígito se iba para un lado distinto. El "148"
     * quedaba casi bien y tapaba el problema; el "1", que tiene mucho aire a los lados,
     * se veía claramente corrido. Por eso el caso se prueba dígito por dígito.
     */
    public function test_el_numero_queda_centrado_dentro_del_disco(): void
    {
        $qr = app(QrService::class);

        foreach (['1', '4', '7', '12', '148'] as $numero) {
            [$dx, $dy, $ancho] = $this->cajaDelNumero($qr->png(self::URL, $numero, 600));

            $this->assertGreaterThan(0, $ancho, "El número {$numero} no se dibujó.");
            $this->assertLessThanOrEqual(2, abs($dx), "El número {$numero} está corrido en horizontal.");
            $this->assertLessThanOrEqual(2, abs($dy), "El número {$numero} está corrido en vertical.");
        }
    }

    /**
     * Caja del número: se miran los píxeles claros de adentro del disco oscuro.
     *
     * Se usa el bounding box y no el centroide: el centroide es el centro de MASA del
     * glifo, y un "7" tiene casi toda la tinta arriba, así que daría corrido aun estando
     * perfectamente centrado.
     *
     * @return array{0: float, 1: float, 2: int} desvío x, desvío y, ancho de la caja
     */
    private function cajaDelNumero(string $png): array
    {
        $im = imagecreatefromstring($png);
        $lado = imagesx($im);
        $centro = $lado / 2;
        // Bien adentro del disco, para no tocar el halo blanco de afuera.
        $radio = (int) ($lado * 0.085);

        $minX = PHP_INT_MAX;
        $maxX = -1;
        $minY = PHP_INT_MAX;
        $maxY = -1;

        for ($y = (int) ($centro - $radio); $y <= (int) ($centro + $radio); $y++) {
            for ($x = (int) ($centro - $radio); $x <= (int) ($centro + $radio); $x++) {
                if (hypot($x - $centro, $y - $centro) > $radio) {
                    continue;
                }

                $color = imagecolorat($im, $x, $y);
                // El PNG del QR es de paleta: imagecolorat devuelve el índice, no el color.
                $rojo = imageistruecolor($im)
                    ? ($color >> 16) & 255
                    : imagecolorsforindex($im, $color)['red'];

                if ($rojo > 200) {
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
        }

        imagedestroy($im);

        if ($maxX < 0) {
            return [0.0, 0.0, 0];
        }

        return [
            ($minX + $maxX) / 2 - $centro,
            ($minY + $maxY) / 2 - $centro,
            $maxX - $minX + 1,
        ];
    }

    public function test_el_qr_de_una_mesa_codifica_su_propia_url(): void
    {
        $comercio = Comercio::factory()->create(['slug' => 'lo-de-tito']);
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 12]);

        // Mismos bytes que generar el QR de esa URL a mano: es la forma de verificar el
        // contenido sin depender de un decodificador.
        $this->assertSame(
            app(QrService::class)->png($mesa->urlPublica(), '12'),
            app(QrService::class)->pngDeMesa($mesa)
        );
    }

    public function test_el_png_se_cachea_en_disco_y_rotar_el_token_lo_invalida(): void
    {
        $comercio = Comercio::factory()->create();
        $mesa = Mesa::factory()->create(['comercio_id' => $comercio->id, 'numero' => 3]);
        $qr = app(QrService::class);

        $qr->pngDeMesa($mesa);
        $rutaVieja = storage_path("app/public/qr/{$comercio->id}/{$mesa->token}.png");
        $this->assertFileExists($rutaVieja);

        app(MesaService::class)->rotarToken($mesa);

        // El archivo del token viejo no puede quedar sirviendo un QR que ya no vale.
        $this->assertFileDoesNotExist($rutaVieja);

        $mesa = $mesa->fresh();
        $qr->pngDeMesa($mesa);
        $this->assertFileExists(storage_path("app/public/qr/{$comercio->id}/{$mesa->token}.png"));
    }

    public function test_un_qr_sin_numero_no_tiene_nada_dibujado_encima(): void
    {
        $qr = app(QrService::class);

        $diff = $this->diferencias(
            $qr->png(self::URL, null, 400),
            $qr->png(self::URL, '', 400)
        );

        $this->assertSame(0, $diff['cantidad']);
    }
}
