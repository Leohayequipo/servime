<?php

namespace App\Console\Commands;

use App\Models\Comercio;
use App\Support\PublicHtml;
use Illuminate\Console\Command;

/**
 * Copia a public_html las portadas que ya estaban subidas.
 *
 * La copia automática al subir arregla las portadas NUEVAS, pero las que ya existían
 * siguen invisibles hasta que alguien las vuelva a subir. Esto las publica todas de una,
 * sin tocar la base ni regenerar imágenes: solo copia archivos.
 *
 * Se puede correr las veces que haga falta.
 */
class PublicarPortadas extends Command
{
    protected $signature = 'portadas:publicar {--comercio= : Solo este comercio (id o slug)}';

    protected $description = 'Copia las portadas de storage a public_html, de donde las sirve el servidor web';

    public function handle(): int
    {
        if (! PublicHtml::disponible()) {
            $this->warn('No existe '.config('servime.public_html').' — nada que hacer.');
            $this->line('En local no hace falta: alcanza con php artisan storage:link.');

            return self::SUCCESS;
        }

        $comercios = Comercio::whereNotNull('portada_url')
            ->when($this->option('comercio'), function ($q, $cual) {
                $q->where(is_numeric($cual) ? 'id' : 'slug', $cual);
            })
            ->get();

        if ($comercios->isEmpty()) {
            $this->info('No hay comercios con portada.');

            return self::SUCCESS;
        }

        $publicados = 0;
        $sinArchivo = [];

        foreach ($comercios as $comercio) {
            $archivos = array_filter($comercio->archivosPortada());
            $ok = PublicHtml::publicarVarias($archivos);
            $publicados += $ok;

            // Menos archivos copiados que esperados significa que el ORIGEN no está: el
            // archivo se perdió del storage y hay que volver a subir la foto. Es distinto
            // de que falle la copia, y conviene que se distinga en la salida.
            if ($ok < count($archivos)) {
                $sinArchivo[] = "{$comercio->slug} ({$ok}/".count($archivos).')';
            }

            $this->line("  {$comercio->slug}: {$ok} archivo(s)");
        }

        $this->newLine();
        $this->info("Listo: {$publicados} archivo(s) publicados de {$comercios->count()} comercio(s).");

        if ($sinArchivo) {
            $this->newLine();
            $this->warn('Estos quedaron incompletos (falta el archivo en storage o no se pudo copiar):');
            foreach ($sinArchivo as $linea) {
                $this->line("  - {$linea}");
            }
            $this->line('Revisá storage/logs/laravel.log para ver el motivo de cada uno.');
        }

        return self::SUCCESS;
    }
}
