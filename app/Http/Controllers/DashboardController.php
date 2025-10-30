<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\Menu;
use App\Models\ChatConversation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin ve todos los comercios
            $comercios = Comercio::all();
            $menus = Menu::all();
            $allConversaciones = ChatConversation::latest()->get();
        } else {
            // Usuario común ve solo sus comercios
            $comercios = Comercio::where('user_id', auth()->id())->get();
            $menus = Menu::whereHas('comercio', function($query) {
                $query->where('user_id', auth()->id());
            })->get();
            $allConversaciones = ChatConversation::whereHas('comercio', function($query) {
                $query->where('user_id', auth()->id());
            })->latest()->get();
        }
        
        // Agrupar conversaciones por session_id
        $sesiones = $allConversaciones->groupBy('session_id')->map(function($conversacionesGrupo) {
            return [
                'session_id' => $conversacionesGrupo->first()->session_id,
                'comercio' => $conversacionesGrupo->first()->comercio,
                'conversaciones' => $conversacionesGrupo->sortByDesc('created_at'),
                'total_mensajes' => $conversacionesGrupo->count(),
                'ultima_conversacion' => $conversacionesGrupo->sortByDesc('created_at')->first(),
                'primera_conversacion' => $conversacionesGrupo->sortBy('created_at')->first(),
            ];
        })->sortByDesc(function($sesion) {
            return $sesion['ultima_conversacion']->created_at;
        })->take(10);

        return view('dashboard', compact('comercios', 'menus', 'sesiones', 'allConversaciones'));
    }
}
