<?php

namespace Tests\Feature;

use App\Jobs\ProcessMenuJob;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\AsistenteApagado;
use App\Notifications\CuotaIaAgotada;
use App\Notifications\CuotaIaPorAgotarse;
use App\Notifications\MenuConError;
use App\Notifications\MenuSinPublicar;
use App\Notifications\PlatosSinPrecio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * La campanita del panel: qué avisa, que no repita y que cada uno vea sólo lo suyo.
 */
class AvisosTest extends TestCase
{
    use RefreshDatabase;

    /** Comercio con un cupo de IA chico, para cruzar los umbrales en pocas llamadas. */
    private function comercioConCupo(int $cupo, ?User $user = null): Comercio
    {
        $user ??= User::factory()->create(['role' => 'user']);
        $plan = Plan::where('key', 'basico')->first();
        $plan->update(['mensajes_ia_mes' => $cupo]);

        return Comercio::factory()->for($user)->create(['plan_id' => $plan->id]);
    }

    private function avisos(string $clase): int
    {
        return DB::table('notifications')->where('type', $clase)->count();
    }

    public function test_avisa_una_sola_vez_al_quedarse_sin_cupo_cerca(): void
    {
        $comercio = $this->comercioConCupo(10);

        // Del 8º mensaje en adelante está en el 80%: el aviso sale una vez, no en cada consulta.
        for ($i = 0; $i < 9; $i++) {
            $comercio->registrarUsoIA();
        }

        $this->assertSame(1, $this->avisos(CuotaIaPorAgotarse::class));
        $this->assertSame(0, $this->avisos(CuotaIaAgotada::class));
    }

    public function test_avisa_cuando_se_acaba_el_cupo_y_tambien_por_mail(): void
    {
        $comercio = $this->comercioConCupo(5);

        for ($i = 0; $i < 7; $i++) {
            $comercio->registrarUsoIA();
        }

        $this->assertSame(1, $this->avisos(CuotaIaAgotada::class));

        // Y el mail que acompaña compila (es HTML propio, no la plantilla de Laravel).
        $mail = (new CuotaIaAgotada($comercio, 5, now()->format('Y-m')))->toMail($comercio->user);
        $this->assertNotEmpty(view($mail->view, $mail->viewData)->render());
        $this->assertContains('mail', (new CuotaIaAgotada($comercio, 5, now()->format('Y-m')))->via($comercio->user));
    }

    public function test_el_cupo_del_mes_nuevo_vuelve_a_avisar(): void
    {
        $comercio = $this->comercioConCupo(5);

        for ($i = 0; $i < 6; $i++) {
            $comercio->registrarUsoIA();
        }

        // Mes siguiente: el contador se reinicia solo y el aviso tiene que poder repetirse.
        $this->travel(1)->months();
        for ($i = 0; $i < 6; $i++) {
            $comercio->registrarUsoIA();
        }

        $this->assertSame(2, $this->avisos(CuotaIaAgotada::class));
    }

    public function test_un_plan_ilimitado_no_avisa_nada(): void
    {
        $comercio = $this->comercioConCupo(5);
        $comercio->planEfectivo()->update(['mensajes_ia_mes' => null]);
        $comercio->refresh()->load('plan');

        for ($i = 0; $i < 20; $i++) {
            $comercio->registrarUsoIA();
        }

        $this->assertSame(0, $this->avisos(CuotaIaPorAgotarse::class));
        $this->assertSame(0, $this->avisos(CuotaIaAgotada::class));
    }

    public function test_el_menu_que_falla_avisa_en_la_campanita(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->for($user)->create();
        $menu = Menu::factory()->for($comercio)->create(['estado' => Menu::ESTADO_PROCESANDO]);

        (new ProcessMenuJob($menu))->failed(new \RuntimeException('se rompió'));

        Notification::assertSentTo($user, MenuConError::class);
        $this->assertSame(Menu::ESTADO_ERROR, $menu->refresh()->estado);
    }

    public function test_la_campanita_muestra_los_avisos_y_se_pueden_marcar_leidos(): void
    {
        $comercio = $this->comercioConCupo(5);
        $user = $comercio->user;

        for ($i = 0; $i < 6; $i++) {
            $comercio->registrarUsoIA();
        }

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('se quedó sin cupo del asistente', false);

        $this->post(route('avisos.leidos'))->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_abrir_un_aviso_lo_marca_leido_y_lleva_a_donde_se_resuelve(): void
    {
        $comercio = $this->comercioConCupo(5);
        $user = $comercio->user;

        for ($i = 0; $i < 6; $i++) {
            $comercio->registrarUsoIA();
        }

        $aviso = $user->unreadNotifications()->first();

        $this->actingAs($user)->get(route('avisos.abrir', $aviso->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->notifications()->find($aviso->id)->read_at);
    }

    public function test_nadie_abre_el_aviso_de_otro(): void
    {
        $comercio = $this->comercioConCupo(5);

        for ($i = 0; $i < 6; $i++) {
            $comercio->registrarUsoIA();
        }

        $aviso = $comercio->user->unreadNotifications()->first();
        $otro = User::factory()->create(['role' => 'user']);

        $this->actingAs($otro)->get(route('avisos.abrir', $aviso->id))->assertNotFound();
        $this->assertNull($aviso->refresh()->read_at);
    }

    // ---- Avisos del estado de la cuenta (comando diario) ----

    public function test_avisa_la_carta_lista_que_quedo_sin_publicar(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->for($user)->create();
        $menu = Menu::factory()->for($comercio)->create(['activo' => false]);

        // Recién procesada no se avisa: puede estar por activarla en ese mismo momento.
        $this->artisan('avisos:revisar')->assertSuccessful();
        $this->assertSame(0, $this->avisos(MenuSinPublicar::class));

        $this->travel(2)->days();
        $this->artisan('avisos:revisar')->assertSuccessful();
        $this->artisan('avisos:revisar')->assertSuccessful();

        // Una sola vez, aunque el cron corra todos los días con el mismo estado.
        $this->assertSame(1, $this->avisos(MenuSinPublicar::class));
        $this->assertStringContainsString($menu->nombre, $user->unreadNotifications()->first()->data['detalle']);
    }

    public function test_no_avisa_si_ya_tiene_una_carta_publicada(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->for($user)->create();
        Menu::factory()->for($comercio)->create(['activo' => true]);
        // Una carta vieja apagada no es un problema.
        Menu::factory()->for($comercio)->create(['activo' => false]);

        $this->travel(2)->days();
        $this->artisan('avisos:revisar')->assertSuccessful();

        $this->assertSame(0, $this->avisos(MenuSinPublicar::class));
    }

    public function test_avisa_el_asistente_apagado_solo_con_la_carta_publicada(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $sinCarta = Comercio::factory()->for($user)->create(['enable_ai_responses' => false]);
        $publicado = Comercio::factory()->for($user)->create(['enable_ai_responses' => false]);
        Menu::factory()->for($publicado)->create(['activo' => true]);

        $this->artisan('avisos:revisar')->assertSuccessful();

        $this->assertSame(1, $this->avisos(AsistenteApagado::class));
        $this->assertSame(
            $publicado->id,
            $user->unreadNotifications()->get()
                ->firstWhere('type', AsistenteApagado::class)->data['comercio_id']
        );
        $this->assertNotSame($sinCarta->id, $publicado->id);
    }

    // ---- Calidad de lo que procesó la IA ----

    public function test_avisa_cuantos_platos_quedaron_sin_precio(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user']);
        $comercio = Comercio::factory()->for($user)->create();
        $menu = Menu::factory()->for($comercio)->create([
            'contexto_gpt' => json_encode(['categorias' => [[
                'nombre' => 'Principales',
                'platos' => [
                    ['nombre' => 'Milanesa', 'precio' => '$12000'],
                    ['nombre' => 'Rabas', 'precio' => ''],
                    ['nombre' => 'Flan'],
                ],
            ]]]),
        ]);

        $this->assertSame(2, $menu->platosSinPrecio());

        $user->notify(new PlatosSinPrecio($menu, $menu->platosSinPrecio()));

        Notification::assertSentTo($user, PlatosSinPrecio::class, function ($n) use ($user) {
            return str_contains($n->toArray($user)['titulo'], '2 platos');
        });
    }

    public function test_un_menu_con_json_roto_no_rompe_el_conteo(): void
    {
        $menu = Menu::factory()->create(['contexto_gpt' => 'no soy json']);

        $this->assertSame([], $menu->platos());
        $this->assertSame(0, $menu->platosSinPrecio());
    }

    public function test_el_super_admin_no_tiene_campanita(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="campanita"', false);
    }
}
