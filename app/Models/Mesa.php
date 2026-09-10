<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una mesa del local, con su QR propio.
 *
 * El `numero` es lo que se imprime y lo que entiende el mozo; el `token` es lo único
 * que viaja en la URL del QR. Ver la migración para por qué van separados.
 */
class Mesa extends Model
{
    use HasFactory;

    /** Laravel pluraliza "Mesa" como "mesas", pero dejarlo explícito no cuesta nada. */
    protected $table = 'mesas';

    protected $fillable = [
        'comercio_id',
        'numero',
        'etiqueta',
        'token',
        'activa',
    ];

    protected $casts = [
        'numero' => 'integer',
        'activa' => 'boolean',
    ];

    /**
     * Alfabeto del token, sin los caracteres que se confunden al leerlos.
     *
     * Sin 0/O ni 1/l/I: el token normalmente se escanea, pero alguna vez alguien va a
     * tipear la URL de un cartel a mano y no tiene por qué adivinar si ese redondel es
     * un cero o una o.
     */
    private const ALFABETO_TOKEN = '23456789abcdefghjkmnpqrstuvwxyz';

    private const LARGO_TOKEN = 8;

    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * URL que se codifica en el QR de esta mesa.
     *
     * El token va como query string y no como segmento de ruta para que el QR impreso
     * siga funcionando si el comercio cambia de slug: `Comercio::resolveRouteBinding`
     * resuelve los slugs viejos, y así no hay que duplicar las tres rutas del menú.
     */
    public function urlPublica(): string
    {
        $comercio = $this->comercio ?? Comercio::find($this->comercio_id);

        return route('menu.public', $comercio).'?m='.$this->token;
    }

    /**
     * Token único, reintentando ante una colisión.
     *
     * Con 31^8 combinaciones (~850 mil millones) chocar es prácticamente imposible,
     * pero el `unique` de la tabla convertiría ese caso en un error 500 en la cara del
     * comerciante justo cuando está dando de alta sus mesas.
     */
    public static function generarToken(): string
    {
        do {
            $token = '';
            for ($i = 0; $i < self::LARGO_TOKEN; $i++) {
                $token .= self::ALFABETO_TOKEN[random_int(0, strlen(self::ALFABETO_TOKEN) - 1)];
            }
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Cómo se muestra la mesa en el panel y en el menú: "Mesa 7" o "Mesa 7 · Terraza".
     */
    public function nombreLegible(): string
    {
        return filled($this->etiqueta)
            ? "Mesa {$this->numero} · {$this->etiqueta}"
            : "Mesa {$this->numero}";
    }

    /**
     * Cuánto dura la mesa en la sesión del cliente.
     *
     * Ventana fija desde que escaneó, no deslizante: una comida no dura seis horas, y
     * lo que hay que evitar es que el que vuelve al día siguiente entre por el link del
     * historial y sus pedidos caigan en la mesa de otro.
     */
    private const HORAS_VIGENCIA = 6;

    /** Clave de sesión. Lleva el comercio adentro para que escanear en otro local no arrastre la mesa del anterior. */
    private static function claveSesion(int $comercioId): string
    {
        return 'mesa:'.$comercioId;
    }

    public static function recordarEnSesion(self $mesa): void
    {
        session()->put(self::claveSesion($mesa->comercio_id), [
            'id' => $mesa->id,
            'ts' => now()->timestamp,
        ]);
    }

    /**
     * Id de la mesa que el cliente tiene en la sesión para este comercio, si sigue vigente.
     *
     * Devuelve el id sin cargar el modelo: es lo único que necesitan el tracking y el
     * carrito, y se llama en cada evento del menú.
     */
    public static function idDeSesion(int $comercioId): ?int
    {
        $guardada = session()->get(self::claveSesion($comercioId));

        if (! is_array($guardada) || empty($guardada['id'])) {
            return null;
        }

        $vencida = ($guardada['ts'] ?? 0) < now()->subHours(self::HORAS_VIGENCIA)->timestamp;

        if ($vencida) {
            session()->forget(self::claveSesion($comercioId));

            return null;
        }

        return (int) $guardada['id'];
    }

    /** La mesa de la sesión, ya cargada. Para las vistas, que necesitan el número. */
    public static function deSesion(int $comercioId): ?self
    {
        $id = self::idDeSesion($comercioId);

        return $id ? static::find($id) : null;
    }

    public static function olvidarDeSesion(int $comercioId): void
    {
        session()->forget(self::claveSesion($comercioId));
    }
}
