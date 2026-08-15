<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Comercio de demostración
    |--------------------------------------------------------------------------
    |
    | Slug del comercio que se muestra como demo pública en la landing.
    | Si el comercio no existe o no tiene un menú procesado, el botón de demo
    | simplemente no se muestra (la landing no se rompe en local ni en staging).
    |
    */

    'demo_slug' => env('DEMO_MENU_SLUG', 'ivanas'),

    /*
    |--------------------------------------------------------------------------
    | Pedido de reseña en Google
    |--------------------------------------------------------------------------
    |
    | Cuándo se le ofrece al cliente dejar su reseña. NO se exponen en el panel
    | a propósito: si cada comercio los toca, no se puede comparar entre locales
    | y el experimento no dice nada. Van por env para poder bajarlos en local y
    | probar sin esperar veinte minutos, sin riesgo de que el valor de prueba
    | termine en producción.
    |
    | - minutos: desde que abrió el menú por primera vez.
    | - mensajes: cuántos le mandó al asistente (el carrito también alcanza).
    | - dias_gracia: al que ya se le pidió, no se le vuelve a pedir por este
    |   tiempo. Es cliente habitual, no un desconocido nuevo cada vez. Al que
    |   además dejó su reseña no se le pide nunca más, sin importar este valor.
    |   En 0 no se recuerda nada: es el modo de prueba, para repetir el flujo
    |   sin borrar el localStorage del teléfono. NO usarlo en producción.
    |
    */

    'resenas' => [
        'minutos' => (int) env('RESENA_MINUTOS', 20),
        'mensajes' => (int) env('RESENA_MENSAJES', 3),
        'dias_gracia' => (int) env('RESENA_DIAS_GRACIA', 15),
    ],

];
