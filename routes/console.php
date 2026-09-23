<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mesas que quedaron abiertas porque nadie las cerró al final del turno.
//
// ⚠️ Esto NO es lo que sostiene la feature, y no hace falta configurarlo para salir a
// producción. Las comandas vencidas se cierran solas cuando alguien las usa:
// `ComandaService::abrirOTomar()` no le devuelve una comanda de anoche al grupo que se
// sienta hoy, y el tablero del mozo las limpia al dibujarse. Se hizo así justamente para
// no depender de que `schedule:run` esté bien puesto en el servidor: un cron que no corre
// no avisa a nadie, y el síntoma —los pedidos de dos grupos mezclados en una mesa— se ve
// recién cuando ya pasó.
//
// Queda programado igual porque no molesta y adelanta la limpieza de las mesas que nadie
// va a tocar hasta mañana. Si el cron no está, no pasa nada.
Schedule::command('comandas:cerrar-inactivas')->everyFifteenMinutes()->withoutOverlapping();

// Avisos de la campanita que salen del estado de la cuenta (carta lista sin publicar,
// asistente apagado). Una vez por día, a la mañana.
//
// ⚠️ A diferencia del de arriba, ESTE sí depende de que `schedule:run` esté puesto en el
// servidor: nadie más calcula estos avisos. Si el cron no corre, no se rompe nada, pero
// esos dos avisos no van a existir nunca.
Schedule::command('avisos:revisar')
    ->timezone('America/Argentina/Buenos_Aires')
    ->dailyAt('10:00')
    ->withoutOverlapping();
