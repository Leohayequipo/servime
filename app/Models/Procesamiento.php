<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una corrida de IA sobre una carta: leerla (tipo `carta`) o armar sus sugerencias
 * (tipo `sugerencias`). Sirve para el tope de cartas del plan y para que el super admin
 * vea cuánto consumió cada una. Ver la migración que la crea.
 */
class Procesamiento extends Model
{
    public const TIPO_CARTA = 'carta';

    public const TIPO_SUGERENCIAS = 'sugerencias';

    public const ORIGEN_COMERCIO = 'comercio';

    public const ORIGEN_AUTOMATICO = 'automatico';

    public const ORIGEN_ADMIN = 'admin';

    protected $fillable = [
        'comercio_id',
        'menu_id',
        'tipo',
        'origen',
        'cuenta_para_limite',
        'modelo',
        'tokens_entrada',
        'tokens_salida',
        'tokens_razonamiento',
        'segundos',
    ];

    protected $casts = [
        'cuenta_para_limite' => 'boolean',
        'tokens_entrada' => 'integer',
        'tokens_salida' => 'integer',
        'tokens_razonamiento' => 'integer',
        'segundos' => 'integer',
    ];

    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
