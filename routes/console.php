<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mesas que quedaron abiertas porque nadie las cerró al final del turno. Cada 15' y no
// una vez por día: si corriera de madrugada, la mesa que se fue a las 22 seguiría
// figurando ocupada toda la noche y el mozo del turno siguiente vería el salón lleno.
//
// Requiere el cron de Laravel en el servidor (`schedule:run` cada minuto). Si ese cron no
// está configurado, esto no corre: se puede ejecutar a mano con
// `php artisan comandas:cerrar-inactivas`.
Schedule::command('comandas:cerrar-inactivas')->everyFifteenMinutes()->withoutOverlapping();
