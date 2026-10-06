<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El cliente de pruebas manda `Accept-Language: en-us` por defecto, y el menú
        // público elige el idioma por ese encabezado: sin esto, cualquier texto que tenga
        // traducción al inglés sale en inglés y los tests que miran la pantalla en
        // castellano fallan en cuanto alguien traduce un texto nuevo. Un test que necesite
        // otro idioma lo pide con su propio encabezado o con ?lang=.
        $this->withHeader('Accept-Language', 'es');
    }
}
