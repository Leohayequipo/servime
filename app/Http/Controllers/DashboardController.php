<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Configurar Carbon en español
        Carbon::setLocale('es');

        $user = auth()->user();
        $recentUsers = collect();
        $recentComercios = collect();
        $totalUsers = 0;
        $chatsHoy = 0;
        $chatsIA = 0;

        $cutoff = now()->subDays(90);

        if ($user->isSuperAdmin()) {
            // Super Admin ve todos los comercios y menús
            $comercios = Comercio::with('user')->get();
            $menus = Menu::with('comercio')->get();
            $allConversaciones = ChatConversation::with('comercio')
                ->where('created_at', '>=', $cutoff)
                ->latest()->get();

            // Métricas adicionales
            $totalUsers = User::count();
            $chatsHoy = ChatConversation::whereDate('created_at', today())->count();
            $chatsIA = ChatConversation::where('used_openai', true)->where('created_at', '>=', $cutoff)->count();
            $recentUsers = User::latest()->take(8)->get();
            $recentComercios = Comercio::with('user')->latest()->take(8)->get();
        } else {
            // Usuario común ve solo sus comercios y menús
            $comercios = Comercio::where('user_id', auth()->id())->get();
            $menus = Menu::whereHas('comercio', function ($query) {
                $query->where('user_id', auth()->id());
            })->get();
            $allConversaciones = ChatConversation::with('comercio')
                ->whereHas('comercio', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->where('created_at', '>=', $cutoff)
                ->latest()->get();

            $recentComercios = $comercios->sortByDesc('created_at')->take(8);
        }

        // Agrupar conversaciones por session_id
        $sesiones = $allConversaciones->groupBy('session_id')->map(function ($conversacionesGrupo) {
            $primeraConv = $conversacionesGrupo->sortBy('created_at')->first();
            $ultimaConv = $conversacionesGrupo->sortByDesc('created_at')->first();

            return [
                'session_id' => $conversacionesGrupo->first()->session_id,
                'comercio' => $conversacionesGrupo->first()->comercio,
                'conversaciones' => $conversacionesGrupo->sortByDesc('created_at'),
                'total_mensajes' => $conversacionesGrupo->count(),
                'ultima_conversacion' => $ultimaConv,
                'primera_conversacion' => $primeraConv,
                // Fechas en formato JavaScript
                'fecha_primera' => $primeraConv->created_at->toISOString(),
                'fecha_ultima' => $ultimaConv->created_at->toISOString(),
                'fecha_primera_timestamp' => $primeraConv->created_at->timestamp,
                'fecha_ultima_timestamp' => $ultimaConv->created_at->timestamp,
            ];
        })->sortByDesc(function ($sesion) {
            return $sesion['ultima_conversacion']->created_at;
        })->take(10);

        // Comercios que agotaron su cuota mensual de IA (para avisar en el dashboard)
        $comerciosSinCuotaIA = $comercios->filter(fn ($comercio) => ! $comercio->puedeUsarIA())->values();

        // Preparar datos para filtros
        $comerciosList = $comercios->map(function ($comercio) {
            return [
                'id' => $comercio->id,
                'nombre' => $comercio->nombre,
                'slug' => $comercio->slug,
            ];
        });

        return view('dashboard', compact(
            'comercios',
            'menus',
            'sesiones',
            'allConversaciones',
            'comerciosList',
            'comerciosSinCuotaIA',
            'recentUsers',
            'recentComercios',
            'totalUsers',
            'chatsHoy',
            'chatsIA'
        ));
    }
}
