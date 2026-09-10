<?php

namespace App\Services;

use App\Models\Mesa;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

/**
 * Genera los QR de las mesas en el servidor.
 *
 * Antes los QR se armaban en el navegador pidiéndoselos a api.qrserver.com. Para un QR
 * por comercio se bancaba; para treinta mesas son treinta llamadas a un tercero cada vez
 * que alguien imprime, y si ese servicio se cae el comercio se queda sin poder imprimir
 * sus carteles. Acá no depende de nadie.
 */
class QrService
{
    /**
     * Corrección de error alta: tolera ~30% de módulos dañados.
     *
     * Es lo que permite tapar el centro con el número de mesa, y de paso aguanta que el
     * cartel se manche o se raye, que en un salón pasa todo el tiempo.
     */
    private const NIVEL_CORRECCION = ErrorCorrectionLevel::High;

    /**
     * Diámetro del disco con el número, como proporción del lado del QR.
     *
     * 20% y no 30%: el límite teórico es cuánto puede reconstruir el nivel H, pero ese
     * margen también lo consumen la impresión, la luz del local y la cámara del cliente.
     * Como es un círculo, el área tapada es π·r² ≈ 3% del total: sobra margen.
     */
    private const PROPORCION_DISCO = 0.20;

    /**
     * Halo blanco alrededor del disco.
     *
     * Es lo que separa el número del patrón de módulos. Sin él, el disco negro se pega a
     * los cuadraditos negros que le tocan y el conjunto se lee como una mancha.
     */
    private const PROPORCION_HALO = 1.16;

    /**
     * Fuente para el número del centro.
     *
     * Viene con la librería, así no hay que sumar un TTF al repo. Si algún día cambia de
     * lugar, `png()` genera el QR sin número en vez de romperse: un QR no puede fallar
     * por un tema tipográfico.
     */
    private function fuente(): ?string
    {
        $ruta = base_path('vendor/endroid/qr-code/assets/open_sans.ttf');

        return is_file($ruta) ? $ruta : null;
    }

    /**
     * PNG del QR. Si viene `$numero`, lo dibuja en un recuadro blanco al centro.
     *
     * El número del centro es para que el cliente confirme que escaneó la mesa en la que
     * está sentado. El número que se lee de lejos va afuera, en la tarjeta impresa.
     */
    public function png(string $url, ?string $numero = null, int $size = 600): string
    {
        $resultado = (new Builder(
            writer: new PngWriter,
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: self::NIVEL_CORRECCION,
            size: $size,
            margin: 16,
        ))->build();

        $png = $resultado->getString();

        if ($numero === null || $numero === '') {
            return $png;
        }

        return $this->conNumeroAlCentro($png, $numero) ?? $png;
    }

    /**
     * Dibuja el número en un recuadro blanco centrado sobre el QR ya generado.
     *
     * Devuelve null si algo no está disponible (GD, la fuente), para que el llamador se
     * quede con el QR limpio en vez de quedarse sin QR.
     */
    private function conNumeroAlCentro(string $png, string $numero): ?string
    {
        $fuente = $this->fuente();

        if (! $fuente || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $imagen = @imagecreatefromstring($png);

        if ($imagen === false) {
            return null;
        }

        try {
            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);

            $cx = (int) round($ancho / 2);
            $cy = (int) round($alto / 2);
            $disco = (int) round($ancho * self::PROPORCION_DISCO);
            $halo = (int) round($disco * self::PROPORCION_HALO);

            $blanco = (int) imagecolorallocate($imagen, 255, 255, 255);
            $tinta = (int) imagecolorallocate($imagen, 17, 17, 17);

            // Disco y no recuadro: el cuadrado blanco con el número adentro se leía como
            // un parche pegado encima del código. El círculo se lee como parte del
            // diseño, que es además cómo se ven los QR con logo de toda la vida.
            //
            // El color es tinta neutra, no el acento de la marca: los colores de ServiME
            // no entran en el menú del cliente ni en el QR (ver la skill `identidad`), y
            // el negro sobre blanco es lo que mejor escanea y lo que menos tinta gasta.
            imagefilledellipse($imagen, $cx, $cy, $halo, $halo, $blanco);
            imagefilledellipse($imagen, $cx, $cy, $disco, $disco, $tinta);

            // El cuerpo de la fuente se calcula midiendo y no con una fórmula por cantidad
            // de dígitos: "7" y "148" tienen que entrar los dos en el mismo disco. Se
            // limita al cuadrado inscrito (0,7·d) para que el número no toque el borde.
            $util = (int) round($disco * 0.62);
            $tamano = $this->tamanoQueEntra($fuente, $numero, $util);
            $caja = imagettfbbox($tamano, 0, $fuente, $numero);

            if ($caja === false) {
                return null;
            }

            // El origen que toma imagettftext es la línea base del primer glifo, no la
            // esquina de la caja: hay que restar el offset que devuelve imagettfbbox
            // (el bearing de la fuente). Sin eso el número queda corrido DENTRO del
            // disco, y corrido distinto según el dígito — el "1" tiene mucho aire a los
            // lados y se iba visiblemente para un costado, mientras que "148" parecía
            // centrado y tapaba el problema.
            $anchoTexto = $caja[2] - $caja[0];
            $altoTexto = $caja[1] - $caja[7];
            $x = (int) round($cx - $anchoTexto / 2 - $caja[0]);
            $y = (int) round($cy - $altoTexto / 2 - $caja[7]);

            imagettftext($imagen, $tamano, 0, $x, $y, $blanco, $fuente, $numero);

            ob_start();
            imagepng($imagen);

            return (string) ob_get_clean();
        } finally {
            imagedestroy($imagen);
        }
    }

    /**
     * Cuerpo de fuente más grande que entra en un cuadrado de lado `$util`.
     *
     * Se mide una vez con un cuerpo grande y se escala: `imagettfbbox` es lineal con el
     * tamaño, así que una sola medición alcanza y no hace falta iterar.
     */
    private function tamanoQueEntra(string $fuente, string $numero, int $util): int
    {
        $base = 100;
        $caja = imagettfbbox($base, 0, $fuente, $numero);

        if ($caja === false) {
            return (int) round($util * 0.6);
        }

        // Mismas diferencias que usa el centrado, para que medir y ubicar coincidan.
        $ancho = max(1, $caja[2] - $caja[0]);
        $alto = max(1, $caja[1] - $caja[7]);

        // Se limita por los dos lados: por ancho manda "148", por alto manda "7".
        $escala = min($util / $ancho, $util * 0.9 / $alto);

        return max(8, (int) round($base * $escala));
    }

    /**
     * PNG de una mesa, cacheado en disco.
     *
     * Se regenera solo si no existe. Rotar el token borra el archivo viejo
     * (ver `olvidar()`), así que el cache nunca sirve un QR que ya no vale.
     */
    public function pngDeMesa(Mesa $mesa, int $size = 600): string
    {
        $ruta = $this->rutaCache($mesa);
        $disco = Storage::disk('public');

        if ($disco->exists($ruta)) {
            return (string) $disco->get($ruta);
        }

        $png = $this->png($mesa->urlPublica(), (string) $mesa->numero, $size);
        $disco->put($ruta, $png);

        return $png;
    }

    /** Para embeber el QR en la hoja imprimible sin que el navegador pida 30 imágenes. */
    public function dataUriDeMesa(Mesa $mesa, int $size = 600): string
    {
        return 'data:image/png;base64,'.base64_encode($this->pngDeMesa($mesa, $size));
    }

    /** Borra el PNG cacheado de una mesa (al rotar el token o darla de baja). */
    public function olvidar(Mesa $mesa): void
    {
        Storage::disk('public')->delete($this->rutaCache($mesa));
    }

    private function rutaCache(Mesa $mesa): string
    {
        return "qr/{$mesa->comercio_id}/{$mesa->token}.png";
    }
}
