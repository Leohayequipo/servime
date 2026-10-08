<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\MesaService;
use App\Services\QrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Alta y mantenimiento de las mesas de un comercio, con sus QR.
 *
 * El comerciante no carga las mesas de a una: dice cuántas tiene y el sistema las
 * numera. Ver `MesaService::sincronizar`.
 */
class MesaController extends Controller
{
    /**
     * Para las imágenes que piden login: que la CDN de Hostinger no las guarde (son de un
     * solo comercio) ni las "optimice" (las re-pide sin sesión y rompe). Ver index().
     */
    private const SIN_CDN = 'private, no-store, no-transform';

    public function __construct(
        private MesaService $mesas,
        private QrService $qr,
    ) {}

    public function index(Comercio $comercio): View
    {
        $this->autorizar($comercio);

        $mesas = $comercio->mesas()->get();

        return view('mesas.index', [
            'comercio' => $comercio,
            'mesas' => $mesas,
            'activas' => $comercio->mesas()->activas()->count(),
            // Los QR van embebidos (data URI) y no como <img src="…/qr.png">. En producción
            // hay adelante una CDN de Hostinger que intercepta las URLs de imágenes para
            // optimizarlas, y las va a buscar SIN la sesión del usuario: estas rutas piden
            // login, le devuelven el redirect y la CDN contesta "Invalid source image".
            // Embebidos no hay ninguna imagen que interceptar. (~3,6 KB por mesa.)
            'qrPorMesa' => $mesas->mapWithKeys(
                fn (Mesa $mesa) => [$mesa->id => $this->qr->dataUriDeMesa($mesa)]
            ),
            'qrMozo' => $comercio->tieneAccesoMozo()
                ? 'data:image/png;base64,'.base64_encode($this->qr->png(route('mozo.pin', $comercio->mozo_token), null, 400))
                : null,
        ]);
    }

    public function sincronizar(Request $request, Comercio $comercio): RedirectResponse
    {
        $this->autorizar($comercio);

        $datos = $request->validate([
            'cantidad' => 'required|integer|min:0|max:'.MesaService::MAX_MESAS,
        ], [
            'cantidad.max' => 'El máximo es '.MesaService::MAX_MESAS.' mesas por local.',
        ]);

        $resultado = $this->mesas->sincronizar($comercio, (int) $datos['cantidad']);

        return redirect()
            ->route('mesas.index', $comercio)
            ->with('success', $this->resumen($resultado));
    }

    public function update(Request $request, Comercio $comercio, Mesa $mesa): RedirectResponse
    {
        $this->autorizar($comercio, $mesa);

        $datos = $request->validate([
            'etiqueta' => 'nullable|string|max:40',
        ]);

        $mesa->update(['etiqueta' => $datos['etiqueta'] ?: null]);

        return redirect()->route('mesas.index', $comercio)->with('success', 'Mesa actualizada.');
    }

    public function rotarToken(Comercio $comercio, Mesa $mesa): RedirectResponse
    {
        $this->autorizar($comercio, $mesa);

        $this->mesas->rotarToken($mesa);

        return redirect()->route('mesas.index', $comercio)->with(
            'success',
            "La mesa {$mesa->numero} tiene un código nuevo. Hay que reimprimir ese cartel: el anterior deja de asignar mesa."
        );
    }

    public function toggleActiva(Comercio $comercio, Mesa $mesa): RedirectResponse
    {
        $this->autorizar($comercio, $mesa);

        $mesa->update(['activa' => ! $mesa->activa]);

        return redirect()->route('mesas.index', $comercio)->with(
            'success',
            $mesa->activa ? "Mesa {$mesa->numero} habilitada." : "Mesa {$mesa->numero} fuera de servicio."
        );
    }

    /**
     * Activa la pantalla del mozo (o le cambia el PIN).
     *
     * El PIN lo elige el dueño y se guarda hasheado, así que no se puede volver a mostrar:
     * si se lo olvida, define uno nuevo desde acá.
     */
    public function activarMozo(Request $request, Comercio $comercio): RedirectResponse
    {
        $this->autorizar($comercio);

        $datos = $request->validate([
            'pin' => ['required', 'string', 'regex:/^\d{4,8}$/'],
        ], [
            'pin.regex' => 'El PIN tiene que ser de 4 a 8 dígitos.',
        ]);

        $eraNuevo = ! $comercio->tieneAccesoMozo();
        $comercio->activarAccesoMozo($datos['pin']);

        return redirect()->route('mesas.index', $comercio)->with(
            'success',
            $eraNuevo
                ? 'Pantalla del mozo activada. Abrí el link en la tablet del local y guardalo en favoritos.'
                : 'PIN actualizado. El link sigue siendo el mismo.'
        );
    }

    public function rotarLinkMozo(Comercio $comercio): RedirectResponse
    {
        $this->autorizar($comercio);

        $comercio->rotarTokenMozo();

        return redirect()->route('mesas.index', $comercio)->with(
            'success',
            'Link nuevo. Las tablets que estaban abiertas con el anterior van a tener que entrar de nuevo.'
        );
    }

    public function desactivarMozo(Comercio $comercio): RedirectResponse
    {
        $this->autorizar($comercio);

        $comercio->desactivarAccesoMozo();

        return redirect()->route('mesas.index', $comercio)->with(
            'success',
            'Pantalla del mozo desactivada. Las comandas abiertas no se borran.'
        );
    }

    /**
     * QR del link del mozo, para abrir el tablero en la tablet sin tipear nada.
     *
     * Va sin número al centro (no es una mesa) y sin cachear en disco: se mira una vez,
     * cuando se configura la tablet, y rotar el link tiene que invalidarlo al instante.
     */
    public function qrMozo(Comercio $comercio): Response
    {
        $this->autorizar($comercio);

        if (! $comercio->tieneAccesoMozo()) {
            abort(404);
        }

        return response(
            $this->qr->png(route('mozo.pin', $comercio->mozo_token), null, 400),
            200,
            ['Content-Type' => 'image/png', 'Cache-Control' => self::SIN_CDN]
        );
    }

    /** PNG del QR de una mesa, para la vista previa y la descarga individual. */
    public function qr(Request $request, Comercio $comercio, Mesa $mesa): Response
    {
        $this->autorizar($comercio, $mesa);

        $png = $this->qr->pngDeMesa($mesa);

        $headers = ['Content-Type' => 'image/png', 'Cache-Control' => self::SIN_CDN];

        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="qr-'.$comercio->slug.'-mesa-'.$mesa->numero.'.png"';
        }

        return response($png, 200, $headers);
    }

    /**
     * Hoja A4 con una tarjeta por mesa, para imprimir y cortar.
     *
     * Se resuelve con la impresión del navegador (Ctrl+P) en vez de generar un PDF: no
     * hay librería de PDF en el proyecto, y así el comerciante puede elegir páginas y
     * reimprimir una sola mesa cuando se le rompe un cartel.
     */
    public function imprimir(Comercio $comercio): View
    {
        $this->autorizar($comercio);

        $mesas = $comercio->mesas()->activas()->get();

        return view('mesas.imprimir', [
            'comercio' => $comercio,
            'mesas' => $mesas,
            // Embebidos como data URI: si no, imprimir 30 mesas dispara 30 requests y el
            // navegador manda a imprimir antes de que carguen las imágenes.
            'qrPorMesa' => $mesas->mapWithKeys(
                fn (Mesa $mesa) => [$mesa->id => $this->qr->dataUriDeMesa($mesa)]
            ),
        ]);
    }

    /**
     * El comercio tiene que ser del usuario (el super admin ve todo) y la mesa, del comercio.
     *
     * Lo segundo no sobra: sin ese chequeo, `/comercios/mi-bar/mesas/931/rotar` dejaría
     * rotarle el token a la mesa de otro local con solo probar ids.
     */
    private function autorizar(Comercio $comercio, ?Mesa $mesa = null): void
    {
        $user = auth()->user();

        if (! $user || (! $user->isSuperAdmin() && $comercio->user_id !== $user->id)) {
            abort(403);
        }

        if ($mesa && $mesa->comercio_id !== $comercio->id) {
            abort(404);
        }
    }

    /** @param array{creadas:int, reactivadas:int, desactivadas:int} $resultado */
    private function resumen(array $resultado): string
    {
        $partes = [];

        if ($resultado['creadas'] > 0) {
            $partes[] = $resultado['creadas'].' mesa(s) nueva(s)';
        }
        if ($resultado['reactivadas'] > 0) {
            $partes[] = $resultado['reactivadas'].' reactivada(s)';
        }
        if ($resultado['desactivadas'] > 0) {
            $partes[] = $resultado['desactivadas'].' fuera de servicio';
        }

        return $partes === []
            ? 'No hubo cambios: ya tenías esa cantidad de mesas.'
            : 'Listo: '.implode(', ', $partes).'.';
    }
}
