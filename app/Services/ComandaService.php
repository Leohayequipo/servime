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
                ->lockForUpdate()
                ->first();

            if ($abierta) {
                return $abierta;
            }

            try {
                return Comanda::create([
                    'comercio_id' => $mesa->comercio_id,
                    'mesa_id' => $mesa->id,
                    'estado' => Comanda::ABIERTA,
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
     * Cierra las comandas que quedaron abiertas sin actividad.
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
