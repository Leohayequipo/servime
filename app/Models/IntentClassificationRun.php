<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentClassificationRun extends Model
{
    protected $fillable = [
        'comercio_id',
        'mensajes_procesados',
        'modelo',
    ];

    protected $casts = [
        'mensajes_procesados' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }
}
