<?php

namespace Tests\Feature;

use App\Exports\MenuExport;
use App\Imports\MenuImport;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * El import rearma cada plato DESDE CERO con lo que trae la planilla.
 *
 * Todo lo que no viaje en una columna se pierde sin aviso: la estrella vivía solo en el JSON
 * del menú, así que exportar a Excel y reimportar borraba los platos estrella del comercio.
 */
class ExportacionMenuTest extends TestCase
{
    use RefreshDatabase;

    private function menu(): Menu
    {
        return Menu::factory()->for(Comercio::factory())->activo()->create([
            'contexto_gpt' => json_encode(['categorias' => [[
                'nombre' => 'Desayunos',
                'platos' => [
                    ['nombre' => 'Medialuna', 'precio' => '$1.600', 'tags' => ['veggie'], 'estrella' => true],
                    ['nombre' => 'Tostadas', 'precio' => '$4.500'],
                ],
            ]]]),
        ]);
    }

    /** Simula lo que hace Excel: exporta, y esas filas vuelven a entrar por el import. */
    private function reimportar(Menu $menu): array
    {
        $export = new MenuExport($menu);
        $encabezados = $export->headings();

        $filas = $export->collection()->map(function ($fila) use ($export, $encabezados) {
            $valores = $export->map($fila);
            // WithHeadingRow normaliza los encabezados a snake_case sin acentos.
            $claves = array_map(
                fn ($h) => str_replace([' ', 'í', 'á', 'ó'], ['_', 'i', 'a', 'o'], mb_strtolower($h)),
                $encabezados
            );

            return array_combine($claves, $valores);
        });

        $import = new MenuImport($menu->comercio_id);
        $import->collection(new Collection($filas->all()));

        return $import->getData();
    }

    public function test_la_estrella_viaja_en_el_excel(): void
    {
        $export = new MenuExport($this->menu());

        $this->assertContains('Estrella', $export->headings());

        $filas = $export->collection();
        $this->assertSame('Sí', $filas[0]['estrella'], 'la marcada');
        $this->assertSame('', $filas[1]['estrella'], 'la que no');
    }

    public function test_exportar_y_reimportar_no_borra_los_platos_estrella(): void
    {
        $platos = $this->reimportar($this->menu())['categorias'][0]['platos'];

        $this->assertTrue($platos[0]['estrella'] ?? false, 'la Medialuna seguía siendo estrella');
        $this->assertArrayNotHasKey('estrella', $platos[1], 'las Tostadas nunca lo fueron');
    }

    public function test_el_import_normaliza_el_precio_igual_que_el_panel(): void
    {
        $import = new MenuImport(1);
        $import->collection(new Collection([
            ['categoria' => 'Test', 'nombre_del_plato' => 'Suelto', 'precio' => '4500'],
            ['categoria' => 'Test', 'nombre_del_plato' => 'Con formato', 'precio' => '$5.700,00'],
            ['categoria' => 'Test', 'nombre_del_plato' => 'Texto', 'precio' => '2x1'],
        ]));

        $platos = $import->getData()['categorias'][0]['platos'];

        $this->assertSame('$4.500', $platos[0]['precio'], 'número pelado: se formatea');
        $this->assertSame('$5.700,00', $platos[1]['precio'], 'ya tenía formato: se respeta');
        $this->assertSame('2x1', $platos[2]['precio'], 'texto: se respeta');
    }

    public function test_acepta_cualquier_marca_de_estrella_escrita_a_mano(): void
    {
        // El export escribe "Sí", pero el comercio edita la planilla y pone lo que se le ocurre.
        $import = new MenuImport(1);
        $import->collection(new Collection([
            ['categoria' => 'T', 'nombre_del_plato' => 'A', 'estrella' => 'si'],
            ['categoria' => 'T', 'nombre_del_plato' => 'B', 'estrella' => 'X'],
            ['categoria' => 'T', 'nombre_del_plato' => 'C', 'estrella' => '1'],
            ['categoria' => 'T', 'nombre_del_plato' => 'D', 'estrella' => 'no'],
            ['categoria' => 'T', 'nombre_del_plato' => 'E', 'estrella' => ''],
        ]));

        $platos = $import->getData()['categorias'][0]['platos'];

        foreach ([0, 1, 2] as $i) {
            $this->assertTrue($platos[$i]['estrella'] ?? false, "la fila $i debía quedar marcada");
        }
        $this->assertArrayNotHasKey('estrella', $platos[3], '"no" no marca');
        $this->assertArrayNotHasKey('estrella', $platos[4], 'vacío no marca');
    }
}
