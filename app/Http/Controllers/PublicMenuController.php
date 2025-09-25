<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    /**
     * Mostrar menús públicos de un comercio
     */
    public function show($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('menu.public', compact('comercio', 'menus'));
    }
}
