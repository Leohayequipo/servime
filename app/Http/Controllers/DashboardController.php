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
            $conversaciones = ChatConversation::latest()->get();
        } else {
            // Usuario común ve solo sus comercios
            $comercios = Comercio::where('user_id', auth()->id())->get();
            $menus = Menu::whereHas('comercio', function($query) {
                $query->where('user_id', auth()->id());
            })->get();
            $conversaciones = ChatConversation::whereHas('comercio', function($query) {
                $query->where('user_id', auth()->id());
            })->latest()->get();
        }

        return view('dashboard', compact('comercios', 'menus', 'conversaciones'));
    }
}
