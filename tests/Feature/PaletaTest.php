<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Fija la regla de la skill `identidad`: en el panel no hay colores sueltos.
 *
 * Todo pasa por los tokens de `tailwind.config.js`, cuyos valores viven en el
 * `:root` de `resources/css/app.css`. La paleta genérica de Tailwind está calibrada
 * para fondos oscuros: sobre el crema del panel, `text-green-200` es casi blanco y
 * `text-red-400` da 2,4:1. Así llegó a producción un «Comercio creado exitosamente»
 * que no se leía.
 *
 * Los 419 tests no detectan un color ilegible —verifican lógica—, así que esta es la
 * única red que hay contra la regresión.
 */
class PaletaTest extends TestCase
{
    /** Paletas de Tailwind que no deben aparecer con número de escala. */
    private const FAMILIAS = [
        'red', 'green', 'blue', 'yellow', 'amber', 'orange', 'purple', 'pink',
        'indigo', 'emerald', 'teal', 'cyan', 'violet', 'fuchsia', 'rose', 'lime',
        'sky', 'gray', 'slate', 'zinc', 'neutral', 'stone',
    ];

    /**
     * Lo que la skill marca como intocable, más lo que no llega a cargar `app.css`.
     *
     * - `menu/` es el menú público: el tema sale de la foto del comercio, ahí la
     *   marca es la del cliente y no la nuestra (ver la skill `portada`).
     * - `emails/` va con hex inline porque los clientes de correo no soportan
     *   variables CSS.
     * - `errors/` es standalone y no carga la hoja del panel.
     */
    private const EXENTOS = [
        'resources/views/menu/',
        'resources/views/emails/',
        'resources/views/errors/',
    ];

    public function test_el_panel_no_usa_la_paleta_generica_de_tailwind(): void
    {
        $patron = '/(?<![\w-])(?:hover:|focus:|active:|group-hover:|file:|hover:file:)?'
            .'(?:bg|text|border|ring|divide|from|to|via|placeholder)-'
            .'(?:'.implode('|', self::FAMILIAS).')-[0-9]{2,3}(?:\/[0-9]{1,3})?(?![\w-])/';

        $sucios = [];

        foreach ($this->vistasDelPanel() as $ruta) {
            $contenido = file_get_contents($ruta);

            // Los comentarios Blade explican justamente de qué clase se viene: no cuentan.
            $contenido = preg_replace('/\{\{--.*?--\}\}/s', '', $contenido);

            if (preg_match_all($patron, $contenido, $m)) {
                $relativa = str_replace(base_path().DIRECTORY_SEPARATOR, '', $ruta);
                $sucios[] = $relativa.' → '.implode(', ', array_unique($m[0]));
            }
        }

        $this->assertEmpty(
            $sucios,
            "Estas vistas del panel usan la paleta genérica de Tailwind en vez de los tokens.\n"
            ."Equivalencias: rojo → danger, verde → accent-green, azul → accent-blue,\n"
            ."amarillo → warning, gris → text-secondary / accent-gray.\n\n"
            .implode("\n", $sucios)
        );
    }

    /** @return iterable<string> */
    private function vistasDelPanel(): iterable
    {
        $base = base_path('resources/views');

        $archivos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($archivos as $archivo) {
            if (! str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }

            $ruta = str_replace('\\', '/', $archivo->getPathname());

            foreach (self::EXENTOS as $exento) {
                if (str_contains($ruta, $exento)) {
                    continue 2;
                }
            }

            yield $archivo->getPathname();
        }
    }
}
