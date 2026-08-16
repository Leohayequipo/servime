<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * Modelos que ofrece el panel, en orden de recomendación.
     *
     * `gpt-5.6-luna` va primero porque salió MÁS BARATO que el mini en la medición
     * (~$3,61-4,05 contra ~$4,15-5,25 por mil charlas) y además acierta 3/3 en consultas
     * de dietas, donde el mini acierta 0/3. Detalle en la skill `chatbot`.
     *
     * La lista NO es una validación: el campo acepta cualquier string, porque un modelo
     * nuevo se puede cargar por SQL antes de que exista acá. Ver `modelosParaElegir()`.
     */
    public const MODELOS_IA = [
        'gpt-5.6-luna',
        'gpt-4o-mini',
        'gpt-4o',
    ];

    /**
     * Los modelos del desplegable, con el que ya tiene el plan siempre incluido.
     *
     * Sin esto, un plan con un modelo cargado por SQL que no esté en la lista muestra el
     * desplegable posicionado en la primera opción, y guardar cualquier OTRO campo del
     * plan se lo cambia en silencio. Se descubre cuando la factura no baja.
     *
     * @return array<int, string>
     */
    public function modelosParaElegir(): array
    {
        $modelos = self::MODELOS_IA;

        if (filled($this->modelo_ia) && ! in_array($this->modelo_ia, $modelos, true)) {
            array_unshift($modelos, $this->modelo_ia);
        }

        return $modelos;
    }

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
