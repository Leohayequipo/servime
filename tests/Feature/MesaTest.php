<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\MesaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las mesas se definen por cantidad ("tengo 12"), no una por una.
 *
 * Lo que hay que proteger es que bajar el número NO borre: detrás de cada mesa hay
 * historial de analytics y un cartel impreso dando vueltas por el salón.
 */
class MesaTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): MesaService
    {
        return app(MesaService::class);
    }

    public function test_sincronizar_crea_las_mesas_numeradas_de_uno_a_n(): void
    {
        $comercio = Comercio::factory()->create();

        $resultado = $this->servicio()->sincronizar($comercio, 12);

        $this->assertSame(12, $resultado['creadas']);
        $this->assertSame(12, $comercio->mesas()->count());
        $this->assertSame(
            range(1, 12),
            $comercio->mesas()->pluck('numero')->all()
        );
    }

    public function test_cada_mesa_recibe_un_token_distinto(): void
    {
        $comercio = Comercio::factory()->create();

        $this->servicio()->sincronizar($comercio, 20);

        $tokens = $comercio->mesas()->pluck('token');

        $this->assertCount(20, $tokens->unique());
        $tokens->each(fn ($token) => $this->assertSame(8, strlen($token)));
    }

    public function test_bajar_la_cantidad_desactiva_pero_no_borra(): void
    {
        $comercio = Comercio::factory()->create();
        $this->servicio()->sincronizar($comercio, 12);
        $tokenMesa10 = $comercio->mesas()->where('numero', 10)->value('token');

        $resultado = $this->servicio()->sincronizar($comercio, 8);

        $this->assertSame(4, $resultado['desactivadas']);
        // Siguen en la base: el histórico de esas mesas no se pierde.
        $this->assertSame(12, Mesa::where('comercio_id', $comercio->id)->count());
        $this->assertSame(8, Mesa::where('comercio_id', $comercio->id)->activas()->count());
        $this->assertDatabaseHas('mesas', ['token' => $tokenMesa10, 'activa' => false]);
    }

    public function test_volver_a_subir_la_cantidad_reactiva_con_el_token_original(): void
    {
        $comercio = Comercio::factory()->create();
        $this->servicio()->sincronizar($comercio, 12);
        $tokenOriginal = $comercio->mesas()->where('numero', 10)->value('token');

        $this->servicio()->sincronizar($comercio, 8);
        $resultado = $this->servicio()->sincronizar($comercio, 12);

        // Reactivadas, no creadas: los carteles ya impresos siguen sirviendo.
        $this->assertSame(0, $resultado['creadas']);
        $this->assertSame(4, $resultado['reactivadas']);
        $this->assertSame(
            $tokenOriginal,
            $comercio->mesas()->where('numero', 10)->value('token')
        );
    }

    public function test_sincronizar_dos_veces_con_el_mismo_numero_no_cambia_nada(): void
    {
        $comercio = Comercio::factory()->create();
        $this->servicio()->sincronizar($comercio, 10);

        $resultado = $this->servicio()->sincronizar($comercio, 10);

        $this->assertSame(
            ['creadas' => 0, 'reactivadas' => 0, 'desactivadas' => 0],
            $resultado
        );
        $this->assertSame(10, $comercio->mesas()->count());
    }

    public function test_las_mesas_de_un_comercio_no_chocan_con_las_de_otro(): void
    {
        $uno = Comercio::factory()->create();
        $otro = Comercio::factory()->create();

        $this->servicio()->sincronizar($uno, 5);
        $this->servicio()->sincronizar($otro, 5);

        // Mismo número, comercios distintos: el unique es compuesto.
        $this->assertSame(5, $uno->mesas()->count());
        $this->assertSame(5, $otro->mesas()->count());
        $this->assertNotSame(
            $uno->mesas()->where('numero', 1)->value('token'),
            $otro->mesas()->where('numero', 1)->value('token')
        );
    }

    public function test_rotar_el_token_invalida_el_anterior_y_conserva_el_numero(): void
    {
        $comercio = Comercio::factory()->create();
        $this->servicio()->sincronizar($comercio, 3);
        $mesa = $comercio->mesas()->where('numero', 2)->first();
        $tokenViejo = $mesa->token;

        $this->servicio()->rotarToken($mesa);

        $this->assertNotSame($tokenViejo, $mesa->fresh()->token);
        $this->assertSame(2, $mesa->fresh()->numero);
        $this->assertDatabaseMissing('mesas', ['token' => $tokenViejo]);
    }

    public function test_la_cantidad_se_topea_para_que_un_typo_no_genere_miles_de_mesas(): void
    {
        $comercio = Comercio::factory()->create();

        $this->servicio()->sincronizar($comercio, 99999);

        $this->assertSame(MesaService::MAX_MESAS, $comercio->mesas()->count());
    }

    public function test_la_url_publica_lleva_el_token_y_no_el_numero(): void
    {
        $comercio = Comercio::factory()->create(['slug' => 'lo-de-tito']);
        $this->servicio()->sincronizar($comercio, 7);
        $mesa = $comercio->mesas()->where('numero', 7)->first();

        $url = $mesa->urlPublica();

        $this->assertStringContainsString('/menu/lo-de-tito', $url);
        $this->assertStringContainsString('?m='.$mesa->token, $url);
        $this->assertStringNotContainsString('mesa=7', $url);
    }
}
