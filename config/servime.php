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

];
