<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Achica y convierte a webp las fotos de platos y promociones.
 *
 * Antes se guardaba el archivo tal cual lo subía el comercio. Medido en la base: fotos de
 * 3120x4160 y 1 MB para una tarjeta que en el menú público se muestra a 448 px de ancho. El
 * cliente que abre el menú desde la mesa se bajaba un mega por foto, con datos móviles.
 *
 * El ahorro NO viene de bajar la calidad: viene de no guardar píxeles que nadie mira. Sobre
 * una foto real de 3120x4160 (1.024 KB): a 1200 px en webp queda en ~112 KB, un 89% menos.
 */
class ImagenMenuService
{
    /**
     * Lado mayor de la imagen guardada.
     *
     * La tarjeta del menú público se muestra a 448 px (`max-w-md` + `w-full`), que en pantalla
     * retina pide ~900 px reales. 1200 deja margen para agrandar la tarjeta más adelante sin
     * tener que reprocesar todo: la diferencia con 800 px son 50 KB, y no vale el riesgo de
     * que una foto se vea blanda en un iPhone.
     */
    public const LADO_MAYOR = 1200;

    /** Calidad webp. Con el ahorro ya en 89%, conviene gastar de más acá antes que ahorrar. */
    public const CALIDAD = 85;

    /** Lo que aceptamos que suban. `webp` entra porque es a lo que convertimos igual. */
    public const FORMATOS = ['jpeg', 'png', 'jpg', 'gif', 'webp'];

    /** Tope de subida en KB. Alto a propósito: lo que se guarda queda chico igual. */
    public const MAX_KB = 10240;

    public function __construct(private BrandThemeService $tema) {}

    /** Regla de validación de Laravel, para que los controllers no la escriban a mano. */
    public static function reglaDeValidacion(bool $requerida = true): string
    {
        return ($requerida ? 'required' : 'nullable')
            .'|image|mimes:'.implode(',', self::FORMATOS).'|max:'.self::MAX_KB;
    }

    /**
     * Procesa y guarda la imagen. Devuelve la ruta relativa dentro del disco público.
     *
     * Si el procesamiento falla (GD sin webp, formato que no abre, imagen gigante que no
     * entra en memoria) guarda el archivo original en vez de tirar error: que el comercio
     * pueda subir su foto importa más que ahorrar kilobytes.
     */
    public function procesar(UploadedFile $archivo, string $directorio): string
    {
        try {
            $img = $this->tema->cargar($archivo->getRealPath());

            $lado = max(imagesx($img), imagesy($img));
            if ($lado > self::LADO_MAYOR) {
                $escala = self::LADO_MAYOR / $lado;
                $reescalada = $this->tema->reescalar($img, (int) round(imagesx($img) * $escala));
                imagedestroy($img);
                $img = $reescalada;
            }

            // guardar() hace imagedestroy() de la imagen que recibe.
            return $this->tema->guardar($img, $directorio.'/'.Str::random(40).'.webp', self::CALIDAD);
        } catch (\Throwable $e) {
            Log::warning('No se pudo optimizar la imagen, se guarda el original: '.$e->getMessage());

            return $archivo->store($directorio, 'public');
        }
    }
}
