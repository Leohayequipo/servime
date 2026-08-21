<?php

namespace App\Http\Controllers;

use App\Models\ChatLanding;
use App\Models\Plan;
use App\Support\ArmaPayloadOpenAI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LandingChatController extends Controller
{
    use ArmaPayloadOpenAI;

    private const BASE_PROMPT = <<<'PROMPT'
Sos el asistente de ventas de ServiME, un SaaS argentino de menús digitales con IA para bares y restaurantes.

Tu objetivo es ayudar a dueños de gastronomía a entender el producto, resolver sus dudas y motivarlos a dar el paso. Sos cercano, buena onda y con foco en tecnología — como alguien del equipo que entiende tanto de producto como de gastronomía.

## Qué es ServiME
ServiME convierte el menú físico de un bar o restaurante en una experiencia digital con IA. El dueño sube su carta (PDF o fotos) y el sistema la procesa automáticamente. Los clientes acceden escaneando un QR desde su celular — sin instalar ninguna app.

## Funcionalidades para el comensal
- Acceso al menú desde el celular con QR, sin instalar nada
- Chatbot con IA entrenado específicamente con el menú del local: platos, precios, categorías, tags y promos
- El bot recomienda según lo que pide el cliente ("algo vegetariano", "sin picante", "sin TACC")
- Pre-pedido con total estimado para mostrar o compartir al camarero
- Fotos y descripciones de platos (si el menú las incluye)
- Categorías automáticas con íconos (entradas, principales, postres, bebidas, etc.)
- Tags de filtrado: vegano, sin gluten, sin TACC, etc.
- Promociones del día destacadas con 🔥

## Funcionalidades para el dueño (panel de gestión)
- Personalización completa: logo, colores (primario, secundario, acento, fondo), mensaje de bienvenida propio, nombre del chatbot propio
- Sube PDF o fotos del menú — la IA extrae todo automáticamente
- Múltiples versiones de menú guardadas; activás la que querés en cualquier momento
- Generador de QR del local listo para imprimir
- Analytics completo: conversaciones totales y del día, preguntas más frecuentes, estadísticas diarias de los últimos 7 días, tiempo promedio de respuesta del bot, exportación de conversaciones a CSV
- Control granular: activar/desactivar la IA y la función de pedido por separado
- Opción de mostrar u ocultar la info de contacto del negocio

## Argumentos de venta clave
- Sin fricción para el cliente: nada de apps, menú siempre actualizado en el celular del comensal
- Menos carga para el salón: el bot responde lo de siempre ("¿qué tiene sin gluten?", "¿cuál es la promo?") y los camareros se enfocan en atender
- IA entrenada con TU menú — no es un chatbot genérico, conoce exactamente lo que tiene el local
- Listo en minutos: subís el PDF de la carta, la IA hace el trabajo pesado
- Métricas reales: sabés qué pregunta la gente, qué platos generan más interés, cuántas interacciones tiene el menú
- Pre-pedido = más velocidad de rotación de mesa = más facturación para el local

## Reglas de comportamiento
- Respondés ÚNICAMENTE sobre ServiME, gastronomía digital y la industria gastronómica
- Si te preguntan algo completamente fuera de ese contexto, lo decís con humor y redirigís: "Eso escapa a mi especialidad, ¡pero en lo que es ServiME soy todo tuyo!"
- NUNCA inventás precios, funcionalidades ni garantías que no estén en este prompt
- Si no podés responder con certeza, ofrecés el contacto humano usando SOLO los datos de contacto reales que figuran más abajo (NUNCA inventes email, teléfono ni dominio)
- Respondés en español rioplatense (vos, che, etc.), tono cercano pero profesional
- Respuestas cortas y directas — máximo 3 párrafos
- Usá **negrita** para resaltar cosas importantes
- Usá saltos de línea para separar ideas, no listas con guión
- No uses headers con ## en tus respuestas
PROMPT;

    private const FEATURE_LABELS = [
        'analytics' => 'Analytics avanzado de consultas',
        'excel' => 'Exportar conversaciones a Excel',
        'promociones' => 'Gestión de promociones destacadas',
        'intencion_ia' => 'Clasificación de intención con IA',
    ];

    private function buildSystemPrompt(): string
    {
        $planes = Plan::where('activo', true)->where('orden', '>', 0)->orderBy('orden')->get();

        $planesText = "\n\n## Planes y precios\n";
        foreach ($planes as $plan) {
            $precio = $plan->precio ? '$'.number_format($plan->precio, 0, ',', '.').'/mes' : 'A consultar';
            $msgs = $plan->mensajes_ia_mes ? number_format($plan->mensajes_ia_mes).' mensajes IA por mes' : 'IA ilimitada';

            $planesText .= "\n### {$plan->nombre} — {$precio}\n";
            $planesText .= "{$plan->descripcion}\n";
            $planesText .= "- {$msgs}\n";

            foreach ($plan->features ?? [] as $feature) {
                $label = self::FEATURE_LABELS[$feature] ?? $feature;
                $planesText .= "- {$label}\n";
            }
        }

        return self::BASE_PROMPT.$planesText;
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'session_id' => 'required|string|max:64',
            'history' => 'array|max:20',
            'history.*.role' => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:2000',
        ]);

        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');

        if (empty($apiKey)) {
            return response()->json(['error' => 'Servicio no disponible'], 503);
        }

        $email = config('services.support.email');
        $whatsapp = config('services.support.whatsapp');

        $contacto = "\n\n## Datos de contacto reales (los ÚNICOS válidos)\n";
        if (! empty($email)) {
            $contacto .= "- Email: {$email}\n";
        }
        if (! empty($whatsapp)) {
            $contacto .= "- WhatsApp: https://wa.me/{$whatsapp}\n";
        }
        $contacto .= 'IMPORTANTE: estos son los ÚNICOS datos de contacto. NUNCA inventes otro email, teléfono o dominio. Si un dato no figura acá, no lo des e invitá a escribir al email.';

        $messages = [['role' => 'system', 'content' => $this->buildSystemPrompt().$contacto]];

        $history = array_slice($request->input('history', []), -10);
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $request->input('message')];

        // El body cambia según la familia del modelo: los GPT-5 rechazan con 400 los
        // parámetros de los GPT-4. Lo resuelve el trait, compartido con el chat del menú.
        $payload = $this->payloadOpenAI(
            config('services.openai.landing_model'),
            $messages,
            config('services.openai.landing_max_tokens'),
            false,
            // Se conservan por si se vuelve a un GPT-4: más bajos que en el asistente del
            // menú porque acá se responde sobre precios y funciones, no se recomiendan platos.
            ['temperature' => 0.5, 'presence_penalty' => 0.2, 'frequency_penalty' => 0.1],
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post($baseUrl.'/chat/completions', $payload);

            if ($response->successful()) {
                $text = trim($response->json('choices.0.message.content', ''));

                ChatLanding::create([
                    'session_id' => $request->input('session_id'),
                    'user_message' => $request->input('message'),
                    'bot_response' => $text,
                    'user_ip' => $request->ip(),
                ]);

                return response()->json(['response' => $text]);
            }

            Log::warning('LandingChat error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return response()->json(['response' => 'Hubo un problema al procesar tu consulta. Contactanos directamente.']);

        } catch (\Exception $e) {
            Log::error('LandingChat exception: '.$e->getMessage());

            return response()->json(['response' => 'Hubo un problema al procesar tu consulta. Contactanos directamente.']);
        }
    }
}
