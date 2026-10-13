<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Menu;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * La búsqueda en el menú que se hace con PHP ANTES de llamar al modelo.
 *
 * Existe porque gpt-4o-mini no encuentra de forma confiable un plato suelto en un menú
 * largo: negaba tener rabas con "- Rabas — $26.000" en el contexto. En vez de confiar en
 * que preste atención, se le pasa el resultado ya buscado.
 *
 * Estos tests no llaman a OpenAI: verifican la búsqueda, que es la parte determinística.
 */
class BusquedaEnMenuTest extends TestCase
{
    use RefreshDatabase;

    private function comercioConCarta(): Comercio
    {
        $comercio = Comercio::factory()->create();

        Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Fritos', 'platos' => [
                    ['nombre' => 'Rabas', 'precio' => '$26.000'],
                    ['nombre' => 'Papas Fritas', 'precio' => '$9.500'],
                ]],
                ['nombre' => 'Cervezas 0.0%', 'platos' => [
                    ['nombre' => 'Corona Sin Alcohol', 'precio' => '$8.000'],
                ]],
                ['nombre' => 'Empanadas', 'platos' => [
                    ['nombre' => 'Empanada de Carne', 'precio' => '$3.000'],
                ]],
            ]], JSON_UNESCAPED_UNICODE),
        ]);

        return $comercio;
    }

    /** @return array<int, string> nombres encontrados */
    private function buscar(Comercio $comercio, string $mensaje): array
    {
        $m = new ReflectionMethod(ChatbotService::class, 'platosQueCoinciden');
        $m->setAccessible(true);

        return array_column($m->invoke(new ChatbotService, $comercio, $mensaje), 'nombre');
    }

    public function test_encuentra_el_plato_por_su_nombre(): void
    {
        $this->assertSame(['Rabas'], $this->buscar($this->comercioConCarta(), 'tienen rabas?'));
    }

    public function test_encuentra_aunque_el_cliente_use_plural(): void
    {
        $this->assertSame(['Empanada de Carne'], $this->buscar($this->comercioConCarta(), 'tienen empanadas?'));
    }

    public function test_encuentra_por_el_nombre_de_la_categoria(): void
    {
        // El menú no tiene ningún plato llamado "cerveza": está en la categoría.
        $this->assertSame(['Corona Sin Alcohol'], $this->buscar($this->comercioConCarta(), 'tienen cerveza?'));
    }

    public function test_entiende_la_jerga(): void
    {
        $this->assertSame(['Corona Sin Alcohol'], $this->buscar($this->comercioConCarta(), 'una birra bien fria'));
    }

    public function test_el_plato_que_se_llama_asi_va_antes_que_el_de_la_categoria(): void
    {
        $comercio = Comercio::factory()->create();
        Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                // Ocho medialunas en una categoría que contiene la palabra "tortas": sin
                // prioridad por nombre llenaban el cupo y tapaban las tortas de verdad.
                ['nombre' => 'Panadería y tortas', 'platos' => array_map(
                    fn ($i) => ['nombre' => "Medialuna $i", 'precio' => '$1.000'],
                    range(1, 8)
                )],
                ['nombre' => 'Tortas', 'platos' => [
                    ['nombre' => 'Torta de Ricota', 'precio' => '$7.000'],
                ]],
            ]], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame('Torta de Ricota', $this->buscar($comercio, 'hay tortas?')[0]);
    }

    public function test_una_pregunta_de_opinion_no_trae_platos(): void
    {
        $comercio = Comercio::factory()->create();
        Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            // Nombre real de un comercio: si "mejor" se busca, el asistente terminaba
            // ofreciendo este desayuno a alguien que preguntaba con qué acompañar un lomito.
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Desayunos', 'platos' => [['nombre' => 'Des. El Mejor!', 'precio' => '$10.500']]],
            ]], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame([], $this->buscar($comercio, 'con cuál decís que queda mejor'));
        $this->assertSame([], $this->buscar($comercio, 'qué me recomendás rico'));
    }

    public function test_no_inventa_lo_que_no_esta(): void
    {
        $this->assertSame([], $this->buscar($this->comercioConCarta(), 'tienen sushi?'));
    }

    public function test_las_muletillas_no_traen_medio_menu(): void
    {
        // "tienen", "algo", "opciones" aparecen en cualquier pregunta: si se buscaran,
        // el prompt se llenaría de platos que no vienen al caso.
        $this->assertSame([], $this->buscar($this->comercioConCarta(), 'tienen algo para mi?'));
    }

    public function test_el_prompt_le_avisa_al_modelo_que_el_plato_existe(): void
    {
        $comercio = $this->comercioConCarta();
        $m = new ReflectionMethod(ChatbotService::class, 'promptUsuario');
        $m->setAccessible(true);
        $prompt = $m->invoke(new ChatbotService, $comercio, 'tienen rabas?');

        $this->assertStringContainsString('BÚSQUEDA YA HECHA EN EL MENÚ', $prompt);
        $this->assertStringContainsString('Rabas', $prompt);
        $this->assertStringContainsString('$26.000', $prompt);
    }

    public function test_si_no_hay_coincidencias_no_ensucia_el_prompt(): void
    {
        $comercio = $this->comercioConCarta();
        $m = new ReflectionMethod(ChatbotService::class, 'promptUsuario');
        $m->setAccessible(true);

        $this->assertStringNotContainsString('BÚSQUEDA YA HECHA EN EL MENÚ', $m->invoke(new ChatbotService, $comercio, 'tienen sushi?'));
    }

    public function test_el_verbo_combinar_no_trae_platos_que_se_llaman_combinado(): void
    {
        // Caso de producción: "quiero una pinta pero no sé con qué combinar" y el "Ver más"
        // ofreció dos licuados. "combinar" entraba por el error de tipeo tolerado y "pero",
        // por el arranque de palabra, habría traído una Peroni.
        $comercio = Comercio::factory()->create();
        Menu::factory()->activo()->create([
            'comercio_id' => $comercio->id,
            'contexto_gpt' => json_encode(['categorias' => [
                ['nombre' => 'Cervezas', 'platos' => [
                    ['nombre' => 'PINTA (Patagonia Tirada)', 'precio' => ''],
                    ['nombre' => 'Peroni', 'precio' => '$9.000'],
                ]],
                ['nombre' => 'Licuados', 'platos' => [
                    ['nombre' => 'Combinado (con LECHE)', 'precio' => '$7800'],
                ]],
            ]], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame(
            ['PINTA (Patagonia Tirada)'],
            $this->buscar($comercio, 'quiero una pinta pero no se con que combinar')
        );
        // El plato sí se encuentra cuando el cliente lo pide por su nombre.
        $this->assertSame(['Combinado (con LECHE)'], $this->buscar($comercio, 'un combinado'));
    }

    private function promptVerMas(Comercio $comercio, string $consultaOriginal): string
    {
        $servicio = new ChatbotService;
        foreach (['modoRespuesta' => 'ver_mas', 'scopeRespuesta' => $consultaOriginal] as $prop => $valor) {
            $p = new \ReflectionProperty(ChatbotService::class, $prop);
            $p->setAccessible(true);
            $p->setValue($servicio, $valor);
        }
        $m = new ReflectionMethod(ChatbotService::class, 'promptUsuario');
        $m->setAccessible(true);

        return $m->invoke($servicio, $comercio, 'Mostrame todas las opciones que coincidan con lo que te pregunté, sin limitarte a unas pocas.');
    }

    public function test_ver_mas_tras_un_pedido_de_acompanamiento_no_ofrece_lo_que_ya_eligio(): void
    {
        // La búsqueda de "quiero una cerveza..." trae la cerveza, y presentada como "ESTO ES
        // TODO" el modelo la ofrecía como "la opción que faltaba".
        $prompt = $this->promptVerMas($this->comercioConCarta(), 'quiero una cerveza pero no se con que combinarla');

        $this->assertStringNotContainsString('ESTO ES TODO', $prompt);
        $this->assertStringNotContainsString('Corona Sin Alcohol', $prompt);
    }

    public function test_ver_mas_de_una_busqueda_comun_sigue_trayendo_la_lista(): void
    {
        $prompt = $this->promptVerMas($this->comercioConCarta(), 'tienen cerveza?');

        $this->assertStringContainsString('ESTO ES TODO', $prompt);
        $this->assertStringContainsString('Corona Sin Alcohol', $prompt);
    }
}
