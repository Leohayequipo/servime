<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Publica archivos de `storage/app/public` en `public_html/storage`, que es de donde el
 * servidor web los sirve.
 *
 * Son dos lugares distintos porque en Hostinger `public_html/storage` es un directorio con
 * COPIAS y no un symlink: el symlink se intentó y falló. Mientras siga así, un archivo
 * guardado por Laravel no existe para el navegador hasta que alguien lo copia.
 *
 * El deploy copia todo de nuevo en cada corrida, pero eso no alcanza por dos motivos: lo
 * subido entre deploys no se ve hasta el siguiente, y si esa copia masiva falla a la mitad
 * -se traga los errores- las imágenes desaparecen sin que nadie se entere. Por eso cada
 * cosa que se sube se publica en el momento.
 *
 * NUNCA tira: no poder copiar una imagen no puede voltear la subida. Loguea y sigue.
 */
class PublicHtml
{
    /**
     * ¿Estamos donde existe el public_html a poblar?
     *
     * En local no existe y todo esto es un no-op, que es lo que se quiere: en local el
     * `php artisan storage:link` de siempre alcanza.
     */
    public static function disponible(): bool
    {
        $base = (string) config('servime.public_html');

        return $base !== '' && is_dir($base);
    }

    /**
     * Copia una ruta relativa del disco `public` a public_html, creando los directorios.
     *
     * @param  string|null  $ruta  ej: "comercios/14/portada/hero.webp"
     * @return bool si quedó publicado (o si no había nada que hacer)
     */
    public static function publicar(?string $ruta): bool
    {
        if (blank($ruta) || ! self::disponible()) {
            return false;
        }

        // El destino se arma con la ruta relativa tal cual, así que hay que asegurarse de
        // que no se escape del directorio con "..".
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');
        if (str_contains($ruta, '..')) {
            Log::warning('Ruta sospechosa al publicar en public_html', ['ruta' => $ruta]);

            return false;
        }

        try {
            $origen = Storage::disk('public')->path($ruta);

            if (! is_file($origen)) {
                return false;
            }

            $destino = rtrim((string) config('servime.public_html'), '/').'/storage/'.$ruta;
            $carpeta = dirname($destino);

            if (! is_dir($carpeta) && ! @mkdir($carpeta, 0775, true) && ! is_dir($carpeta)) {
                Log::error('No se pudo crear el directorio en public_html', ['carpeta' => $carpeta]);

                return false;
            }

            if (! @copy($origen, $destino)) {
                Log::error('No se pudo copiar a public_html', [
                    'origen' => $origen,
                    'destino' => $destino,
                    'error' => error_get_last(),
                    'carpeta_escribible' => is_writable($carpeta),
                ]);

                return false;
            }

            @chmod($destino, 0664);

            return true;
        } catch (\Throwable $e) {
            Log::error('Excepción al publicar en public_html: '.$e->getMessage(), ['ruta' => $ruta]);

            return false;
        }
    }

    /**
     * Publica varias rutas. Los nulos se ignoran, así se le puede pasar directamente lo
     * que devuelve el modelo sin filtrar antes.
     *
     * @param  array<int, string|null>  $rutas
     * @return int cuántas quedaron publicadas
     */
    public static function publicarVarias(array $rutas): int
    {
        $ok = 0;

        foreach ($rutas as $ruta) {
            if (self::publicar($ruta)) {
                $ok++;
            }
        }

        return $ok;
    }
}
