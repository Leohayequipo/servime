<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    /**
     * Mostrar menús públicos de un comercio (solo slug)
     */
    public function show(Comercio $comercio)
    {
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('menu.public', compact('comercio', 'menus'));
    }
}
