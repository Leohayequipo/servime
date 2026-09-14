<?php

/**
 * La cola de `composer dev`, con reinicio automático.
 *
 * `queue:listen` corre cada job en un proceso aparte y lo mata si pasa el `--timeout`.
 * El problema es qué hace después: en vez de seguir escuchando, tira la excepción y se
 * cierra entero. Y `composer dev` no lo vuelve a levantar, así que la cola queda muerta
 * en silencio y los menús se quedan en "procesando" para siempre.
 *
 * Lo que lo dispara en la práctica no es un job colgado sino la PC suspendida: el
 * timeout se mide con el reloj de pared, así que al despertar la máquina un worker
 * que esperaba con la cola vacía "lleva" horas corriendo. Pasó el 14/09/2026 a las
 * 2 de la mañana, sin ningún job en la cola.
 *
 * Por qué no `concurrently --restart-tries`: reinicia TODOS los procesos del grupo, y
 * `pail` en Windows muere siempre al arrancar (necesita pcntl), así que quedaba en loop.
 *
 * El --timeout (710s) queda entre dos números:
 * - por ENCIMA del `$timeout` de ProcessMenuJob (700s), para no cortar un menú lento;
 * - por DEBAJO del `retry_after` de config/queue.php (720s). En Windows el `$timeout`
 *   del job no se aplica —el worker necesita pcntl para cortarlo—, así que el corte real
 *   es este. Si llegara después del `retry_after`, la cola le entregaría a otro worker
 *   un job que todavía está corriendo.
 */
$comando = 'php artisan queue:listen --tries=1 --timeout=710';

while (true) {
    passthru($comando, $codigo);

    fwrite(STDERR, "\n[cola-dev] queue:listen se cerró (código {$codigo}). Lo vuelvo a levantar en 3 segundos.\n\n");
    sleep(3);
}
