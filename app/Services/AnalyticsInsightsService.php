<?php

namespace App\Services;

use App\Models\ChatConversation;

/**
 * Insights derivados del chat: intención de las consultas (rule-based) y
 * sugerencias accionables para el comercio. Sin costo de IA.
 */
class AnalyticsInsightsService
{
    /**
     * Categorías de intención -> palabras clave (normalizadas, sin acentos).
     */
    private const INTENTS = [
        'Precio' => ['precio', 'precios', 'cuesta', 'cuestan', 'vale', 'valen', 'cuanto', 'cuanta', 'valor', 'sale', 'salen'],
        'Horario' => ['horario', 'horarios', 'abren', 'cierran', 'abierto', 'cerrado', 'hora', 'atienden', 'atencion'],
        'Envío / Delivery' => ['envio', 'envios', 'delivery', 'domicilio', 'reparto', 'llevan', 'envian', 'mandan'],
        'Reservas' => ['reserva', 'reservas', 'reservar', 'mesa', 'mesas', 'turno', 'turnos'],
        'Medios de pago' => ['tarjeta', 'efectivo', 'mercadopago', 'transferencia', 'debito', 'credito', 'pago', 'pagar', 'pagos'],
        'Dieta / Restricciones' => ['tacc', 'celiaco', 'celiaca', 'celiacos', 'vegano', 'vegana', 'veganos', 'vegetariano', 'vegetariana', 'gluten', 'lactosa', 'diabetico', 'apto', 'aptos'],
        'Recomendación' => ['recomenda', 'recomienda', 'recomiendan', 'sugeri', 'sugiere', 'conviene', 'mejor', 'rico', 'rica'],
        'Ubicación' => ['donde', 'direccion', 'ubicados', 'ubicacion', 'llego', 'mapa', 'quedan', 'estan'],
    ];

    /**
     * Categorías válidas de intención (para el clasificador con IA).
     *
     * @return array<int, string>
     */
    public static function categorias(): array
    {
        return [...array_keys(self::INTENTS), 'Otras'];
    }

    /**
     * Distribución de intención de los mensajes (últimos 30 días).
     */
    public function intents($comercioId): array
    {
        // Solo lo que tipeó el cliente: los atajos de la interfaz mandan un texto armado
        // que no representa una intención propia y sesgaba el conteo.
        $convs = ChatConversation::forComercio($comercioId)
            ->recent(30)
            ->escritas()
            ->get(['user_message', 'intent']);

        $counts = array_fill_keys(self::categorias(), 0);
        $total = 0;
        $ia = 0; // cuántos fueron clasificados con IA (intent guardado)

        foreach ($convs as $c) {
            if (empty($c->user_message)) {
                continue;
            }
            $total++;

            // Si ya tiene intención guardada (IA) la usamos; si no, rule-based en vivo.
            if (! empty($c->intent) && isset($counts[$c->intent])) {
                $counts[$c->intent]++;
                $ia++;
            } else {
                $counts[$this->ruleIntent($this->normalizar((string) $c->user_message))]++;
            }
        }

        arsort($counts);

        return ['total' => $total, 'counts' => $counts, 'ia' => $ia];
    }

    /**
     * Intención rule-based: primera categoría cuyas keywords matchean (una por mensaje).
     */
    private function ruleIntent(string $norm): string
    {
        foreach (self::INTENTS as $name => $kws) {
            if (preg_match('/\b('.implode('|', $kws).')\b/', $norm)) {
                return $name;
            }
        }

        return 'Otras';
    }

    /**
     * Sugerencias accionables a partir de la intención + métricas de comportamiento.
     */
    public function suggestions($comercioId, array $metrics, array $intents): array
    {
        $sug = [];
        $total = $intents['total'];
        $counts = $intents['counts'];
        $umbral = max(3, (int) ceil($total * 0.15));

        // Intenciones con demanda -> aclarar esa info en el menú
        $mensajes = [
            'Envío / Delivery' => 'Varios clientes preguntan por envíos/delivery. Aclará si hacés y la zona de reparto.',
            'Horario' => 'Te consultan seguido por el horario. Conviene tenerlo bien visible.',
            'Medios de pago' => 'Hay consultas por medios de pago. Aclará qué aceptás (tarjeta, efectivo, transferencia).',
            'Reservas' => 'Te preguntan por reservas/mesas. Mostrá cómo reservar.',
            'Ubicación' => 'Consultan por la ubicación. Sumá dirección y cómo llegar.',
            'Dieta / Restricciones' => 'Hay demanda de opciones aptas (sin TACC, vegano, etc.). Destacalas en el menú.',
        ];
        foreach ($mensajes as $intent => $texto) {
            if (($counts[$intent] ?? 0) >= $umbral) {
                $sug[] = ['icon' => '💡', 'text' => $texto];
            }
        }

        // Calidad del asistente (últimos 30 días)
        if ($total >= 8) {
            $base = ChatConversation::forComercio($comercioId)->recent(30);
            $tot = (clone $base)->count();
            $fb = (clone $base)->where('used_openai', false)->count();
            if ($tot > 0 && $fb / $tot >= 0.25) {
                $sug[] = ['icon' => '⚠️', 'text' => 'El asistente no pudo responder el '.round($fb / $tot * 100).'% de las consultas. Revisá que el menú y los datos del comercio estén completos.'];
            }
            $avg = (clone $base)->whereNotNull('response_time_ms')->avg('response_time_ms');
            if ($avg && $avg > 6000) {
                $sug[] = ['icon' => '🐢', 'text' => 'El tiempo de respuesta del asistente es alto ('.round($avg / 1000, 1).'s).'];
            }
        }

        // Embudo: entran pero no eligen
        $f = $metrics['funnel'];
        if ($f['visitas'] >= 10 && $f['eligieron'] / max(1, $f['visitas']) < 0.5) {
            $sug[] = ['icon' => '🔻', 'text' => 'Muchos entran al menú pero pocos eligen una opción. Revisá la pantalla inicial (IA vs clásico).'];
        }

        // Promos con clicks pero sin interés en pedir
        if ($metrics['topPromos']->isNotEmpty() && $f['interesados'] === 0) {
            $sug[] = ['icon' => '🔥', 'text' => 'Tus promos reciben clicks pero nadie llegó a "Lo quiero". Quizás la oferta o el precio no terminan de cerrar.'];
        }

        return $sug;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return (string) preg_replace('/[^a-z0-9 ]+/', ' ', $texto);
    }
}
