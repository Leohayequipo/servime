<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un ítem pedido dentro de una ronda de la comanda.
 */
class ComandaItem extends Model
{
    use HasFactory;

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
}
