<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lo que pidió una mesa desde que se sentó el grupo hasta que se fue.
 *
 * Ver la migración para por qué existe este nivel por encima de `carts` y por qué
 * cerrar no es borrar.
 */
class Comanda extends Model
{
    use HasFactory;

    public const ABIERTA = 'abierta';

    public const CERRADA = 'cerrada';

    protected $fillable = [
        'comercio_id',
        'mesa_id',
        'estado',
        'total_centavos',
        'abierta_at',
        'cerrada_at',
        'cerrada_por',
        'mesa_abierta_id',
    ];

    protected $casts = [
        'total_centavos' => 'integer',
        'abierta_at' => 'datetime',
        'cerrada_at' => 'datetime',
    ];

    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComandaItem::class)->orderBy('ronda')->orderBy('id');
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->where('estado', self::ABIERTA);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === self::ABIERTA;
    }

    /** Número de la próxima ronda. La primera es la 1. */
    public function proximaRonda(): int
    {
        return (int) $this->items()->max('ronda') + 1;
    }

    /**
     * Ítems agrupados por ronda, para que el mozo vea qué entró y cuándo.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, ComandaItem>>
     */
    public function rondas(): \Illuminate\Support\Collection
    {
        return $this->items->groupBy('ronda');
    }

    /**
     * ¿Todavía se puede deshacer el cierre?
     *
     * Sólo un rato: pasada la ventana no es un error del mozo, es un grupo que se fue, y
     * reabrir mezclaría dos servicios en el mismo registro.
     */
    public function sePuedeReabrir(): bool
    {
        if ($this->estaAbierta() || ! $this->cerrada_at) {
            return false;
        }

        return $this->cerrada_at->greaterThan(
            now()->subMinutes(\App\Services\ComandaService::MINUTOS_PARA_REABRIR)
        );
    }

    /** Cuánto duró (o lleva) el servicio de esta mesa, en minutos. */
    public function minutosDeServicio(): int
    {
        return (int) max(0, $this->abierta_at->diffInMinutes($this->cerrada_at ?? now()));
    }

    /** Hace cuánto entró el último pedido. Es lo que le dice al mozo a qué mesa ir. */
    public function minutosDesdeElUltimoPedido(): int
    {
        $ultimo = $this->items->max('created_at') ?? $this->abierta_at;

        return (int) max(0, $ultimo->diffInMinutes(now()));
    }
}
