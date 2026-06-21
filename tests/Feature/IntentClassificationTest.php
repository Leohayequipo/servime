<?php

namespace Tests\Feature;

use App\Jobs\ClassifyIntentsJob;
use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Plan;
use App\Models\User;
use App\Services\IntentClassifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntentClassificationTest extends TestCase
{
    use RefreshDatabase;

    private function conversacion(Comercio $comercio, string $msg): void
    {
        ChatConversation::create([
            'comercio_id' => $comercio->id,
            'session_id' => 'sess_'.uniqid(),
            'user_message' => $msg,
            'bot_response' => 'ok',
            'used_openai' => true,
            'response_time_ms' => 1000,
        ]);
    }

    public function test_el_servicio_clasifica_y_registra_la_corrida(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['categorias' => ['Horario', 'Precio']])]]],
            ], 200),
        ]);

        $comercio = Comercio::factory()->create();
        $this->conversacion($comercio, '¿Cuál es el horario?');
        $this->conversacion($comercio, '¿Cuánto cuesta?');

        $res = (new IntentClassifierService)->classifyPending($comercio);

        $this->assertSame(2, $res['procesados']);
        $this->assertSame(2, ChatConversation::whereNotNull('intent')->where('intent_source', 'ia')->count());
        $this->assertDatabaseHas('intent_classification_runs', [
            'comercio_id' => $comercio->id,
            'mensajes_procesados' => 2,
        ]);
    }

    public function test_sin_api_key_no_clasifica_ni_registra(): void
    {
        config(['services.openai.api_key' => null]);
        $comercio = Comercio::factory()->create();
        $this->conversacion($comercio, '¿Hacen envíos?');

        $res = (new IntentClassifierService)->classifyPending($comercio);

        $this->assertSame(0, $res['procesados']);
        $this->assertDatabaseCount('intent_classification_runs', 0);
    }

    public function test_reclassify_bloqueado_sin_la_feature(): void
    {
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'basico')->value('id'),
        ]);

        $this->actingAs($user)->post(route('analytics.reclassify', $comercio))->assertForbidden();
    }

    public function test_reclassify_encola_el_job_con_la_feature(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $comercio = Comercio::factory()->for($user)->create([
            'plan_id' => Plan::where('key', 'premium')->value('id'),
        ]);

        $this->actingAs($user)->post(route('analytics.reclassify', $comercio))->assertRedirect();

        Bus::assertDispatched(ClassifyIntentsJob::class);
    }
}
