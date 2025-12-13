<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'comercio_id',
        'nombre',
        'archivo_original',
        'texto_extraido',
        'contexto_gpt',
        'formato',
        'procesado',
        'activo'
    ];

    protected $casts = [
        'procesado' => 'boolean',
        'activo' => 'boolean'
    ];

    public function comercio()
    {
        return $this->belongsTo(Comercio::class);
    }
}
