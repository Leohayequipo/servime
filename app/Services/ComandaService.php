<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Mesa;
use Illuminate\Support\Facades\DB;

class ComandaService
{
    /**
     * Después de cuántas horas sin pedidos una comanda se cierra sola.
     *
     * El mozo se va a olvidar de cerrar alguna: sin esto, la mesa 7 arranca el martes
     * mostrando la milanesa del lunes.
     */
    public const HORAS_INACTIVIDAD = 3;

    /**
     * La comanda abierta de la mesa, o una nueva.
     *
     * El `lockForUpdate` y el reintento no sobran: dos teléfonos de la misma mesa
     * tocando "Pedir" en el mismo segundo llegan juntos acá. El índice único de
     * `mesa_abierta_id` es la última red — si dos hilos igual intentan insertar, uno
     * falla y vuelve a buscar la que acaba de crear el otro, en vez de romperle el
     * pedido al cliente.
     */
    public function abrirOTomar(Mesa $mesa): Comanda
    {
        return DB::transaction(function () use ($mesa) {
            $abierta = Comanda::where('mesa_id', $mesa->id)
                ->abiertas()
                ->with('items')
                ->lockForUpdate()
                ->first();

            // Una comanda vencida NO se reusa: se cierra acá mismo y el grupo que llega
            // arranca con la suya.
            //
            // Sin esto, la mesa que quedó abierta de anoche se le devuelve entera a la
            // gente que se sienta al mediodía —mismas rondas, mismo total, todo mezclado—
            // y eso es peor que dejarla ocupada. Va en este punto y no en un cron a
            // propósito: es la consulta que ya se hace, cuesta cero, y no puede fallar en
            // silencio como un `schedule:run` mal configurado en el servidor.
            if ($abierta && $this->estaVencida($abierta)) {
                $this->cerrar($abierta, 'auto');
                $abierta = null;
            }

            if ($abierta) {
                return $abierta;
            }

            try {
                return Comanda::create([
                    'comercio_id' => $mesa->comercio_id,
                    'mesa_id' => $mesa->id,
                    'estado' => Comanda::ABIERTA,
                    // Explícito aunque la columna tenga default 0: sin esto el objeto
                    // recién creado devuelve null hasta que alguien lo relea de la base,
                    // y cualquier cuenta que lo use antes se cae o suma mal.
                    'total_centavos' => 0,
                    'abierta_at' => now(),
                    'mesa_abierta_id' => $mesa->id,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Otro request la creó entre el SELECT y el INSERT.
                return Comanda::where('mesa_id', $mesa->id)->abiertas()->firstOrFail();
            }
        });
    }

    /**
     * Pasa lo que el cliente tenía en su carrito a una ronda nueva de la comanda.
     *
     * El carrito se vacía: si no, el cliente vuelve a tocar "Pedir" y manda todo de
     * nuevo. Lo que pidió lo sigue viendo, pero leído de la comanda, no del carrito.
     *
     * @return array{comanda: Comanda, ronda: int, items: int}
     */
    public function enviarCarrito(Cart $cart, Mesa $mesa): array
    {
        $comanda = $this->abrirOTomar($mesa);

        return DB::transaction(function () use ($cart, $comanda) {
            $cart->load('items');
            $ronda = $comanda->proximaRonda();
            $enviados = 0;

            foreach ($cart->items as $item) {
                if ($item->qty < 1) {
                    continue;
                }

                ComandaItem::create([
                    'comanda_id' => $comanda->id,
                    'ronda' => $ronda,
                    'nombre' => $item->nombre,
                    'qty' => $item->qty,
                    'price_centavos' => $item->price_centavos,
                    'price_text' => $item->price_text,
                    'origen' => ComandaItem::ORIGEN_CLIENTE,
                    'session_id' => $cart->session_id,
                ]);
                $enviados++;
            }

            $cart->items()->delete();
            $cart->update(['total_centavos' => 0]);

            $this->recalcularTotal($comanda);

            return ['comanda' => $comanda->fresh('items'), 'ronda' => $ronda, 'items' => $enviados];
        });
    }

    /**
     * El mozo agrega algo a mano ("agregame un agua").
     *
     * Cada agregado del mozo es su propia ronda, igual que un pedido del cliente: lo que
     * importa es el orden en que fueron entrando las cosas a la mesa.
     */
    public function agregarDelMozo(Comanda $comanda, string $nombre, int $qty = 1, ?string $precioTexto = null, ?string $nota = null): ComandaItem
    {
        $item = ComandaItem::create([
            'comanda_id' => $comanda->id,
            'ronda' => $comanda->proximaRonda(),
            'nombre' => trim($nombre),
            'qty' => max(1, $qty),
            'price_text' => $precioTexto,
            'price_centavos' => $this->aCentavos($precioTexto),
            'origen' => ComandaItem::ORIGEN_MOZO,
            'nota' => $nota ? trim($nota) : null,
        ]);

        $this->recalcularTotal($comanda);

        return $item;
    }

    public function cambiarCantidad(ComandaItem $item, int $qty): void
    {
        if ($qty < 1) {
            $this->borrarItem($item);

            return;
        }

        $item->update(['qty' => $qty]);
        $this->recalcularTotal($item->comanda);
    }

    public function borrarItem(ComandaItem $item): void
    {
        $comanda = $item->comanda;
        $item->delete();
        $this->recalcularTotal($comanda);
    }

    /**
     * Cierra la comanda y libera la mesa.
     *
     * Los ítems quedan: cerrar es marcar que el grupo se fue, no borrar lo que pidió.
     * `mesa_abierta_id` pasa a NULL, que es lo que libera el índice único para que la
     * próxima gente que se siente pueda abrir la suya.
     */
    public function cerrar(Comanda $comanda, string $por = 'mozo'): Comanda
    {
        if (! $comanda->estaAbierta()) {
            return $comanda;
        }

        $comanda->update([
            'estado' => Comanda::CERRADA,
            'cerrada_at' => now(),
            'cerrada_por' => $por,
            'mesa_abierta_id' => null,
        ]);

        return $comanda;
    }

    /**
     * Cuánto tiempo se puede deshacer el cierre de una mesa.
     *
     * El botón "Cerrar mesa" está a un toque en el tablero, así que cerrar la equivocada
     * va a pasar. Pasada la ventana ya no es un error: es un grupo que se fue, y reabrir
     * su comanda mezclaría dos servicios distintos en el mismo registro.
     */
    public const MINUTOS_PARA_REABRIR = 15;

    /**
     * Vuelve a abrir una comanda recién cerrada, para deshacer un cierre por error.
     *
     * Retoma la MISMA comanda en vez de crear una nueva: si no, el grupo quedaría partido
     * en dos registros y ni el total ni los tiempos servirían para nada.
     *
     * @return bool si se pudo
     */
    public function reabrir(Comanda $comanda): bool
    {
        if ($comanda->estaAbierta()) {
            return true;
        }

        if (! $comanda->sePuedeReabrir()) {
            return false;
        }

        return (bool) DB::transaction(function () use ($comanda) {
            // Entre que el mozo vio el botón y lo tocó, alguien pudo sentarse y abrir la
            // mesa. El índice único lo frenaría con un error 500; mejor decirlo.
            $ocupada = Comanda::where('mesa_id', $comanda->mesa_id)
                ->abiertas()
                ->lockForUpdate()
                ->exists();

            if ($ocupada) {
                return false;
            }

            $comanda->update([
                'estado' => Comanda::ABIERTA,
                'cerrada_at' => null,
                'cerrada_por' => null,
                'mesa_abierta_id' => $comanda->mesa_id,
            ]);

            return true;
        });
    }

    /**
     * ¿Esta comanda lleva demasiado tiempo sin que nadie pida nada?
     *
     * Se mide desde el último pedido y no desde la apertura: una mesa que abrió hace
     * cuatro horas pero pidió el postre recién está comiendo, no olvidada.
     */
    public function estaVencida(Comanda $comanda, int $horas = self::HORAS_INACTIVIDAD): bool
    {
        if (! $comanda->estaAbierta()) {
            return false;
        }

        $ultimaActividad = $comanda->items->max('created_at') ?? $comanda->abierta_at;

        return $ultimaActividad->lessThan(now()->subHours($horas));
    }

    /**
     * Cierra de una las comandas vencidas de un comercio.
     *
     * Lo usa el tablero del mozo antes de dibujar las mesas: sin esto, las vencidas se
     * cierran recién cuando alguien las toca, y el mozo que abre a la mañana ve cinco
     * mesas "ocupadas" de la noche anterior.
     *
     * @return int cuántas cerró
     */
    public function cerrarVencidasDe(int $comercioId): int
    {
        $cerradas = 0;

        $abiertas = Comanda::where('comercio_id', $comercioId)->abiertas()->with('items')->get();

        foreach ($abiertas as $comanda) {
            if ($this->estaVencida($comanda)) {
                $this->cerrar($comanda, 'auto');
                $cerradas++;
            }
        }

        return $cerradas;
    }

    /**
     * Cierra las comandas que quedaron abiertas sin actividad, en TODOS los comercios.
     *
     * Ya no es la defensa principal —de eso se encargan `abrirOTomar` y el tablero, que
     * limpian solos cuando se los usa— sino una herramienta para correr a mano o desde un
     * cron si alguna vez lo hay. Se deja porque no molesta y sirve para limpiar de una.
     *
     * @return int cuántas cerró
     */
    public function cerrarInactivas(int $horas = self::HORAS_INACTIVIDAD): int
    {
        $limite = now()->subHours($horas);
        $cerradas = 0;

        Comanda::abiertas()->with('items')->chunkById(100, function ($comandas) use ($limite, &$cerradas) {
            foreach ($comandas as $comanda) {
                $ultimaActividad = $comanda->items->max('created_at') ?? $comanda->abierta_at;

                if ($ultimaActividad->lessThan($limite)) {
                    $this->cerrar($comanda, 'auto');
                    $cerradas++;
                }
            }
        });

        return $cerradas;
    }

    /**
     * Total orientativo de la mesa.
     *
     * Los ítems sin precio (los que el mozo escribió a mano, o platos que el menú no
     * trae con precio) suman cero. Por eso el total es una referencia y no la cuenta: la
     * pantalla del mozo lo dice explícitamente.
     */
    public function recalcularTotal(Comanda $comanda): void
    {
        $total = $comanda->items()->get()->sum(fn (ComandaItem $item) => $item->subtotalCentavos());

        $comanda->update(['total_centavos' => $total]);
    }

    /**
     * Precio en centavos desde el texto del menú, en formato argentino.
     *
     * Misma regla que el carrito ({@see \App\Http\Controllers\CartController}): el '.'
     * es separador de miles y la ',' el decimal, salvo el caso "X.YY" que sí es decimal.
     */
    private function aCentavos(?string $texto): ?int
    {
        if ($texto === null) {
            return null;
        }

        $s = preg_replace('/[^0-9.,]/', '', $texto);

        if ($s === '') {
            return null;
        }

        if (str_contains($s, ',')) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } elseif (! preg_match('/^\d+\.\d{2}$/', $s)) {
            $s = str_replace('.', '', $s);
        }

        return is_numeric($s) ? (int) round(((float) $s) * 100) : null;
    }
}
