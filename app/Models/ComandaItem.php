<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un ítem pedido dentro de una ronda de la comanda.
 *
 * Con SoftDeletes: lo que el mozo borra deja rastro, para poder resolver un reclamo o
 * deshacer un toque en la fila equivocada. Ver la migración.
 */
class ComandaItem extends Model
{
    use HasFactory, SoftDeletes;

    public const ORIGEN_CLIENTE = 'cliente';

    public const ORIGEN_MOZO = 'mozo';

    protected $fillable = [
        'comanda_id',
        'ronda',
        'nombre',
        'qty',
        'price_centavos',
        'price_text',
        'origen',
        'session_id',
        'nota',
    ];

    protected $casts = [
        'ronda' => 'integer',
        'qty' => 'integer',
        'price_centavos' => 'integer',
    ];

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function subtotalCentavos(): int
    {
        return (int) ($this->price_centavos ?? 0) * $this->qty;
    }

    public function loAgregoElMozo(): bool
    {
        return $this->origen === self::ORIGEN_MOZO;
    }

    /**
     * Los ítems borrados de un comercio, del más reciente al más viejo.
     *
     * La consulta va SIEMPRE por acá y no a mano: `comanda_items` no tiene `comercio_id`
     * propio —cuelga de `comandas`— así que un `where` descuidado sobre esta tabla mezcla
     * el histórico de todos los locales. Este scope obliga a pasar el comercio.
     */
    public function scopeBorradosDe(Builder $query, Comercio $comercio, int $dias = 30): Builder
    {
        return $query
            ->onlyTrashed()
            ->whereHas('comanda', fn ($q) => $q->where('comercio_id', $comercio->id))
            ->where('deleted_at', '>=', now()->subDays($dias))
            ->latest('deleted_at');
    }
}
