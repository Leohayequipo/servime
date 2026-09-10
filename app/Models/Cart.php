<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'comercio_id', 'mesa_id', 'session_id', 'total_centavos', 'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}
