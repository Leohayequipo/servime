<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Slug que un comercio tuvo antes.
 *
 * Existe para que los QR impresos con una URL vieja sigan llevando al menú
 * después de un renombrado.
 */
class ComercioSlug extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['comercio_id', 'slug'];

    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }
}
