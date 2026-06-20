<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\ChatConversation;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Dashboard principal de analytics
     */
    public function dashboard()
    {
        $comercios = Comercio::withCount(['menus', 'chatConversations'])->get();
        
        $stats = [
            'total_comercios' => Comercio::count(),
            'total_menus' => Menu::count(),
            'total_conversations' => ChatConversation::count(),
            'conversations_today' => ChatConversation::whereDate('created_at', today())->count(),
            'openai_usage' => ChatConversation::usedOpenAI()->count(),
            'fallback_usage' => ChatConversation::where('used_openai', false)->count(),
        ];

        return view('analytics.dashboard', compact('comercios', 'stats'));
    }

    /**
     * Analytics específicos de un comercio
     */
    public function comercio($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);

        if (!auth()->user()->isSuperAdmin() && $comercio->user_id !== auth()->id()) {
            abort(403);
        }

        if (!auth()->user()->isSuperAdmin() && !$comercio->tieneFeature('analytics')) {
            abort(403, 'Las analíticas están disponibles a partir del plan Pro.');
        }

        $conversations = ChatConversation::forComercio($comercioId)
            ->recent(30)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_conversations' => ChatConversation::forComercio($comercioId)->count(),
            'conversations_today' => ChatConversation::forComercio($comercioId)
                ->whereDate('created_at', today())->count(),
            'openai_usage' => ChatConversation::forComercio($comercioId)->usedOpenAI()->count(),
            'avg_response_time' => ChatConversation::forComercio($comercioId)
                ->whereNotNull('response_time_ms')
                ->avg('response_time_ms'),
        ];

        // Preguntas más frecuentes
        $frequentQuestions = $this->getFrequentQuestions($comercioId);
        
        // Estadísticas por día
        $dailyStats = $this->getDailyStats($comercioId);

        return view('analytics.comercio', compact(
            'comercio', 
            'conversations', 
            'stats', 
            'frequentQuestions',
            'dailyStats'
        ));
    }

    /**
     * Obtener preguntas más frecuentes
     */
    private function getFrequentQuestions($comercioId, $limit = 10)
    {
        return ChatConversation::forComercio($comercioId)
            ->select('user_message', DB::raw('COUNT(*) as frequency'))
            ->groupBy('user_message')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener estadísticas diarias
     */
    private function getDailyStats($comercioId, $days = 7)
    {
        return ChatConversation::forComercio($comercioId)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN used_openai = 1 THEN 1 ELSE 0 END) as openai_count'),
                DB::raw('AVG(response_time_ms) as avg_response_time')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Exportar conversaciones
     */
    public function exportConversations($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);

        if (!auth()->user()->isSuperAdmin() && $comercio->user_id !== auth()->id()) {
            abort(403);
        }

        if (!auth()->user()->isSuperAdmin() && !$comercio->tieneFeature('analytics')) {
            abort(403, 'Las analíticas están disponibles a partir del plan Pro.');
        }

        $conversations = ChatConversation::forComercio($comercioId)
            ->with('comercio')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = "conversaciones_{$comercio->nombre}_" . now()->format('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($conversations) {
            $file = fopen('php://output', 'w');
            
            // Headers del CSV
            fputcsv($file, [
                'Fecha',
                'Comercio',
                'Mensaje Usuario',
                'Respuesta Bot',
                'Usó OpenAI',
                'Tiempo Respuesta (ms)',
                'IP',
                'User Agent'
            ]);

            foreach ($conversations as $conversation) {
                fputcsv($file, [
                    $conversation->created_at->format('Y-m-d H:i:s'),
                    $conversation->comercio->nombre,
                    $conversation->user_message,
                    $conversation->bot_response,
                    $conversation->used_openai ? 'Sí' : 'No',
                    $conversation->response_time_ms,
                    $conversation->user_ip,
                    $conversation->user_agent
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
