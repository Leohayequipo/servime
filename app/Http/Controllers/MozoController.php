<?php

namespace App\Http\Controllers;

use App\Http\Middleware\MozoAutenticado;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\ChatbotService;
use App\Services\ComandaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * La pantalla del mozo: las mesas del local con su comanda, en vivo.
 *
 * Corre sin usuario de Laravel: entra por un link secreto del comercio y un PIN (ver
 * MozoAutenticado). El comercio siempre sale del middleware, nunca de un parámetro que
 * el cliente pueda tocar.
 */
class MozoController extends Controller
{
    /** Intentos de PIN antes de dejar la tablet afuera un rato. */
    private const MAX_INTENTOS_PIN = 8;

    private const BLOQUEO_SEGUNDOS = 600;

    public function __construct(private ComandaService $comandas) {}

    public function pin(Request $request): View|RedirectResponse
    {
        $comercio = $this->comercio($request);

        if ($request->session()->get(MozoAutenticado::claveSesion($comercio))) {
            return redirect()->route('mozo.tablero', $comercio->mozo_token);
        }

        return view('mozo.pin', ['comercio' => $comercio]);
    }

    /**
     * Valida el PIN.
     *
     * Cuatro dígitos son diez mil combinaciones: sin freno, un script las prueba todas en
     * minutos. Con ocho intentos por tablet y diez minutos de bloqueo, agotarlas lleva
     * meses, y el mozo que se equivocó dos veces no se entera de que existe un límite.
     */
    public function validarPin(Request $request): RedirectResponse
    {
        $comercio = $this->comercio($request);
        $clave = 'mozo-pin:'.$comercio->id.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS_PIN)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);

            return back()->withErrors([
                'pin' => "Demasiados intentos. Probá de nuevo en {$minutos} minuto(s).",
            ]);
        }

        $datos = $request->validate(['pin' => 'required|string|max:12']);

        if (! $comercio->pinMozoValido($datos['pin'])) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

            return back()->withErrors(['pin' => 'PIN incorrecto.']);
        }

        RateLimiter::clear($clave);
        $request->session()->put(MozoAutenticado::claveSesion($comercio), true);

        return redirect()->route('mozo.tablero', $comercio->mozo_token);
    }

    public function salir(Request $request): RedirectResponse
    {
        $comercio = $this->comercio($request);
        $request->session()->forget(MozoAutenticado::claveSesion($comercio));

        return redirect()->route('mozo.pin', $comercio->mozo_token);
    }

    public function tablero(Request $request): View
    {
        $comercio = $this->comercio($request);

        return view('mozo.tablero', [
            'comercio' => $comercio,
            'mesas' => $this->estadoDeLasMesas($comercio),
            // La carta entera para el "+ Agregar", agrupada por categoría. Se manda
            // completa y se filtra en el navegador: son decenas de platos, no miles, y
            // así el mozo navega sin esperar un request por toque en el wifi del local.
            //
            // Va con la CATEGORÍA a propósito: el mozo no se sabe la carta de memoria y
            // escribir el nombre exacto es buscar a ciegas. Tocando "Bebidas" ve lo que
            // hay, que es como funciona en el salón.
            'platos' => collect(app(ChatbotService::class)->platosDelMenu($comercio))
                ->map(fn ($p) => [
                    'nombre' => $p['nombre'],
                    'precio' => $p['precio'],
                    'categoria' => $p['categoria'] ?: 'Otros',
                ])
                ->values(),
        ]);
    }

    /** El mismo estado que la vista, en JSON, para el refresco automático. */
    public function estado(Request $request): JsonResponse
    {
        $comercio = $this->comercio($request);

        return response()->json([
            'success' => true,
            'mesas' => $this->estadoDeLasMesas($comercio),
            'actualizado' => now()->toIso8601String(),
        ]);
    }

    /**
     * El mozo abre una mesa sin que nadie haya pedido desde el teléfono.
     *
     * Es el caso más común del salón: se sientan, no usan el menú digital y le dictan el
     * pedido al mozo. Sin esto, la pantalla sólo servía para las mesas que habían pedido
     * solas, que son las que menos ayuda necesitan.
     */
    public function abrirMesa(Request $request, string $token, Mesa $mesa): JsonResponse
    {
        $comercio = $this->comercio($request);

        if ($mesa->comercio_id !== $comercio->id) {
            abort(404);
        }

        // `abrirOTomar` es idempotente: si dos mozos tocan la misma mesa a la vez, los dos
        // terminan en la misma comanda en vez de duplicarla.
        $comanda = $this->comandas->abrirOTomar($mesa);

        return response()->json([
            'success' => true,
            'comanda_id' => $comanda->id,
            'mesas' => $this->estadoDeLasMesas($comercio),
        ]);
    }

    public function agregarItem(Request $request, string $token, Comanda $comanda): JsonResponse
    {
        $comercio = $this->comercio($request);
        $this->verificarComanda($comercio, $comanda);

        $datos = $request->validate([
            'nombre' => 'required|string|max:120',
            'qty' => 'nullable|integer|min:1|max:99',
            'precio' => 'nullable|string|max:40',
            'nota' => 'nullable|string|max:120',
        ]);

        $item = $this->comandas->agregarDelMozo(
            $comanda,
            $datos['nombre'],
            (int) ($datos['qty'] ?? 1),
            $datos['precio'] ?? null,
            $datos['nota'] ?? null,
        );

        return response()->json([
            'success' => true,
            // Se devuelve para que la pantalla pueda señalar cuál es el que acaba de
            // entrar: sin esa marca el mozo no sabe si su toque hizo algo.
            'item_id' => $item->id,
            'mesas' => $this->estadoDeLasMesas($comercio),
        ]);
    }

    public function cambiarCantidad(Request $request, string $token, Comanda $comanda, ComandaItem $item): JsonResponse
    {
        $comercio = $this->comercio($request);
        $this->verificarComanda($comercio, $comanda, $item);

        $datos = $request->validate(['qty' => 'required|integer|min:0|max:99']);

        $this->comandas->cambiarCantidad($item, (int) $datos['qty']);

        return response()->json(['success' => true, 'mesas' => $this->estadoDeLasMesas($comercio)]);
    }

    public function borrarItem(Request $request, string $token, Comanda $comanda, ComandaItem $item): JsonResponse
    {
        $comercio = $this->comercio($request);
        $this->verificarComanda($comercio, $comanda, $item);

        $this->comandas->borrarItem($item);

        return response()->json(['success' => true, 'mesas' => $this->estadoDeLasMesas($comercio)]);
    }

    /**
     * Cierra la mesa cuando el grupo se va.
     *
     * No borra: la comanda queda con todo lo que se pidió (ver ComandaService::cerrar).
     */
    public function cerrar(Request $request, string $token, Comanda $comanda): JsonResponse
    {
        $comercio = $this->comercio($request);
        $this->verificarComanda($comercio, $comanda);

        $this->comandas->cerrar($comanda);

        return response()->json(['success' => true, 'mesas' => $this->estadoDeLasMesas($comercio)]);
    }

    /**
     * Deshace el cierre de una mesa recién cerrada.
     *
     * El botón "Cerrar mesa" está a un toque en el tablero: cerrar la equivocada va a
     * pasar, y sin esto había que abrir una comanda nueva y recargar todo a mano.
     */
    public function reabrir(Request $request, string $token, Comanda $comanda): JsonResponse
    {
        $comercio = $this->comercio($request);
        $this->verificarComanda($comercio, $comanda);

        if (! $this->comandas->reabrir($comanda)) {
            return response()->json([
                'success' => false,
                'message' => $comanda->fresh()->estaAbierta()
                    ? 'La mesa ya está abierta.'
                    : 'Ya pasó el tiempo para reabrirla, o alguien la volvió a abrir. Abrila de nuevo y cargá lo que falte.',
            ], 409);
        }

        return response()->json(['success' => true, 'mesas' => $this->estadoDeLasMesas($comercio)]);
    }

    /**
     * Las mesas que se cerraron hoy, con lo que pidió cada una.
     *
     * Para el reclamo que llega después de que el grupo se fue, y para que el mozo
     * confirme qué pasó en una mesa que ya no está en el tablero.
     */
    public function cerradas(Request $request): JsonResponse
    {
        $comercio = $this->comercio($request);

        $comandas = Comanda::where('comercio_id', $comercio->id)
            ->where('estado', Comanda::CERRADA)
            ->where('cerrada_at', '>=', now()->startOfDay())
            ->with(['items', 'mesa'])
            ->latest('cerrada_at')
            ->limit(60)
            ->get();

        return response()->json([
            'success' => true,
            'comandas' => $comandas->map(fn (Comanda $c) => [
                'comanda_id' => $c->id,
                'numero' => $c->mesa->numero,
                'etiqueta' => $c->mesa->etiqueta,
                'desde' => $c->abierta_at->format('H:i'),
                'hasta' => $c->cerrada_at->format('H:i'),
                'minutos' => $c->minutosDeServicio(),
                'items_total' => $c->items->sum('qty'),
                'total_centavos' => $c->total_centavos,
                // La cerró el mozo o se cerró sola por inactividad: no es lo mismo, y
                // muchas 'auto' seguidas significan que nadie está cerrando las mesas.
                'cerrada_por' => $c->cerrada_por,
                'se_puede_reabrir' => $c->sePuedeReabrir(),
                'rondas' => $this->rondasDe($c),
            ])->all(),
        ]);
    }

    /**
     * Todas las mesas en servicio con su comanda abierta, si tienen.
     *
     * Se devuelven TODAS y no sólo las ocupadas: el mozo mira el salón, no una lista de
     * pedidos, y necesita ver la mesa 7 vacía en el mismo lugar de siempre.
     *
     * @return array<int, array<string, mixed>>
     */
    private function estadoDeLasMesas(Comercio $comercio): array
    {
        // Las que quedaron abiertas de anoche se cierran antes de dibujar nada: si no, el
        // mozo que abre a la mañana ve el salón lleno de mesas que ya no existen. Es la
        // misma limpieza que hace `abrirOTomar`, acá para que se note sin tocar la mesa.
        $this->comandas->cerrarVencidasDe($comercio->id);

        $abiertas = Comanda::where('comercio_id', $comercio->id)
            ->abiertas()
            ->with('items')
            ->get()
            ->keyBy('mesa_id');

        // Las cerradas hace poco: son las que todavía se pueden reabrir si el mozo tocó
        // la mesa equivocada. Se consultan de una sola vez y no por mesa.
        $reabribles = Comanda::where('comercio_id', $comercio->id)
            ->where('estado', Comanda::CERRADA)
            ->where('cerrada_at', '>=', now()->subMinutes(ComandaService::MINUTOS_PARA_REABRIR))
            ->latest('cerrada_at')
            ->get()
            ->keyBy('mesa_id');

        return $comercio->mesas()->activas()->get()->map(function ($mesa) use ($abiertas, $reabribles) {
            $comanda = $abiertas->get($mesa->id);
            $reciente = $comanda ? null : $reabribles->get($mesa->id);

            return [
                'mesa_id' => $mesa->id,
                'numero' => $mesa->numero,
                'etiqueta' => $mesa->etiqueta,
                'ocupada' => (bool) $comanda,
                'comanda_id' => $comanda?->id,
                'total_centavos' => $comanda?->total_centavos ?? 0,
                'items_total' => $comanda ? $comanda->items->sum('qty') : 0,
                'minutos' => $comanda?->minutosDesdeElUltimoPedido(),
                'rondas' => $comanda ? $this->rondasDe($comanda) : [],
                // Sólo en las libres: el deshacer del cierre por error.
                'reabrir_id' => $reciente?->id,
                'cerrada_hace' => $reciente ? (int) max(0, $reciente->cerrada_at->diffInMinutes(now())) : null,
            ];
        })->all();
    }

    /**
     * Ítems agrupados por el momento en que entraron, del más reciente al más viejo.
     *
     * Agrupar por momento es lo que hace usable la pantalla: sin eso, el mozo tiene que
     * releer toda la lista buscando qué cambió desde la última vez que miró. Y como no se
     * marca nada como entregado, la hora es además el único indicio para distinguir lo
     * que ya llevó a la mesa de lo que falta.
     *
     * Van al revés que en la base a propósito: lo último es lo que el mozo necesita, y al
     * final de la lista es donde el ojo llega último.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rondasDe(Comanda $comanda): array
    {
        return $comanda->rondas()->map(fn ($items, $ronda) => [
            'ronda' => $ronda,
            'hora' => $items->first()->created_at->format('H:i'),
            'del_mozo' => $items->first()->loAgregoElMozo(),
            'items' => $items->map(fn (ComandaItem $item) => [
                'id' => $item->id,
                'nombre' => $item->nombre,
                'qty' => $item->qty,
                'nota' => $item->nota,
                'precio' => $item->price_text,
                // En centavos para mostrarlo con el mismo formato que el total. null = el
                // plato no tenía precio en la carta: no es "$0", se muestra "sin precio".
                'precio_centavos' => $item->price_centavos,
                'subtotal_centavos' => $item->price_centavos !== null ? $item->subtotalCentavos() : null,
                'del_mozo' => $item->loAgregoElMozo(),
            ])->values()->all(),
        ])->reverse()->values()->all();
    }

    private function comercio(Request $request): Comercio
    {
        return $request->attributes->get('comercioMozo');
    }

    /**
     * La comanda (y el ítem) tienen que ser de este comercio.
     *
     * Sin esto, un link de mozo válido serviría para cerrarle las mesas a otro local con
     * sólo cambiar el id en la URL.
     */
    private function verificarComanda(Comercio $comercio, Comanda $comanda, ?ComandaItem $item = null): void
    {
        if ($comanda->comercio_id !== $comercio->id) {
            abort(404);
        }

        if ($item && $item->comanda_id !== $comanda->id) {
            abort(404);
        }
    }
}
