<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConversation extends Model
{
    protected $fillable = [
        'comercio_id',
        'mesa_id',
        'session_id',
        'user_message',
        'origen',
        'idioma',
        'bot_response',
        'platos_sugeridos',
        'platos_ofrecidos',
        'falta',
        'used_openai',
        'intent',
        'intent_source',
        'response_time_ms',
        'user_ip',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'used_openai' => 'boolean',
        'platos_ofrecidos' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con comercio
     */
    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }

    /**
     * Scope para conversaciones de un comercio
     */
    public function scopeForComercio($query, $comercioId)
    {
        return $query->where('comercio_id', $comercioId);
    }

    /**
     * Scope para conversaciones de una sesión
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope para conversaciones que usaron OpenAI
     */
    public function scopeUsedOpenAI($query)
    {
        return $query->where('used_openai', true);
    }

    /**
     * Scope para conversaciones recientes
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Horas sin escribir que cierran una visita. Pasado eso, el chat arranca de cero.
     *
     * El `session_id` vive en el localStorage del celular y no vence nunca, así que sin
     * corte el que vuelve a la semana siguiente vería (y el asistente recordaría) lo que
     * charló la vez anterior. Tres horas cubren una comida larga con sobremesa.
     */
    public const HORAS_VISITA = 3;

    /**
     * Los mensajes de la visita en curso de este dispositivo, en orden cronológico.
     *
     * La visita se arma hacia atrás desde ahora: cada mensaje entra si está a menos de
     * HORAS_VISITA del siguiente (o de ahora, el último). Así una charla que arrancó hace
     * cuatro horas pero sigue activa no se corta a la mitad.
     *
     * La usan las DOS puntas a propósito: lo que el cliente ve al volver a entrar y lo
     * que el asistente recuerda. Si no coinciden, el modelo contesta "como te dije" sobre
     * algo que el cliente ya no tiene en pantalla.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function deLaVisita(int $comercioId, string $sessionId, int $limite): \Illuminate\Support\Collection
    {
        $filas = self::where('comercio_id', $comercioId)
            ->where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        $visita = collect();
        $corte = now();
        foreach ($filas as $fila) {
            if ($fila->created_at->lt($corte->copy()->subHours(self::HORAS_VISITA))) {
                break;
            }
            $visita->push($fila);
            $corte = $fila->created_at;
        }

        return $visita->reverse()->values();
    }

    /**
     * Solo lo que TIPEÓ el cliente, sin los mensajes que arma la interfaz al tocar
     * "Ver más opciones" o un chip de categoría.
     *
     * Usalo en todo lo que analice el TEXTO del mensaje (palabras, preguntas frecuentes,
     * intención): esos mensajes sintéticos son idénticos entre sí y dominan cualquier
     * conteo. Para métricas de volumen (total de consultas, tiempo de respuesta) NO hay
     * que filtrar: son interacciones reales del cliente.
     */
    public function scopeEscritas($query)
    {
        return $query->where('origen', 'escrito');
    }
}
