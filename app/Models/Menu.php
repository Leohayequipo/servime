<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comercio_id',
        'nombre',
        'archivo_original',
        'texto_extraido',
        'contexto_gpt',
        'formato',
        'procesado',
        'estado',
        'activo',
    ];

    protected $casts = [
        'procesado' => 'boolean',
        'activo' => 'boolean',
    ];

    // Estados de procesamiento del menú
    public const ESTADO_PROCESANDO = 'procesando';

    public const ESTADO_LISTO = 'listo';

    public const ESTADO_ERROR = 'error';

    public function comercio()
    {
        return $this->belongsTo(Comercio::class);
    }

    public function estaListo(): bool
    {
        return $this->estado === self::ESTADO_LISTO;
    }

    public function tuvoError(): bool
    {
        return $this->estado === self::ESTADO_ERROR;
    }
}
