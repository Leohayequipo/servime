<?php

namespace App\Services;

use App\Models\Comercio;
use Illuminate\Support\Facades\Storage;

/**
 * Deriva el tema del menú público a partir de la portada del comercio.
 *
 * La idea: la pantalla no se pinta con colores elegidos por nosotros, se pinta
 * con los de la foto que el comercio subió para representarse. De una sola
 * imagen salen cuatro cosas:
 *
 *   1. Fondo desenfocado (~5 KB) — la atmósfera del lugar.
 *   2. Hero nítido 16:11 — para que se reconozca dónde está parado el cliente.
 *   3. Miniatura cuadrada — para la tarjeta del menú completo.
 *   4. Paleta: base, velo, superficies y acento sugerido.
 *
 * Todo con GD, sin dependencias nuevas. Tarda ~1s por imagen, así que corre en
 * cola (GenerateBrandThemeJob), nunca dentro del request.
 */
class BrandThemeService
{
    /** Luminancia de canal a la que se lleva la base: ni penumbra ni claro. */
    private const BASE_LUM_OBJETIVO = 74.0;

    /** Luminancia a la que se lleva el fondo desenfocado. */
    private const FONDO_LUM_OBJETIVO = 78.0;

    /** Contraste WCAG mínimo del acento contra la base. */
    private const CONTRASTE_MINIMO = 4.5;

    /** Acento de reserva cuando la imagen no tiene ningún color de marca. */
    private const ACENTO_FALLBACK = [201, 169, 97];

    /**
     * Alto máximo del hero respecto del ancho (4/3 = una foto vertical normal).
     *
     * Más alto que esto se recorta, no para encuadrar sino para no mandar
     * píxeles que ninguna pantalla va a mostrar. Da margen de sobra para mover
     * el punto focal: la caja del hero en el celular es apaisada (~1.6:1).
     */
    private const ALTO_MAXIMO_RELATIVO = 4 / 3;

    /**
     * Tamaño recomendado de la portada.
     *
     * Es una recomendación, no un requisito: una imagen chica se procesa igual
     * y sale borrosa en el hero, pero es mejor que el comercio tenga su foto
     * mediocre a que no pueda subir nada. El aviso queda guardado para
     * mostrárselo en el panel.
     */
    public const ANCHO_RECOMENDADO = 900;

    public const ALTO_RECOMENDADO = 600;

    /**
     * Hasta acá se procesa dentro del request; más grande va a la cola.
     *
     * Medido en local: hay un costo fijo de ~1,1 s (el desenfoque por pasos y
     * la codificación webp) más ~0,18 s por megapíxel.
     *
     *    1,9 MP → 1,4 s     7,7 MP → 2,4 s
     *    4,3 MP → 1,9 s    12,2 MP → 3,2 s
     *
     * El corte va en 14 y no en 12 porque la foto estándar de iPhone es
     * 4032×3024 = 12,2 MP: con 12 quedaba justo afuera y el caso más común de
     * todos terminaba en la cola, que es justo la parte frágil que queremos
     * evitar. 3,2 s es una espera razonable al guardar un formulario.
     * En un hosting más lento conviene bajarlo.
     */
    public const INLINE_MAX_MEGAPIXELES = 14;

    /**
     * ¿Conviene resolverla en el momento o mandarla a la cola?
     *
     * Sólo mira las dimensiones (getimagesize no descomprime la imagen), así
     * que preguntar es gratis.
     */
    public function puedeProcesarInline(string $ruta): bool
    {
        $info = @getimagesize($ruta);

        if ($info === false) {
            return false;
        }

        return ($info[0] * $info[1]) <= self::INLINE_MAX_MEGAPIXELES * 1_000_000;
    }

    /**
     * Genera todos los derivados y devuelve lo que hay que guardar en el
     * comercio. No persiste: de eso se encarga el job.
     *
     * @return array{portada_fondo_url:string, portada_hero_url:string, portada_thumb_url:string, tema_derivado:array}
     *
     * @throws \RuntimeException si no hay GD o la imagen no se puede leer
     */
    public function generar(Comercio $comercio, string $rutaOriginal): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('GD no está disponible.');
        }

        $img = $this->cargar($rutaOriginal);

        try {
            $promedio = $this->promedio($img);
            $base = $this->baseDesdePromedio($promedio);
            $acento = $this->acento($img, $base);

            $dir = "comercios/{$comercio->id}/portada";

            // El hero y la miniatura NO se recortan para encuadrar: se guardan
            // enteros y el encuadre lo hace el CSS con el punto focal. Así el
            // comerciante puede moverlo sin que haya que regenerar nada, y el
            // mismo punto sirve para las dos.
            $fondo = $this->guardar($this->desenfocar($img), "$dir/fondo.webp", 76);
            $hero = $this->guardar($this->paraEncuadrar($img, 780), "$dir/hero.webp", 70);
            $thumb = $this->guardar($this->paraEncuadrar($img, 220), "$dir/thumb.webp", 78);

            return [
                'portada_fondo_url' => $fondo,
                'portada_hero_url' => $hero,
                'portada_thumb_url' => $thumb,
                'tema_derivado' => [
                    'ancho' => imagesx($img),
                    'alto' => imagesy($img),
                    'baja_calidad' => imagesx($img) < self::ANCHO_RECOMENDADO
                        || imagesy($img) < self::ALTO_RECOMENDADO,
                    'promedio' => $this->hex($promedio),
                    'base' => $this->hex($base),
                    'velo' => $this->velo($base),
                    'superficies' => $this->superficies($base),
                    'acento_sugerido' => $this->hex($acento['rgb']),
                    'acento_crudo' => $acento['crudo'] ? $this->hex($acento['crudo']) : null,
                    'acento_fallback' => $acento['fallback'],
                    'contraste' => round($this->contraste($acento['rgb'], $base), 2),
                    'hash_origen' => hash_file('xxh128', $rutaOriginal),
                    'generado_en' => now()->toIso8601String(),
                ],
            ];
        } finally {
            imagedestroy($img);
        }
    }

    // ── Paleta ───────────────────────────────────────────────────────────

    /**
     * Base del tema: el promedio de la imagen, corregido.
     *
     * El promedio crudo no sirve tal cual por dos razones, y cada corrección
     * arregla una: promediar apaga el croma (todo tiende al gris, de ahí el
     * piso de saturación) y la luminancia del promedio dice a qué hora
     * sacaron la foto, no cómo es la marca. El TONO no se toca: es lo único
     * que el promedio conserva de la identidad.
     */
    public function baseDesdePromedio(array $promedio): array
    {
        [$h, $s, $l] = $this->rgbAHsl($promedio);

        $s = min(0.30, max(0.16, $s));
        $rgb = $this->hslARgb([$h, $s, $l]);

        $lum = $this->lumCanal($rgb);
        $k = $lum > 1 ? self::BASE_LUM_OBJETIVO / $lum : 1.0;

        return array_map(fn ($c) => (int) max(0, min(255, round($c * $k))), $rgb);
    }

    /**
     * El velo que va sobre el fondo desenfocado.
     *
     * Es un solo color en tres pasos (100%, 84%, 65% de luminosidad), que es
     * la estructura que hacía funcionar al taupe original.
     */
    public function velo(array $base): array
    {
        $mul = fn (float $k) => array_map(fn ($c) => (int) round($c * $k), $base);

        return [
            'centro' => $this->rgba($mul(1.22), 0.30),
            'halo' => $this->rgba($base, 0.58),
            'arriba' => $this->rgba($base, 0.72),
            'medio' => $this->rgba($mul(0.84), 0.66),
            'abajo' => $this->rgba($mul(0.65), 0.84),
        ];
    }

    /**
     * Superficies (tarjetas) derivadas de la base.
     *
     * Definirlas como blanco translúcido fijo sólo funciona sobre fondos muy
     * oscuros: sobre un medio tono, sumar 4% de blanco no se ve. Una versión
     * oscurecida de la propia base con filo claro arriba se lee como plano
     * elevado sobre cualquier tono.
     */
    public function superficies(array $base): array
    {
        $k = $this->lumRelativa($base) > 0.06 ? 0.42 : 0.62;

        return [
            'fondo' => $this->rgba(array_map(fn ($c) => (int) round($c * $k), $base), 0.62),
            'hover' => $this->rgba(array_map(fn ($c) => (int) round($c * ($k + 0.22)), $base), 0.50),
            'borde' => 'rgba(255,247,235,.13)',
            'borde_arriba' => 'rgba(255,247,235,.22)',
        ];
    }

    /**
     * Acento: color saturado más frecuente, normalizado hasta que se lea.
     *
     * Ojo con lo que devuelve el crudo: sobre una foto de ambiente gana la
     * vegetación o la madera, y sobre una foto de platos gana el marrón de la
     * comida. Por eso esto es una SUGERENCIA — quien manda sigue siendo el
     * color que el comerciante eligió en el selector de temas.
     *
     * @return array{rgb:array, crudo:?array, fallback:bool}
     */
    public function acento($img, array $base): array
    {
        $s = $this->reescalar($img, 72);
        $baldes = [];

        for ($y = 0, $h = imagesy($s); $y < $h; $y++) {
            for ($x = 0, $w = imagesx($s); $x < $w; $x++) {
                $c = imagecolorat($s, $x, $y);
                $r = ($c >> 16) & 255;
                $g = ($c >> 8) & 255;
                $b = $c & 255;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);

                if ($max < 40 || $min > 232) {
                    continue;   // casi negro o casi blanco
                }
                if (($max - $min) / max($max, 1) < 0.28) {
                    continue;   // gris
                }

                $k = (($r >> 5) << 6) | (($g >> 5) << 3) | ($b >> 5);
                $baldes[$k] ??= [0, 0, 0, 0];
                $baldes[$k][0] += $r;
                $baldes[$k][1] += $g;
                $baldes[$k][2] += $b;
                $baldes[$k][3]++;
            }
        }

        imagedestroy($s);

        if (! $baldes) {
            return ['rgb' => self::ACENTO_FALLBACK, 'crudo' => null, 'fallback' => true];
        }

        uasort($baldes, fn ($a, $b) => $b[3] <=> $a[3]);
        $top = reset($baldes);
        $crudo = [
            (int) ($top[0] / $top[3]),
            (int) ($top[1] / $top[3]),
            (int) ($top[2] / $top[3]),
        ];

        return ['rgb' => $this->normalizarAcento($crudo, $base), 'crudo' => $crudo, 'fallback' => false];
    }

    /**
     * Lleva el acento a una banda usable y después lo aclara hasta pasar el
     * contraste mínimo contra la base. Un vino casi negro y un amarillo flúor
     * tienen que salir los dos legibles.
     */
    public function normalizarAcento(array $crudo, array $base): array
    {
        [$h, $s, $l] = $this->rgbAHsl($crudo);

        $s = min(0.72, max(0.34, $s));
        $l = min(0.70, max(0.54, $l));
        $out = $this->hslARgb([$h, $s, $l]);

        $vueltas = 0;
        while ($this->contraste($out, $base) < self::CONTRASTE_MINIMO && $l < 0.90 && $vueltas++ < 45) {
            $l += 0.01;
            $out = $this->hslARgb([$h, $s, $l]);
        }

        return $out;
    }

    // ── Imagen ───────────────────────────────────────────────────────────

    /**
     * Desenfoque suave.
     *
     * Se baja a 40px —ese achicado ES el desenfoque, cada píxel promedia un
     * vecindario enorme— y se sube en pasos aplicando un gaussiano en cada
     * uno para borrar las costuras de la interpolación. Estirar una imagen
     * diminuta desde el navegador da bloques y rombos, no desenfoque.
     */
    public function desenfocar($img)
    {
        $cur = $this->reescalar($img, 40);

        foreach ([80, 160, 320, 560] as $ancho) {
            $siguiente = $this->reescalar($cur, $ancho);
            imagedestroy($cur);
            $cur = $siguiente;

            for ($i = 0; $i < 7; $i++) {
                imagefilter($cur, IMG_FILTER_GAUSSIAN_BLUR);
            }
        }

        $lum = $this->lumCanal($this->promedio($cur));
        imagefilter($cur, IMG_FILTER_BRIGHTNESS, (int) max(-150, min(90, round(self::FONDO_LUM_OBJETIVO - $lum))));
        imagefilter($cur, IMG_FILTER_CONTRAST, -6);

        return $cur;
    }

    /**
     * Imagen lista para encuadrar por CSS: se conserva entera.
     *
     * Sólo se recorta si es desproporcionadamente alta (más que 3:4), y ahí es
     * para no mandar píxeles que nadie va a ver nunca — no para elegir el
     * encuadre. Ese trabajo es del punto focal.
     */
    public function paraEncuadrar($img, int $ancho)
    {
        $w = imagesx($img);
        $h = imagesy($img);

        $recortada = $h / $w > self::ALTO_MAXIMO_RELATIVO
            ? $this->recorteAspecto($img, $w, 1 / self::ALTO_MAXIMO_RELATIVO, 0.5)
            : $img;

        $escalada = $this->reescalar($recortada, $ancho);

        if ($recortada !== $img) {
            imagedestroy($recortada);
        }

        return $escalada;
    }

    /** Recorte a una relación de aspecto, centrado en la franja superior. */
    public function recorteAspecto($img, int $anchoSalida, float $ratio, float $cy = 0.38)
    {
        $w = imagesx($img);
        $h = imagesy($img);

        $srcW = $w;
        $srcH = (int) round($w / $ratio);
        if ($srcH > $h) {
            $srcH = $h;
            $srcW = (int) round($h * $ratio);
        }

        $x = (int) max(0, min($w - $srcW, ($w - $srcW) / 2));
        $y = (int) max(0, min($h - $srcH, $cy * $h - $srcH / 2));

        $dst = imagecreatetruecolor($anchoSalida, (int) round($anchoSalida / $ratio));
        imagecopyresampled($dst, $img, 0, 0, $x, $y, imagesx($dst), imagesy($dst), $srcW, $srcH);

        return $dst;
    }

    public function recorteCuadrado($img, int $lado, float $cx = 0.5, float $cy = 0.55)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $src = (int) (min($w, $h) * 0.62);

        $x = (int) max(0, min($w - $src, $cx * $w - $src / 2));
        $y = (int) max(0, min($h - $src, $cy * $h - $src / 2));

        $dst = imagecreatetruecolor($lado, $lado);
        imagecopyresampled($dst, $img, 0, 0, $x, $y, $lado, $lado, $src, $src);

        return $dst;
    }

    /**
     * Carga la imagen respetando la orientación EXIF.
     *
     * Sin esto, una foto vertical sacada con celular llega acostada: GD no lee
     * la etiqueta de orientación que sí interpretan el visor y el navegador.
     *
     * Público porque `ImagenMenuService` lo reusa para las fotos de platos: la lectura del
     * EXIF y el corte por memoria tienen que ser los mismos en todas las imágenes que entran.
     */
    public function cargar(string $ruta)
    {
        $info = @getimagesize($ruta);

        if ($info === false) {
            throw new \RuntimeException("No se pudo leer la imagen: $ruta");
        }

        $this->verificarQueEntraEnMemoria($info[0], $info[1]);

        $img = match ($info[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => imagecreatefromwebp($ruta),
            IMAGETYPE_GIF => imagecreatefromgif($ruta),
            default => imagecreatefromjpeg($ruta),
        };

        if ($img === false) {
            throw new \RuntimeException("Formato no soportado: $ruta");
        }

        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($ruta);
            $grados = match ($exif['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($grados !== 0) {
                $rotada = imagerotate($img, $grados, 0);
                imagedestroy($img);
                $img = $rotada;
            }
        }

        return $img;
    }

    /**
     * Corta antes de descomprimir si la imagen no entra en memoria.
     *
     * El peso del archivo no dice nada: GD descomprime a bitmap, así que la
     * memoria es ancho × alto × 4 bytes. Un JPEG de 1 MB en 8000×6000 son
     * 192 MB en RAM y hace explotar PHP con un fatal error — que NO se puede
     * atrapar con try/catch y mata el proceso entero, dejando al comercio
     * clavado en "procesando".
     *
     * Medido: 4032×3024 (12 MP, foto típica de celular) usa 76 MB de pico.
     * De ahí sale el factor de ~6 bytes por píxel, que contempla la copia
     * intermedia del reescalado.
     */
    private function verificarQueEntraEnMemoria(int $ancho, int $alto): void
    {
        $limite = $this->limiteDeMemoriaEnBytes();

        if ($limite <= 0) {
            return;  // sin límite configurado
        }

        $necesita = $ancho * $alto * 6;
        $disponible = $limite - memory_get_usage(true) - (24 * 1024 * 1024); // margen

        if ($necesita > $disponible) {
            $mp = round($ancho * $alto / 1_000_000, 1);

            throw new \RuntimeException(
                "La imagen es demasiado grande para procesar: {$ancho}×{$alto} ({$mp} MP). ".
                'Reducila y volvé a subirla.'
            );
        }
    }

    /** memory_limit en bytes; 0 o -1 significan sin límite. */
    private function limiteDeMemoriaEnBytes(): int
    {
        $valor = trim((string) ini_get('memory_limit'));

        if ($valor === '' || $valor === '-1') {
            return 0;
        }

        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1024 * 1024 * 1024,
            'm' => $numero * 1024 * 1024,
            'k' => $numero * 1024,
            default => $numero,
        };
    }

    /** Público: lo reusa `ImagenMenuService` (ver `cargar`). */
    public function reescalar($img, int $ancho)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $alto = max(1, (int) round($ancho * $h / $w));

        $dst = imagecreatetruecolor($ancho, $alto);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $ancho, $alto, $w, $h);

        return $dst;
    }

    /** Guarda como webp en el disco público y devuelve la ruta relativa. Público: ver `cargar`. */
    public function guardar($img, string $ruta, int $calidad): string
    {
        ob_start();
        imagewebp($img, null, $calidad);
        $bin = ob_get_clean();
        imagedestroy($img);

        Storage::disk('public')->put($ruta, $bin);

        return $ruta;
    }

    /** Promedio liso de todos los píxeles. */
    public function promedio($img): array
    {
        $s = $this->reescalar($img, 40);
        $r = $g = $b = $n = 0;

        for ($y = 0, $h = imagesy($s); $y < $h; $y++) {
            for ($x = 0, $w = imagesx($s); $x < $w; $x++) {
                $c = imagecolorat($s, $x, $y);
                $r += ($c >> 16) & 255;
                $g += ($c >> 8) & 255;
                $b += $c & 255;
                $n++;
            }
        }

        imagedestroy($s);

        return [(int) ($r / $n), (int) ($g / $n), (int) ($b / $n)];
    }

    // ── Color ────────────────────────────────────────────────────────────

    public function contraste(array $a, array $b): float
    {
        $la = $this->lumRelativa($a);
        $lb = $this->lumRelativa($b);
        [$hi, $lo] = $la > $lb ? [$la, $lb] : [$lb, $la];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private function lumRelativa(array $rgb): float
    {
        $lin = fn (float $c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $lin($rgb[0]) + 0.7152 * $lin($rgb[1]) + 0.0722 * $lin($rgb[2]);
    }

    private function lumCanal(array $rgb): float
    {
        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    }

    private function rgbAHsl(array $c): array
    {
        [$r, $g, $b] = [$c[0] / 255, $c[1] / 255, $c[2] / 255];
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match ($max) {
            $r => (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6,
            $g => (($b - $r) / $d + 2) / 6,
            default => (($r - $g) / $d + 4) / 6,
        };

        return [$h, $s, $l];
    }

    private function hslARgb(array $hsl): array
    {
        [$h, $s, $l] = $hsl;

        if ($s == 0.0) {
            $v = (int) round($l * 255);

            return [$v, $v, $v];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $canal = function (float $t) use ($p, $q) {
            $t = fmod($t + 1, 1);
            if ($t < 1 / 6) {
                return $p + ($q - $p) * 6 * $t;
            }
            if ($t < 1 / 2) {
                return $q;
            }
            if ($t < 2 / 3) {
                return $p + ($q - $p) * (2 / 3 - $t) * 6;
            }

            return $p;
        };

        return [
            (int) round($canal($h + 1 / 3) * 255),
            (int) round($canal($h) * 255),
            (int) round($canal($h - 1 / 3) * 255),
        ];
    }

    private function hex(array $c): string
    {
        return sprintf('#%02X%02X%02X', $c[0], $c[1], $c[2]);
    }

    private function rgba(array $c, float $a): string
    {
        return sprintf('rgba(%d,%d,%d,%.2f)', $c[0], $c[1], $c[2], $a);
    }
}
