<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'comercio_id',
        'mesa_id',
        'session_id',
        'event',
        'data',
        'user_ip',
        'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }

    public function scopeForComercio($query, $comercioId)
    {
        return $query->where('comercio_id', $comercioId);
    }

    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
