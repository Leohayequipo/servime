<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConversation extends Model
{
    protected $fillable = [
        'comercio_id',
        'session_id',
        'user_message',
        'bot_response',
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
}
