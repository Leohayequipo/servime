<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LandingChatContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_prompt_del_landing_incluye_el_contacto_real_y_prohibe_inventar(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.support.email' => 'info.servime@gmail.com',
            'services.support.whatsapp' => '542257526706',
        ]);

        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Escribinos por email.']]],
        ], 200)]);

        $this->postJson('/api/landing-chat', [
            'message' => '¿Cómo los contacto?',
            'session_id' => 'sess-landing-1',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $system = collect($request->data()['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return str_contains($system, 'info.servime@gmail.com')
                && str_contains($system, '542257526706')
                && str_contains($system, 'NUNCA inventes');
        });
    }
}
