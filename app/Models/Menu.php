<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    public function comercio()
    {
        return $this->belongsTo(Comercio::class);
    }
}
