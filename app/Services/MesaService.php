<?php

namespace App\Services;

use App\Models\Comercio;
use App\Models\Mesa;
use Illuminate\Support\Facades\DB;

class MesaService
{
    public function __construct(private QrService $qr) {}

    /**
     * Tope de mesas por comercio. No es una regla de negocio: es un freno para que un
     * dedo pesado no genere diez mil filas y diez mil PNG con un typo en el input.
     */
    public const MAX_MESAS = 300;

    /**
     * Deja el comercio con exactamente `$cantidad` mesas activas, numeradas de 1 a N.
     *
     * Las que sobran se DESACTIVAN, no se borran. Dos motivos: el histórico de
     * analytics de esa mesa se conserva, y un QR ya impreso que reaparezca no da un
     * error raro sino que simplemente no asigna mesa. Volver a subir el número las
     * reactiva con su token original, así el comerciante que se equivocó de número no
     * tiene que reimprimir nada.
     *
     * Es idempotente: llamarla dos veces con el mismo número no cambia nada.
     *
     * @return array{creadas:int, reactivadas:int, desactivadas:int}
     */
    public function sincronizar(Comercio $comercio, int $cantidad): array
    {
        $cantidad = max(0, min($cantidad, self::MAX_MESAS));

        return DB::transaction(function () use ($comercio, $cantidad) {
            $existentes = Mesa::where('comercio_id', $comercio->id)
                ->orderBy('numero')
                ->get()
                ->keyBy('numero');

            $creadas = 0;
            $reactivadas = 0;

            for ($numero = 1; $numero <= $cantidad; $numero++) {
                $mesa = $existentes->get($numero);

                if (! $mesa) {
                    Mesa::create([
                        'comercio_id' => $comercio->id,
                        'numero' => $numero,
                        'token' => Mesa::generarToken(),
                        'activa' => true,
                    ]);
                    $creadas++;

                    continue;
                }

                if (! $mesa->activa) {
                    $mesa->update(['activa' => true]);
                    $reactivadas++;
                }
            }

            // Todo lo que quedó por encima del número pedido sale de circulación.
            $desactivadas = Mesa::where('comercio_id', $comercio->id)
                ->where('numero', '>', $cantidad)
                ->where('activa', true)
                ->update(['activa' => false]);

            return [
                'creadas' => $creadas,
                'reactivadas' => $reactivadas,
                'desactivadas' => $desactivadas,
            ];
        });
    }

    /**
     * Le da un token nuevo a una mesa e invalida el anterior.
     *
     * Se usa cuando el cartel se fotografió o se filtró: el número impreso sigue siendo
     * el mismo, así que solo hay que reimprimir ese cartel, no renumerar el salón.
     */
    public function rotarToken(Mesa $mesa): Mesa
    {
        // El PNG cacheado se llama por el token viejo: hay que borrarlo ANTES de pisarlo,
        // o queda un archivo huérfano en disco por cada rotación.
        $this->qr->olvidar($mesa);

        $mesa->update(['token' => Mesa::generarToken()]);

        return $mesa;
    }
}
