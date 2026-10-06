<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * Modelos que ofrece el panel, en orden de recomendación.
     *
     * `gpt-5.6-luna` va primero porque salió MÁS BARATO que el mini en la medición
     * (~$3,61-4,05 contra ~$4,15-5,25 por mil charlas) y además acierta 3/3 en consultas
     * de dietas, donde el mini acierta 0/3. Detalle en la skill `chatbot`.
     *
     * La lista NO es una validación: el campo acepta cualquier string, porque un modelo
     * nuevo se puede cargar por SQL antes de que exista acá. Ver `modelosParaElegir()`.
     */
    public const MODELOS_IA = [
        'gpt-5.6-luna',
        'gpt-4o-mini',
        'gpt-4o',
    ];

    /**
     * Las features que se pueden activar por plan, con su etiqueta para el panel.
     *
     * Fuente única a propósito. Antes la lista estaba escrita a mano DOS veces —los
     * checkboxes de la vista y el `Rule::in` del validador— y se desincronizaron: al sumar
     * `mesas` y `comandas` se tocó sólo la vista, así que el panel las ofrecía y guardar
     * fallaba con "The selected features.5 is invalid". El super admin veía la opción,
     * la marcaba, y el plan no cambiaba.
     *
     * Al agregar una feature nueva, va acá y nada más.
     */
    public const FEATURES = [
        'analytics' => 'Analytics (panel de conversaciones)',
        'excel' => 'Exportar / importar menú a Excel',
        'promociones' => 'Promociones del día',
        'intencion_ia' => 'Clasificación de intención con IA (analytics)',
        'customizacion' => 'Portada personalizada del menú',
        'mesas' => 'Mesas con QR propio (y métricas por mesa)',
        'comandas' => 'Comandas y pantalla del mozo (requiere mesas)',
        'sugerencias' => 'Sugerencias «va bien con» en la carta',
        'lista_pedido' => '«Lo quiero» y «Mi pedido» en la carta',
    ];

    /**
     * Los modelos del desplegable, con el que ya tiene el plan siempre incluido.
     *
     * Sin esto, un plan con un modelo cargado por SQL que no esté en la lista muestra el
     * desplegable posicionado en la primera opción, y guardar cualquier OTRO campo del
     * plan se lo cambia en silencio. Se descubre cuando la factura no baja.
     *
     * @return array<int, string>
     */
    public function modelosParaElegir(): array
    {
        $modelos = self::MODELOS_IA;

        if (filled($this->modelo_ia) && ! in_array($this->modelo_ia, $modelos, true)) {
            array_unshift($modelos, $this->modelo_ia);
        }

        return $modelos;
    }

    protected $fillable = [
        'key',
        'nombre',
        'descripcion',
        'precio',
        'mensajes_ia_mes',
        'modelo_ia',
        'max_tokens_chat',
        'cartas_primer_mes',
        'cartas_por_mes',
        'features',
        'orden',
        'activo',
    ];

    protected $casts = [
        'features' => 'array',
        'activo' => 'boolean',
        'precio' => 'decimal:2',
        'mensajes_ia_mes' => 'integer',
        'max_tokens_chat' => 'integer',
        'cartas_primer_mes' => 'integer',
        'cartas_por_mes' => 'integer',
        'orden' => 'integer',
    ];

    public function comercios()
    {
        return $this->hasMany(Comercio::class);
    }

    /**
     * Plan por defecto al que caen los comercios sin plan asignado.
     */
    public static function default(): ?self
    {
        return static::where('key', 'basico')->first()
            ?? static::orderBy('orden')->first();
    }

    /**
     * El primer plan que incluye la feature, en el orden en que se venden.
     *
     * Para decirle a un comercio a qué plan pasar sin escribir "Pro" a mano: los planes
     * se editan desde /admin/plans, y un nombre fijo en el código queda mintiendo el día
     * que se renombra un plan o se mueve una feature de uno a otro. Deja afuera los
     * inactivos y el de demo (`orden = 0`), igual que la sección de precios de la landing.
     */
    public static function primeroConFeature(string $feature): ?self
    {
        return static::where('activo', true)
            ->where('orden', '>', 0)
            ->orderBy('orden')
            ->get()
            ->first(fn (self $plan) => $plan->tieneFeature($feature));
    }

    /**
     * ¿El plan trae el asistente con IA?
     *
     * `0` mensajes significa "sin asistente" (planes Free y Mínimo) y `null` significa
     * ilimitado: no es lo mismo que quedarse sin cupo a mitad de mes, que es de un plan que
     * SÍ lo trae. Sin asistente, el menú no ofrece el chat en ningún lado.
     */
    public function tieneAsistente(): bool
    {
        return $this->mensajes_ia_mes === null || $this->mensajes_ia_mes > 0;
    }

    /**
     * El cupo de IA en palabras: "Sin asistente", "Asistente sin límite" o "5.000 mensajes…".
     *
     * Existe porque el 0 es falso en PHP: con `mensajes_ia_mes ? … : 'ilimitado'`, la
     * landing le iba a anunciar "IA ilimitada" al plan que no tiene asistente.
     */
    public function cupoIaLegible(): string
    {
        if (! $this->tieneAsistente()) {
            return 'Sin asistente con IA';
        }

        return $this->esIlimitado()
            ? 'Asistente sin límite'
            : number_format($this->mensajes_ia_mes, 0, ',', '.').' mensajes del asistente por mes';
    }

    /**
     * El tope de cartas en palabras, o null si no tiene tope.
     */
    public function topeCartasLegible(): ?string
    {
        $primero = $this->cartas_primer_mes;
        $despues = $this->cartas_por_mes;

        if ($primero === null && $despues === null) {
            return null;
        }

        $txt = fn (?int $n) => $n === null ? 'sin límite' : ($n === 1 ? '1 carta' : $n.' cartas');

        return $primero === $despues
            ? ucfirst($txt($despues)).' por mes'
            : ucfirst($txt($primero)).' el primer mes, después '.$txt($despues).' por mes';
    }

    /**
     * El precio tal como se le muestra al comercio: "Gratis", "A consultar" o "$2.000".
     *
     * Antes era un `precio ? ... : 'A consultar'`, y con el Free en $0 mostraba "A
     * consultar": el 0 es falso en PHP.
     */
    public function precioLegible(): string
    {
        if ($this->precio === null) {
            return 'A consultar';
        }

        return (float) $this->precio === 0.0
            ? 'Gratis'
            : '$'.number_format((float) $this->precio, 0, ',', '.');
    }

    /**
     * ¿La cuota de mensajes IA es ilimitada?
     */
    public function esIlimitado(): bool
    {
        return is_null($this->mensajes_ia_mes);
    }

    /**
     * ¿El plan incluye esta feature? (analytics, customizacion)
     */
    public function tieneFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }
}
