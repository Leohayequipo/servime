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
        $comercios = Comercio::where('user_id', auth()->id())->get();
        $menus = Menu::whereHas('comercio', function($query) {
            $query->where('user_id', auth()->id());
        })->get();
        $conversaciones = ChatConversation::whereHas('comercio', function($query) {
            $query->where('user_id', auth()->id());
        })->latest()->get();

        return view('dashboard', compact('comercios', 'menus', 'conversaciones'));
    }
}
