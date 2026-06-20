<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'nombre',
        'descripcion',
        'precio',
        'mensajes_ia_mes',
        'modelo_ia',
        'max_tokens_chat',
        'features',
        'orden',
        'activo',
    ];

    protected $casts = [
        'features' => 'array',
        'activo' => 'boolean',
        'precio' => 'decimal:2',
        'mensajes_ia_mes' => 'integer',
        'max_tokens_chat' => 'integer',
        'orden' => 'integer',
    ];

    public function comercios()
    {
        return $this->hasMany(Comercio::class);
    }

    /**
     * Plan por defecto al que caen los comercios sin plan asignado.
     */
    public static function default(): ?self
    {
        return static::where('key', 'basico')->first()
            ?? static::orderBy('orden')->first();
    }

    /**
     * ¿La cuota de mensajes IA es ilimitada?
     */
    public function esIlimitado(): bool
    {
        return is_null($this->mensajes_ia_mes);
    }

    /**
     * ¿El plan incluye esta feature? (analytics, customizacion)
     */
    public function tieneFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }
}
