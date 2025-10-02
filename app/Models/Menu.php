<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'comercio_id',
        'nombre',
        'archivo_original',
        'texto_extraido',
        'contexto_gpt',
        'formato',
        'procesado'
    ];

    protected $casts = [
        'procesado' => 'boolean'
    ];

    public function comercio()
    {
        return $this->belongsTo(Comercio::class);
    }
}
