<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    /**
     * Pantalla de selección: elegir entre menú con IA o menú clásico
     */
    public function choose(Comercio $comercio)
    {
        return view('menu.choose', compact('comercio'));
    }

    /**
     * Mostrar menú con IA (chatbot) - versión anterior
     */
    public function chat(Comercio $comercio)
    {
        $menus = $comercio->menus()
            ->where('procesado', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('menu.chat', compact('comercio', 'menus'));
    }

    /**
     * Mostrar menú clásico (PDF/imagen directo)
     */
    public function classic(Comercio $comercio)
    {
        $menuActivo = $comercio->menus()
            ->where('activo', true)
            ->where('procesado', true)
            ->first();
        
        // Si no hay menú activo, buscar el último procesado
        if (!$menuActivo) {
            $menuActivo = $comercio->menus()
                ->where('procesado', true)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        return view('menu.classic', compact('comercio', 'menuActivo'));
    }
}
