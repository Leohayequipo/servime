<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La cola tiene que darle a cada job tiempo de terminar antes de dárselo a otro worker.
 *
 * `retry_after` es el plazo tras el cual la cola considera abandonado un job reservado y
 * se lo entrega a otro worker. Si es menor que el timeout del job, un job lento —procesar
 * un menú tarda más de un minuto— se ejecuta DOS veces en paralelo: dos llamadas a la IA
 * cobradas y dos mails al comercio. Venía en los 90 por defecto de Laravel contra un job
 * de 700, y por eso existe este test: el día que alguien sume un job más lento que el
 * plazo, se entera acá y no por la factura.
 */
class ColaTest extends TestCase
{
    public function test_retry_after_le_gana_al_job_mas_lento(): void
    {
        $retryAfter = (int) config('queue.connections.database.retry_after');

        $lentos = [];

        foreach (glob(app_path('Jobs/*.php')) as $archivo) {
            $clase = 'App\\Jobs\\'.basename($archivo, '.php');

            if (! class_exists($clase)) {
                continue;
            }

            // Sin instanciar: los jobs piden sus modelos en el constructor.
            $timeout = (new \ReflectionClass($clase))->getDefaultProperties()['timeout'] ?? null;

            if ($timeout !== null && $timeout >= $retryAfter) {
                $lentos[] = "{$clase} (timeout {$timeout}s)";
            }
        }

        $this->assertEmpty(
            $lentos,
            "Estos jobs tardan más de lo que la cola espera antes de reintentar ({$retryAfter}s),\n"
            ."así que se pueden ejecutar dos veces en paralelo. Subí `retry_after` en\n"
            ."config/queue.php por encima del timeout más alto:\n\n"
            .implode("\n", $lentos)
        );
    }
}
