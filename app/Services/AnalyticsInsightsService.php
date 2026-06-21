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
     * Distribución de intención de los mensajes (últimos 30 días).
     */
    public function intents($comercioId): array
    {
        $mensajes = ChatConversation::forComercio($comercioId)
            ->recent(30)
            ->pluck('user_message')
            ->filter();

        $counts = array_fill_keys(array_keys(self::INTENTS), 0);
        $counts['Otras'] = 0;
        $total = 0;

        $regex = [];
        foreach (self::INTENTS as $name => $kws) {
            $regex[$name] = '/\b('.implode('|', $kws).')\b/';
        }

        foreach ($mensajes as $msg) {
            $total++;
            $norm = $this->normalizar((string) $msg);
            $matched = false;
            foreach ($regex as $name => $rx) {
                if (preg_match($rx, $norm)) {
                    $counts[$name]++;
                    $matched = true;
                }
            }
            if (! $matched) {
                $counts['Otras']++;
            }
        }

        arsort($counts);

        return ['total' => $total, 'counts' => $counts];
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
