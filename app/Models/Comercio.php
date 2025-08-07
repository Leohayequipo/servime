<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comercio extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
