<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Plan;
use App\Models\Procesamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Planes Free y Mínimo: sin asistente, con tope de cartas, y editables como cualquier plan.
 */
class PlanFreeTest extends TestCase
{
    use RefreshDatabase;

    private function comercioEn(string $key, array $extra = []): Comercio
    {
        $user = User::factory()->create(['role' => 'user']);

        return Comercio::factory()->for($user)->create($extra + [
            'plan_id' => Plan::where('key', $key)->value('id'),
            'enable_ai_responses' => true,
        ]);
    }

    private function procesadas(Comercio $comercio, int $cuantas, bool $cuenta = true): void
    {
        for ($i = 0; $i < $cuantas; $i++) {
            Procesamiento::create([
                'comercio_id' => $comercio->id,
                'tipo' => Procesamiento::TIPO_CARTA,
                'origen' => Procesamiento::ORIGEN_COMERCIO,
                'cuenta_para_limite' => $cuenta,
            ]);
        }
    }

    // ---- Los planes ----

    public function test_la_migracion_crea_free_y_minimo_primeros_en_la_escalera(): void
    {
        $free = Plan::where('key', 'free')->firstOrFail();
        $minimo = Plan::where('key', 'minimo')->firstOrFail();

        $this->assertSame('Gratis', $free->precioLegible());
        $this->assertSame('$2.000', $minimo->precioLegible());
        $this->assertFalse($free->tieneAsistente());
        $this->assertFalse($minimo->tieneAsistente());

        // La única diferencia entre los dos: si el pedido le llega al mozo.
        $this->assertFalse($free->tieneFeature('comandas'));
        $this->assertTrue($minimo->tieneFeature('comandas'));

        $this->assertSame([1, 2], [$free->orden, $minimo->orden]);

        // Apagados: el deploy no los publica en la landing, lanzarlos es tildar "activo".
        $this->assertFalse($free->activo);
        $this->assertFalse($minimo->activo);
        $this->assertGreaterThan(2, Plan::where('key', 'basico')->value('orden'));
    }

    public function test_los_planes_que_ya_existian_no_pierden_nada(): void
    {
        // "Lo quiero" ya estaba en el chat de todos: la casilla nueva viene tildada.
        foreach (['basico', 'pro', 'premium'] as $key) {
            $plan = Plan::where('key', $key)->first();
            $this->assertTrue($plan->tieneFeature('lista_pedido'), $key);
            $this->assertTrue($plan->tieneFeature('sugerencias'), $key);
            $this->assertNull($plan->cartas_por_mes, "{$key} no tiene tope de cartas");
        }
    }

    public function test_el_admin_edita_los_topes_de_cartas_y_vacio_es_sin_limite(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $free = Plan::where('key', 'free')->first();

        $datos = [
            'nombre' => 'Free',
            'modelo_ia' => 'gpt-4o-mini',
            'max_tokens_chat' => 900,
            'mensajes_ia_mes' => 0,
            'cartas_primer_mes' => 5,
            'cartas_por_mes' => '',
            'features' => ['sugerencias'],
            'activo' => 1,
        ];

        $this->actingAs($admin)->put(route('admin.plans.update', $free), $datos)->assertRedirect();

        $free->refresh();
        $this->assertSame(5, $free->cartas_primer_mes);
        $this->assertNull($free->cartas_por_mes);
        // 0 mensajes sigue siendo 0 (sin asistente), no se confunde con vacío (ilimitado).
        $this->assertSame(0, $free->mensajes_ia_mes);
    }

    public function test_un_plan_sin_asistente_no_anuncia_ia_ilimitada(): void
    {
        // El 0 es falso en PHP: con `mensajes ? … : 'ilimitado'` la landing le iba a
        // anunciar IA ilimitada al plan gratis.
        $this->assertSame('Sin asistente con IA', Plan::where('key', 'free')->first()->cupoIaLegible());

        Plan::where('key', 'free')->update(['activo' => true]);   // lanzado

        $this->get('/')->assertOk()->assertSee('Sin asistente IA');
    }

    // ---- Sin asistente: directo a la carta ----

    public function test_sin_asistente_el_qr_entra_directo_a_la_carta(): void
    {
        $comercio = $this->comercioEn('free');

        $this->get(route('menu.public', $comercio))->assertRedirect(route('menu.classic', $comercio));
        $this->get(route('menu.chat', $comercio))->assertRedirect(route('menu.classic', $comercio));
    }

    public function test_con_asistente_sigue_la_portada_de_siempre(): void
    {
        $comercio = $this->comercioEn('pro');

        $this->get(route('menu.public', $comercio))->assertOk();
    }

    public function test_si_el_comercio_apaga_el_asistente_tambien_va_a_la_carta(): void
    {
        $comercio = $this->comercioEn('premium', ['enable_ai_responses' => false]);

        $this->get(route('menu.public', $comercio))->assertRedirect(route('menu.classic', $comercio));
    }

    // ---- Tope de cartas ----

    public function test_el_primer_mes_puede_procesar_tres_cartas(): void
    {
        $comercio = $this->comercioEn('free');

        $this->assertSame(3, $comercio->cartasRestantes());

        $this->procesadas($comercio, 3);

        $this->assertSame(0, $comercio->cartasRestantes());
        $this->assertFalse($comercio->puedeProcesarCarta());
    }

    public function test_despues_del_primer_mes_es_una_por_mes(): void
    {
        $comercio = $this->comercioEn('free');
        $this->procesadas($comercio, 3);

        // Pasado el primer período, lo usado en la prueba no se descuenta del mes siguiente.
        $this->travel(31)->days();

        $this->assertSame(1, $comercio->cartasRestantes());

        $this->procesadas($comercio, 1);
        $this->assertFalse($comercio->puedeProcesarCarta());
    }

    public function test_lo_que_no_cuenta_para_el_tope_no_se_descuenta(): void
    {
        $comercio = $this->comercioEn('free');

        // Las sugerencias que se generan solas al procesar la carta, o lo que hace el admin.
        $this->procesadas($comercio, 5, cuenta: false);

        $this->assertSame(3, $comercio->cartasRestantes());
    }

    public function test_los_planes_sin_tope_procesan_sin_limite(): void
    {
        $comercio = $this->comercioEn('pro');
        $this->procesadas($comercio, 20);

        $this->assertNull($comercio->cartasRestantes());
        $this->assertTrue($comercio->puedeProcesarCarta());
    }

    public function test_al_tope_no_deja_subir_otra_carta_y_dice_cuando_se_renueva(): void
    {
        $comercio = $this->comercioEn('free');
        $this->procesadas($comercio, 3);

        $this->actingAs($comercio->user)
            ->post(route('menus.store', $comercio), [])
            ->assertRedirect()
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Se renueva el'));
    }
}
