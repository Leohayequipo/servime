<?php

namespace App\Models;

use App\Notifications\Aviso;
use App\Notifications\CuotaIaAgotada;
use App\Notifications\CuotaIaPorAgotarse;
use App\Services\MenuTranslatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Comercio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'tagline',
        'horarios',
        'logo_url',
        'portada_url',
        'portada_fondo_url',
        'portada_hero_url',
        'portada_thumb_url',
        'portada_focal_x',
        'portada_focal_y',
        'tema_derivado',
        'portada_estado',
        'user_id',
        'plan_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'welcome_message',
        'menu_description',
        'contact_info',
        'chatbot_name',
        'chatbot_config',
        'show_contact_info',
        'enable_ai_responses',
        'page_title',
        'page_description',
        'favicon_url',
        'pedido_habilitado',
        'google_review_url',
        'resenas_activo',
        'resena_titulo',
        'resena_texto',
        'mozo_token',
        'mozo_pin_hash',
        'mozo_token_rotado_at',
        'menu_clasico_vista',
    ];

    /** Las dos formas de mostrar el menú clásico. */
    public const VISTA_ARCHIVO = 'archivo';

    public const VISTA_LISTA = 'lista';

    /** El hash del PIN del mozo no sale nunca en un json ni en un dump. */
    protected $hidden = [
        'mozo_pin_hash',
    ];

    /**
     * Textos por defecto del pedido de reseña.
     *
     * No se guardan en la columna: viven acá y se usan cuando el comercio no editó nada,
     * así una mejora de redacción llega a todos los que nunca la tocaron.
     */
    public const RESENA_TITULO = '¿Cómo la pasaron?';

    public const RESENA_TEXTO = 'Si te gustó, contalo en Google. ¡Gracias!';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /** Slugs que este comercio tuvo antes; siguen resolviendo a su menú. */
    public function slugsAnteriores()
    {
        return $this->hasMany(ComercioSlug::class);
    }

    /**
     * Resuelve el comercio también por sus slugs viejos.
     *
     * Así un QR impreso antes de un renombrado sigue llevando al menú en vez
     * de dar 404. Va acá y no en cada ruta porque hay 41 rutas con
     * {comercio:slug} y todas se benefician igual.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $campo = $field ?? $this->getRouteKeyName();

        if ($encontrado = $this->where($campo, $value)->first()) {
            return $encontrado;
        }

        if ($campo === 'slug') {
            return static::whereHas('slugsAnteriores', fn ($q) => $q->where('slug', $value))->first();
        }

        return null;
    }

    /**
     * Slug único a partir del nombre.
     *
     * Dos comercios pueden llamarse igual y ser distintos ("La Esquina" en dos
     * barrios), así que un nombre repetido no se rechaza: se le agrega sufijo.
     * Antes esto no existía y el nombre repetido reventaba contra el índice
     * comercios_slug_unique con un 500.
     *
     * También se saltean los slugs históricos: reutilizar uno haría que un QR
     * viejo terminara en el menú de otro comercio.
     */
    public static function generarSlugUnico(string $nombre, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'comercio';
        $slug = $base;
        $n = 1;

        while (static::slugOcupado($slug, $ignorarId)) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }

    /** ¿Este slug lo está usando otro comercio, ahora o en el pasado? */
    public static function slugOcupado(string $slug, ?int $ignorarId = null): bool
    {
        $enUso = static::where('slug', $slug)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();

        $historico = ComercioSlug::where('slug', $slug)
            ->when($ignorarId, fn ($q) => $q->where('comercio_id', '!=', $ignorarId))
            ->exists();

        return $enUso || $historico;
    }

    /**
     * Cambia el slug archivando el anterior como alias.
     *
     * Devuelve false si el slug pedido ya lo ocupa otro comercio.
     */
    public function cambiarSlug(string $nuevo, bool $manual = false): bool
    {
        $nuevo = Str::slug($nuevo) ?: 'comercio';

        if ($nuevo === $this->slug) {
            $this->slug_manual = $manual || $this->slug_manual;

            return true;
        }

        if (static::slugOcupado($nuevo, $this->id)) {
            return false;
        }

        if (filled($this->slug)) {
            // firstOrCreate: si el comercio vuelve a un slug que ya tuvo, el
            // alias podría existir de un cambio anterior.
            ComercioSlug::firstOrCreate(
                ['slug' => $this->slug],
                ['comercio_id' => $this->id, 'created_at' => now()]
            );
        }

        // Si el nuevo slug estaba archivado como alias propio, se libera: ahora
        // es el vigente y no tiene sentido que además redirija.
        $this->slugsAnteriores()->where('slug', $nuevo)->delete();

        $this->slug = $nuevo;
        $this->slug_manual = $manual || $this->slug_manual;

        return true;
    }

    protected $casts = [
        'show_contact_info' => 'boolean',
        'enable_ai_responses' => 'boolean',
        'pedido_habilitado' => 'boolean',
        'chatbot_config' => 'array',
        'chips' => 'array',
        'resenas_activo' => 'boolean',
        'tema_derivado' => 'array',
        'horarios' => 'array',
        'slug_manual' => 'boolean',
    ];

    /**
     * ¿Hay que pedirle la reseña de Google al cliente en este menú?
     *
     * Las dos condiciones juntas: el switch prendido NO alcanza si no hay link, porque el
     * botón abriría la nada. Se pregunta desde la vista pública, así que tiene que poder
     * responder sin depender de nadie más.
     */
    public function pideResena(): bool
    {
        return $this->resenas_activo && filled($this->google_review_url);
    }

    /**
     * Los cuatro archivos de la portada: el original y los tres derivados.
     *
     * El original va incluido porque es el que muestra el panel para elegir el encuadre:
     * sin él no se ve la foto y no hay nada sobre lo que arrastrar el marcador.
     *
     * @return array<int, string|null>
     */
    public function archivosPortada(): array
    {
        return [
            $this->portada_url,
            $this->portada_fondo_url,
            $this->portada_hero_url,
            $this->portada_thumb_url,
        ];
    }

    /** El título del pedido de reseña: el del comercio, o el de fábrica. */
    public function resenaTitulo(): string
    {
        return filled($this->resena_titulo) ? $this->resena_titulo : self::RESENA_TITULO;
    }

    /** El texto del pedido de reseña: el del comercio, o el de fábrica. */
    public function resenaTexto(): string
    {
        return filled($this->resena_texto) ? $this->resena_texto : self::RESENA_TEXTO;
    }

    /**
     * Cuántas veces se mostró el pedido de reseña y cuántas lo tocaron.
     *
     * Se cuentan sesiones y no eventos: el bloque se muestra una sola vez por sesión, pero
     * un evento repetido (reintento de red, doble toque) inflaría el número y arruinaría
     * el porcentaje, que es lo único que dice si sirve.
     *
     * @return array{impresiones:int, clicks:int, cierres:int, ignorados:int}
     */
    public function estadisticasResenas(int $dias = 30): array
    {
        $contar = fn (string $evento) => \Illuminate\Support\Facades\DB::table('tracking_events')
            ->where('comercio_id', $this->id)
            ->where('event', $evento)
            ->when($dias > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($dias)))
            ->distinct()
            ->count('session_id');

        $impresiones = $contar('resena_view');
        $clicks = $contar('resena_click');
        $cierres = $contar('resena_dismiss');

        return [
            'impresiones' => $impresiones,
            'clicks' => $clicks,
            'cierres' => $cierres,
            // Ni lo tocaron ni lo cerraron: lo tuvieron delante y no lo registraron. Es
            // problema de MOMENTO, no de texto, y por eso se cuenta aparte del cierre.
            // Nunca negativo: si el tracking pierde una impresión (bloqueador, pestaña
            // cerrada antes del beacon) los clicks pueden superarlas.
            'ignorados' => max(0, $impresiones - $clicks - $cierres),
        ];
    }

    /** Nombres de los días, indexados por día ISO (1 = lunes). */
    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    /**
     * ¿Está abierto ahora?
     *
     * Devuelve `null` cuando el comercio no cargó horarios. Es importante que
     * sea null y no false: la pantalla pública no muestra nada en vez de
     * afirmar algo que no sabe. Antes decía "ABIERTO AHORA" siempre, fijo.
     */
    public function estaAbierto(?\Carbon\Carbon $ahora = null): ?bool
    {
        $horarios = $this->horarios;

        if (blank($horarios)) {
            return null;
        }

        $ahora ??= now();

        // Se miran hoy y ayer: un turno que cierra pasada la medianoche
        // ("de 19 a 2AM") sigue vigente de madrugada, pero pertenece al día
        // anterior.
        foreach ([0, 1] as $diasAtras) {
            $dia = $ahora->copy()->subDays($diasAtras);

            foreach ($horarios[(string) $dia->dayOfWeekIso] ?? [] as $turno) {
                [$desde, $hasta] = $turno;

                $inicio = $dia->copy()->setTimeFromTimeString($desde);
                $fin = $dia->copy()->setTimeFromTimeString($hasta);

                if ($fin->lessThanOrEqualTo($inicio)) {
                    $fin->addDay();
                }

                if ($ahora->betweenIncluded($inicio, $fin)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cuándo vuelve a abrir, para no dejar al cliente sólo con un "cerrado".
     * Devuelve algo como "19:00" o "Lunes 08:00", o null si no hay horarios.
     */
    public function proximaApertura(?\Carbon\Carbon $ahora = null): ?string
    {
        if (blank($this->horarios)) {
            return null;
        }

        $ahora ??= now();

        for ($i = 0; $i <= 7; $i++) {
            $dia = $ahora->copy()->addDays($i);

            foreach ($this->horarios[(string) $dia->dayOfWeekIso] ?? [] as [$desde, $hasta]) {
                $inicio = $dia->copy()->setTimeFromTimeString($desde);

                if ($inicio->greaterThan($ahora)) {
                    return $i === 0
                        ? $inicio->format('H:i')
                        : self::DIAS[$inicio->dayOfWeekIso].' '.$inicio->format('H:i');
                }
            }
        }

        return null;
    }

    /**
     * Frase corta para el menú público.
     *
     * Si no hay tagline se cae a la descripción, pero SÓLO si entra entera:
     * recortar un párrafo largo deja la frase cortada al medio, que se ve peor
     * que no mostrar nada.
     */
    public function taglinePublico(): ?string
    {
        if (filled($this->tagline)) {
            return $this->tagline;
        }

        $desc = trim(strip_tags((string) $this->descripcion));

        return mb_strlen($desc) > 0 && mb_strlen($desc) <= 80 ? $desc : null;
    }

    /**
     * Punto focal de la portada, listo para `object-position`.
     *
     * Ej: "50% 38%". Es lo único que decide el encuadre — el hero y la
     * miniatura se guardan enteros y el navegador los recuadra con esto, así
     * que moverlo no requiere regenerar ninguna imagen.
     */
    public function focoPortada(): string
    {
        return ($this->portada_focal_x ?? 50).'% '.($this->portada_focal_y ?? 50).'%';
    }

    /** ¿Puede este comercio personalizar la portada del menú público? */
    public function puedePersonalizarPortada(): bool
    {
        return $this->tieneFeature('customizacion');
    }

    /** ¿Hay una portada procesada y lista para pintar el menú público? */
    public function tienePortada(): bool
    {
        if ($this->portada_estado !== 'listo'
            || empty($this->portada_hero_url)
            || empty($this->tema_derivado['base'])) {
            return false;
        }

        return $this->archivosDePortadaEnDisco();
    }

    /** Memoria por request: `tienePortada()` se llama más de una vez por visita. */
    private ?bool $portadaEnDisco = null;

    /**
     * ¿Están en el disco los tres archivos que la vista pública va a pedir?
     *
     * NO alcanza con que la base diga que la portada está lista. Si los archivos no están,
     * `menu.portada` igual emite <img> de fondo, hero y thumb: son TRES 404 por visita, de
     * cada cliente, cada vez que alguien escanea el QR.
     *
     * Y eso no es solo ruido. Todos los clientes de un local salen por el mismo wifi —el
     * mismo motivo por el que los rate limiters van por dispositivo y no por IP—, así que
     * varias mesas abriendo un menú con las imágenes rotas parecen, desde afuera, una sola
     * IP escaneando el servidor. Un fail2ban que mire HTTP puede bloquear al local entero
     * de su propio menú, todas las mesas a la vez.
     *
     * Faltando cualquiera de las tres se cae a `menu.choose`, el diseño sin portada, que
     * existe, funciona y no pide ningún archivo.
     */
    private function archivosDePortadaEnDisco(): bool
    {
        if ($this->portadaEnDisco !== null) {
            return $this->portadaEnDisco;
        }

        $disco = \Illuminate\Support\Facades\Storage::disk('public');

        foreach ([$this->portada_hero_url, $this->portada_fondo_url, $this->portada_thumb_url] as $ruta) {
            if (blank($ruta) || ! $disco->exists($ruta)) {
                \Illuminate\Support\Facades\Log::warning('Portada marcada como lista con archivos faltantes', [
                    'comercio' => $this->slug,
                    'falta' => $ruta ?: '(ruta vacía)',
                ]);

                return $this->portadaEnDisco = false;
            }
        }

        return $this->portadaEnDisco = true;
    }

    /**
     * Tema derivado de la portada, con valores por defecto.
     *
     * Devuelve siempre un array usable: si el comercio no tiene portada, la
     * vista pública igual puede pedir claves sin chequear nada.
     */
    public function tema(): array
    {
        $t = $this->tema_derivado ?? [];

        return [
            'base' => $t['base'] ?? '#1A1614',
            'velo' => $t['velo'] ?? [],
            'superficies' => $t['superficies'] ?? [],
            'acento' => $this->colorAcento(),
            'acento_sugerido' => $t['acento_sugerido'] ?? null,
        ];
    }

    /**
     * ¿El acento sugerido por la portada difiere del que está aplicado?
     *
     * Sirve para ofrecerlo en el panel sin pisarlo: el color elegido por el
     * comerciante manda, esto es sólo una propuesta.
     */
    public function tieneAcentoSugeridoDistinto(): bool
    {
        $sugerido = $this->tema_derivado['acento_sugerido'] ?? null;

        return $sugerido !== null && strcasecmp($sugerido, $this->colorAcento()) !== 0;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    /** Mesas del local, en el orden en que están numeradas. */
    public function mesas()
    {
        return $this->hasMany(Mesa::class)->orderBy('numero');
    }

    public function comandas()
    {
        return $this->hasMany(Comanda::class);
    }

    /** ¿Está activada la pantalla del mozo? */
    public function tieneAccesoMozo(): bool
    {
        return filled($this->mozo_token) && filled($this->mozo_pin_hash);
    }

    /**
     * Activa (o reconfigura) la pantalla del mozo con un PIN nuevo.
     *
     * El token sólo se genera la primera vez: cambiar el PIN no tiene por qué invalidar
     * el link que ya está abierto en la tablet de la barra.
     */
    public function activarAccesoMozo(string $pin): void
    {
        $this->update([
            'mozo_token' => $this->mozo_token ?: Str::random(24),
            'mozo_pin_hash' => \Illuminate\Support\Facades\Hash::make($pin),
            'mozo_token_rotado_at' => $this->mozo_token_rotado_at ?: now(),
        ]);
    }

    /** Link nuevo: las tablets abiertas con el anterior quedan afuera. */
    public function rotarTokenMozo(): void
    {
        $this->update([
            'mozo_token' => Str::random(24),
            'mozo_token_rotado_at' => now(),
        ]);
    }

    public function desactivarAccesoMozo(): void
    {
        $this->update([
            'mozo_token' => null,
            'mozo_pin_hash' => null,
            'mozo_token_rotado_at' => null,
        ]);
    }

    public function pinMozoValido(string $pin): bool
    {
        return $this->mozo_pin_hash
            && \Illuminate\Support\Facades\Hash::check($pin, $this->mozo_pin_hash);
    }

    /**
     * ¿Hay un archivo de menú que se pueda mostrar?
     *
     * Mira el DISCO y no sólo la base: la ruta puede estar guardada y el archivo no
     * existir (pasa al bajar una base de producción sin los archivos, o si se pierde en
     * un deploy).
     *
     * Vive en el modelo y no en un controlador porque la vista del menú la renderizan
     * tres sitios distintos —`MenuController::create`, `::edit` y `AdminController::editMenu`—
     * y pasar el dato desde cada uno ya falló una vez: el admin tiraba
     * "Undefined variable" porque era el único que no lo mandaba.
     */
    public function tieneArchivoDeMenu(): bool
    {
        $menu = $this->menuVigente();

        return $menu
            && filled($menu->archivo_original)
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($menu->archivo_original);
    }

    /**
     * Los pasos para que el menú público del comercio esté completo, cada uno con si
     * ya está hecho o no.
     *
     * Existe porque crear un comercio dejaba al comerciante en el dashboard con un
     * "creado exitosamente" y nada más: sin menú, sin portada, sin horarios y sin
     * ninguna señal de que faltaba todo eso. El alta pide cuatro datos; el menú que ve
     * el cliente necesita bastante más.
     *
     * Devuelve los cuatro pasos siempre, completos o no: la vista los usa para mostrar
     * un checklist con lo que falta resaltado y lo hecho tildado, no sólo lo pendiente.
     *
     * El orden es el del recorrido real: sin menú cargado, la portada no tiene sentido.
     *
     * @return list<array{texto: string, detalle: string, ruta: string, completo: bool}>
     */
    public function pasosConfiguracion(): array
    {
        $pasos = [
            [
                'texto' => 'Cargá tu menú',
                'detalle' => 'Subí el PDF o la foto de tu carta y la IA extrae los platos.',
                'ruta' => route('menus.create', $this),
                'completo' => $this->menus()->where('procesado', true)->exists(),
            ],
        ];

        // La portada es feature de plan: al que no la tiene no se le ofrece algo que no
        // puede hacer.
        if ($this->puedePersonalizarPortada()) {
            $pasos[] = [
                'texto' => 'Subí una portada',
                'detalle' => 'De esa foto salen el fondo y los colores de tu menú.',
                // Misma pantalla que el paso anterior, pero con ancla: sin esto son dos
                // links al mismo lado y el segundo parece un error.
                'ruta' => route('menus.create', $this).'#bloque-portada',
                'completo' => $this->tienePortada(),
            ];
        }

        $pasos[] = [
            'texto' => 'Cargá tus horarios',
            'detalle' => 'Con esto el menú muestra si estás abierto; sin esto no muestra nada.',
            'ruta' => route('comercios.edit', $this).'#horariosGrilla',
            'completo' => filled($this->horarios),
        ];

        $pasos[] = [
            'texto' => 'Subí tu logo',
            'detalle' => 'Se usa en el panel y en el QR con logo.',
            'ruta' => route('comercios.edit', $this).'#logo',
            'completo' => filled($this->logo_url),
        ];

        return $pasos;
    }

    /**
     * Sólo lo que falta, de `pasosConfiguracion()`.
     *
     * @return list<array{texto: string, detalle: string, ruta: string, completo: bool}>
     */
    public function pasosPendientes(): array
    {
        return array_values(array_filter(
            $this->pasosConfiguracion(),
            fn (array $paso) => ! $paso['completo']
        ));
    }

    /**
     * Qué se muestra en el menú clásico, resolviendo el caso imposible.
     *
     * Si el comercio eligió "archivo" pero no hay archivo (lo cargó por Excel, o el
     * archivo se perdió), se muestra la lista igual: es preferible a dejarlo con el
     * cartel de "menú no disponible" teniendo todos los platos cargados.
     */
    public function vistaMenuClasico(bool $hayArchivo): string
    {
        if (! $hayArchivo) {
            return self::VISTA_LISTA;
        }

        return $this->menu_clasico_vista === self::VISTA_LISTA
            ? self::VISTA_LISTA
            : self::VISTA_ARCHIVO;
    }

    /**
     * Plan efectivo del comercio (cae al plan por defecto si no tiene uno asignado).
     */
    public function planEfectivo(): ?Plan
    {
        return $this->plan ?: Plan::default();
    }

    public function tieneFeature(string $feature): bool
    {
        $plan = $this->planEfectivo();

        return $plan ? $plan->tieneFeature($feature) : false;
    }

    /**
     * Modelo de IA a usar según el plan (con fallback seguro).
     */
    public function modeloIA(): string
    {
        return $this->planEfectivo()?->modelo_ia ?: 'gpt-4o-mini';
    }

    public function maxTokensChat(): int
    {
        return $this->planEfectivo()?->max_tokens_chat ?: 900;
    }

    /**
     * Mensajes IA usados en el período actual (resetea solo al cambiar de mes).
     */
    public function mensajesIaUsados(): int
    {
        if ($this->ia_mensajes_periodo !== now()->format('Y-m')) {
            return 0;
        }

        return (int) $this->ia_mensajes_usados;
    }

    /**
     * ¿Le queda cuota de IA al comercio este mes? (null en el plan = ilimitado).
     */
    public function puedeUsarIA(): bool
    {
        $plan = $this->planEfectivo();
        if (! $plan || $plan->esIlimitado()) {
            return true;
        }

        return $this->mensajesIaUsados() < $plan->mensajes_ia_mes;
    }

    /**
     * Registrar un consumo de IA (incrementa el contador del mes en curso).
     */
    public function registrarUsoIA(): void
    {
        $periodoActual = now()->format('Y-m');

        if ($this->ia_mensajes_periodo !== $periodoActual) {
            // Nuevo mes: reiniciar contador.
            $this->ia_mensajes_periodo = $periodoActual;
            $this->ia_mensajes_usados = 1;
        } else {
            $this->ia_mensajes_usados = (int) $this->ia_mensajes_usados + 1;
        }

        $this->save();

        $this->avisarSiSeAcabaLaCuota();
    }

    /**
     * Avisa al dueño cuando el cupo de mensajes del mes se está por terminar (80%) y
     * cuando se terminó.
     *
     * Va acá y no en el controller porque las dos rutas del chat —la normal y el stream—
     * pasan por `registrarUsoIA()`: colgado de una sola, el aviso se perdería justo en la
     * que usa el front. Cada aviso se manda **una vez por comercio y por mes**; el período
     * viaja en los datos de la notificación y es lo que se consulta para no repetirlo.
     */
    private function avisarSiSeAcabaLaCuota(): void
    {
        $plan = $this->planEfectivo();
        $cupo = (int) ($plan?->mensajes_ia_mes ?? 0);

        // Plan ilimitado o sin cupo definido: no hay nada que avisar.
        if (! $plan || $plan->esIlimitado() || $cupo <= 0) {
            return;
        }

        $user = $this->user;

        if (! $user) {
            return;
        }

        $usados = $this->mensajesIaUsados();
        $periodo = $this->ia_mensajes_periodo;

        $clave = ['comercio_id' => $this->id, 'periodo' => $periodo];

        if ($usados >= $cupo) {
            Aviso::unaVez($user, CuotaIaAgotada::class, $clave, fn () => new CuotaIaAgotada($this, $cupo, $periodo));

            return;
        }

        if ($usados >= (int) ceil($cupo * 0.8)) {
            Aviso::unaVez($user, CuotaIaPorAgotarse::class, $clave, fn () => new CuotaIaPorAgotarse($this, $usados, $cupo, $periodo));
        }
    }

    /**
     * Reset manual del consumo de IA (usado por el super admin; se cobra aparte).
     */
    public function resetUsoIA(): void
    {
        $this->ia_mensajes_periodo = now()->format('Y-m');
        $this->ia_mensajes_usados = 0;
        $this->save();
    }

    public function chatConversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    /**
     * Obtener mensaje de bienvenida personalizado o por defecto
     */
    public function getWelcomeMessageAttribute($value)
    {
        return $value ?: '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?';
    }

    /**
     * Obtener nombre del chatbot personalizado o por defecto
     */
    public function getChatbotNameAttribute($value)
    {
        return $value ?: 'Asistente IA';
    }

    /**
     * Obtener título de página personalizado o por defecto
     */
    public function getPageTitleAttribute($value)
    {
        return $value ?: $this->nombre.' - Menú Digital';
    }

    /**
     * Promos cargadas en la sección "Promociones del Día" del panel de menús,
     * tal cual (nombre, precio, descripción, imagen). El chat las muestra
     * directamente, sin pasar por la IA. Devuelve TODAS las que existan.
     */
    public function getPromocionesDelMenu(): array
    {
        $menuActivo = \App\Support\VistaPrevia::menu($this) ?? $this->menus()
            ->where('activo', true)
            ->where('procesado', true)
            ->first();

        if (! $menuActivo || ! $menuActivo->contexto_gpt) {
            return [];
        }

        try {
            $clean = trim($menuActivo->contexto_gpt);
            if (strpos($clean, '```json') !== false) {
                $clean = preg_replace('/```json\s*/', '', $clean);
                $clean = preg_replace('/```\s*$/', '', $clean);
            }

            $data = json_decode(trim($clean), true);
            if (! is_array($data) || ! isset($data['categorias']) || ! is_array($data['categorias'])) {
                return [];
            }

            foreach ($data['categorias'] as $categoria) {
                $nombreLower = strtolower(trim($categoria['nombre'] ?? ''));
                if ($nombreLower !== 'promociones del día' && $nombreLower !== 'promociones del dia') {
                    continue;
                }

                $promos = [];
                foreach ($categoria['platos'] ?? [] as $plato) {
                    $nombre = trim($plato['nombre'] ?? '');
                    if ($nombre === '') {
                        continue;
                    }
                    $imagen = trim($plato['imagen'] ?? '');
                    $promos[] = [
                        'nombre' => $nombre,
                        'precio' => $this->formatearPrecioPromo($plato['precio'] ?? ''),
                        'descripcion' => trim($plato['descripcion'] ?? ''),
                        'imagen' => $imagen === '' ? '' : $this->resolverImagenPromo($imagen),
                    ];
                }

                return $promos;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Precio del panel ("19500") al formato del menú ("$19.500"). Si el comercio
     * escribió otra cosa ("2x1", "$19.500"), se respeta tal cual.
     *
     * El criterio vive en `Support\Precio` porque es el mismo para promos y para platos.
     */
    private function formatearPrecioPromo(string $precio): string
    {
        return \App\Support\Precio::formatear($precio);
    }

    /**
     * Resuelve la URL de la imagen de una promo (agrega prefijo si vino relativa).
     */
    private function resolverImagenPromo(string $ruta): string
    {
        if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
            return $ruta;
        }
        $ruta = '/'.ltrim($ruta, '/');
        if (! str_starts_with($ruta, '/storage/')) {
            $ruta = '/storage/'.ltrim($ruta, '/');
        }

        return asset($ruta);
    }

    /**
     * El menú que se le muestra al cliente: el activo, o el último procesado.
     *
     * El plan B no es un detalle. Un comercio puede quedarse sin ningún menú marcado
     * como activo —borrando el que lo estaba, por ejemplo—, y ahí la pregunta "¿cuál
     * muestro?" se responde en cuatro lugares distintos del sistema. Tres tenían este
     * plan B y uno no: `getCategoriasDelMenu()` y `getTagsDelMenu()` exigían `activo`
     * y devolvían vacío.
     *
     * Se veía así: el cliente entraba, la carta se mostraba bien y el chat respondía
     * bien, pero los tres chips de la portada caían al genérico "¿Qué tienen?" en vez
     * de ofrecer las promos y las categorías del comercio. Sin ningún error en el log.
     *
     * Por eso vive acá y no copiado en cada lugar: la regla es una sola.
     */
    public function menuVigente(): ?Menu
    {
        // En la vista previa del editor, el menú que se está editando (ver VistaPrevia::menu).
        if ($enEdicion = \App\Support\VistaPrevia::menu($this)) {
            return $enEdicion;
        }

        return $this->menus()->where('procesado', true)->where('activo', true)->first()
            ?: $this->menus()->where('procesado', true)
                ->orderByDesc('created_at')
                // Desempate por id: dos menús subidos en el mismo segundo comparten
                // `created_at` y el orden queda librado a lo que devuelva la base. Con
                // sólo `latest()` el "último" podía ser el anterior.
                ->orderByDesc('id')
                ->first();
    }

    /**
     * Obtener categorías del menú que se le muestra al cliente
     */
    public function getCategoriasDelMenu(): array
    {
        $menuActivo = $this->menuVigente();

        if (! $menuActivo || ! $menuActivo->contexto_gpt) {
            return [];
        }

        try {
            $jsonContent = $menuActivo->contexto_gpt;
            $clean = trim($jsonContent);

            // Remover markdown si existe
            if (strpos($clean, '```json') !== false) {
                $clean = preg_replace('/```json\s*/', '', $clean);
                $clean = preg_replace('/```\s*$/', '', $clean);
            }

            $data = json_decode($clean, true);

            if (! is_array($data) || ! isset($data['categorias'])) {
                return [];
            }

            $categorias = [];
            $promociones = [];

            foreach ($data['categorias'] as $categoria) {
                if (isset($categoria['nombre']) && ! empty($categoria['nombre'])) {
                    $nombreLower = strtolower(trim($categoria['nombre']));
                    $esPromocion = $nombreLower === 'promociones del día' || $nombreLower === 'promociones del dia';

                    $item = [
                        'nombre' => $categoria['nombre'],
                        'icon' => $esPromocion ? '🔥' : $this->getIconForCategory($categoria['nombre']),
                        'es_promocion' => $esPromocion,
                    ];

                    // Separar promociones de categorías normales
                    if ($esPromocion) {
                        $promociones[] = $item;
                    } else {
                        $categorias[] = $item;
                    }
                }
            }

            // Retornar promociones primero (destacadas) y luego categorías normales
            return array_merge($promociones, $categorias);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener todos los tags únicos del menú que se le muestra al cliente
     */
    public function getTagsDelMenu(): array
    {
        try {
            $menuActivo = $this->menuVigente();

            if (! $menuActivo || ! $menuActivo->contexto_gpt) {
                return [];
            }

            $jsonContent = $menuActivo->contexto_gpt;
            $clean = trim($jsonContent);

            // Remover markdown si existe
            if (strpos($clean, '```json') !== false) {
                $clean = preg_replace('/```json\s*/', '', $clean);
                $clean = preg_replace('/```\s*$/', '', $clean);
            }

            $data = json_decode($clean, true);

            if (! is_array($data) || ! isset($data['categorias']) || ! is_array($data['categorias'])) {
                return [];
            }

            $tagsUnicos = [];

            foreach ($data['categorias'] as $categoria) {
                if (! is_array($categoria)) {
                    continue;
                }

                if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                    foreach ($categoria['platos'] as $plato) {
                        if (! is_array($plato)) {
                            continue;
                        }

                        if (isset($plato['tags']) && is_array($plato['tags'])) {
                            foreach ($plato['tags'] as $tag) {
                                if (! empty($tag) && is_string($tag) && ! in_array($tag, $tagsUnicos)) {
                                    $tagsUnicos[] = $tag;
                                }
                            }
                        }
                    }
                }
            }

            // Ordenar alfabéticamente
            sort($tagsUnicos);

            return $tagsUnicos;
        } catch (\Exception $e) {
            \Log::error('Error en getTagsDelMenu: '.$e->getMessage(), [
                'comercio_id' => $this->id,
                'exception' => $e,
            ]);

            return [];
        }
    }

    /** Ámbar cálido: acento por defecto del menú digital. */
    public const ACENTO_POR_DEFECTO = '#C87F35';

    /**
     * Acentos que ofrece el panel. UN color por tema: el resto de la interfaz son
     * neutros cálidos fijos (ver la paleta en menu/chat.blade.php), así cualquier
     * elección del comerciante queda bien.
     *
     * Antes eran 8 temas que seteaban 4 colores cada uno, con hues fríos de Tailwind
     * que chocaban entre sí y con la comida. Y en los temas oscuros `primary_color`
     * guardaba un casi-negro, no un acento.
     *
     * @var array<string, array{nombre:string, color:string}>
     */
    public const ACENTOS = [
        'ambar' => ['nombre' => 'Ámbar', 'color' => '#C87F35'],
        'cobre' => ['nombre' => 'Cobre', 'color' => '#B4653A'],
        'terracota' => ['nombre' => 'Terracota', 'color' => '#A8541F'],
        'oliva' => ['nombre' => 'Oliva', 'color' => '#7C8B4E'],
        'vino' => ['nombre' => 'Vino', 'color' => '#8C3A46'],
        'dorado' => ['nombre' => 'Dorado', 'color' => '#C9A227'],
    ];

    /** Texto sembrado por defecto como mensaje de bienvenida del chat. */
    /**
     * La personalidad que el asistente YA tiene cuando el comercio no eligió ninguna.
     *
     * No es una preferencia inventada para la pantalla: son los valores con los que está
     * escrito el prompt base de ChatbotService, que es lo que se usa cuando
     * `chatbot_config` viene vacío ("sos entusiasta... en prosa natural y cálida", todo en
     * voseo, "un emoji de categoría queda bien, con moderación"). Por eso marcarlos en la
     * pantalla no le cambia nada al chat: antes aparecían todos sin elegir, y parecía que el
     * asistente no tenía personalidad cuando en realidad hablaba así.
     *
     * Si se cambia el prompt base, esto se cambia con él. `ChatbotConfigTest` lo verifica.
     */
    public const PERSONALIDAD_POR_DEFECTO = [
        'tono' => 'calido_atento',
        'trato' => 'voseo',
        'emojis' => 'moderado',
    ];

    public const BIENVENIDA_POR_DEFECTO = '¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?';

    /**
     * Mensaje de bienvenida del chat, traducido al idioma del cliente cuando corresponde.
     *
     * La mayoría de los comercios tiene guardado el texto por defecto sin haberlo tocado
     * (igual que pasaba con los colores). Para esos alcanza con la traducción del archivo
     * de idioma, que es gratis e instantánea.
     *
     * Si el comerciante escribió SU propio mensaje, se traduce con IA y queda cacheado,
     * con instrucciones de conservarle el tono. Antes se devolvía tal cual, así que un
     * local con bienvenida personalizada le mostraba español a un turista: justo lo
     * contrario de la señal que el mensaje de bienvenida tiene que dar.
     */
    public function mensajeBienvenida(): string
    {
        $propio = trim((string) $this->welcome_message);

        if ($propio === '' || $propio === self::BIENVENIDA_POR_DEFECTO) {
            return __(self::BIENVENIDA_POR_DEFECTO);
        }

        return app(MenuTranslatorService::class)->texto($this, $propio);
    }

    /** Clave del acento actualmente elegido, para marcar el swatch activo en el panel. */
    public function acentoActual(): ?string
    {
        $color = strtoupper($this->colorAcento());

        foreach (self::ACENTOS as $key => $a) {
            if (strtoupper($a['color']) === $color) {
                return $key;
            }
        }

        return null; // color propio, fuera de la paleta ofrecida
    }

    /**
     * Color de acento del menú: el único color de marca que se usa en la interfaz.
     *
     * Se respeta siempre lo que el comerciante eligió en el selector de temas. El ámbar
     * solo entra cuando no hay nada guardado.
     *
     * OJO: no tratar '#3B82F6' como "sin elegir". Es el default de la migración, pero
     * TAMBIÉN es el tema "Profesional" que ofrece el panel, así que pisarlo hacía que
     * ese tema no tuviera efecto.
     */
    public function colorAcento(): string
    {
        $color = trim((string) $this->primary_color);

        return $color === '' ? self::ACENTO_POR_DEFECTO : $color;
    }

    /**
     * Obtener icono para una categoría
     */
    private function getIconForCategory(string $nombre): string
    {
        $nombreLower = strtolower($nombre);

        $iconMap = [
            'entrada' => '🍽️', 'entradas' => '🍽️',
            'principal' => '🥘', 'principales' => '🥘', 'plato principal' => '🥘', 'platos principales' => '🥘',
            'bebida' => '🍷', 'bebidas' => '🍷', 'tragos' => '🍸',
            'postre' => '🍰', 'postres' => '🍰',
            'pizza' => '🍕', 'pizzas' => '🍕',
            'pasta' => '🍝', 'pastas' => '🍝',
            'ensalada' => '🥗', 'ensaladas' => '🥗',
            'carne' => '🍖', 'carnes' => '🍖',
            'pescado' => '🐟', 'marisco' => '🍤', 'mariscos' => '🍤',
            'queso' => '🧀', 'quesos' => '🧀',
            'café' => '☕',
            'cerveza' => '🍺', 'cervezas' => '🍺',
        ];

        foreach ($iconMap as $key => $icon) {
            if (strpos($nombreLower, $key) !== false) {
                return $icon;
            }
        }

        return '🍽️'; // Default icon
    }

    /**
     * Los chips que se muestran en una superficie ('portada' o 'chat').
     *
     * Si el comercio configuró los suyos, mandan esos. Si no, siguen los automáticos que
     * se derivan del menú, así nadie se queda sin chips y no cambia nada para quien nunca
     * entró a configurarlos.
     *
     * @return array<int, array{id:string, emoji:string, label:string, q:string}>
     */
    public function chipsDe(string $superficie, ?int $max = null): array
    {
        $max ??= \App\Support\Chip::visibles($superficie);
        $propios = $this->chips[$superficie] ?? null;

        if (is_array($propios) && ! empty($propios)) {
            return array_slice(\App\Support\Chip::normalizarLista($propios), 0, $max);
        }

        return array_map(
            fn ($chip) => \App\Support\Chip::normalizar($chip),
            $this->getChipsSugeridos($max)
        );
    }

    /**
     * Chips sugeridos para la pantalla de bienvenida, generados a partir del
     * menú REAL del comercio (promos, tags dietéticos y categorías). Máx $max.
     * Cada chip: ['emoji' => ..., 'label' => ..., 'q' => <pregunta para el chat>].
     */
    public function getChipsSugeridos(int $max = 3): array
    {
        $chips = [];

        // 1. Recomendación: universal, siempre útil como primer acceso
        $chips[] = ['emoji' => '✨', 'label' => '¿Qué me recomendás?', 'q' => '¿Qué me recomendás?'];

        $categorias = $this->getCategoriasDelMenu();
        $tags = $this->getTagsDelMenu();

        // 2. Promos: si el menú tiene una categoría de promociones
        $tienePromos = collect($categorias)->contains(fn ($c) => ! empty($c['es_promocion']));
        if ($tienePromos) {
            $chips[] = ['emoji' => '🔥', 'label' => 'Ver promos', 'q' => '¿Qué promociones tienen hoy?'];
        }

        // 3. Tags dietéticos (match tolerante: "Sin TACC", "sin-tacc", "SIN_TACC"...)
        $tagsNorm = array_map(fn ($t) => strtoupper(str_replace([' ', '-'], '_', trim((string) $t))), $tags);
        $tagChips = [
            'SIN_TACC' => ['🌾', 'Sin TACC', '¿Tienen opciones sin TACC?'],
            'VEGANO' => ['🌱', 'Veganas', '¿Tienen opciones veganas?'],
            'VEGETARIANO' => ['🥗', 'Vegetarianas', '¿Tienen opciones vegetarianas?'],
            'SIN_LACTOSA' => ['🥛', 'Sin lactosa', '¿Tienen opciones sin lactosa?'],
            'SIN_AZUCAR' => ['🍬', 'Sin azúcar', '¿Tienen opciones sin azúcar?'],
        ];
        foreach ($tagChips as $tag => [$emoji, $label, $q]) {
            if (in_array($tag, $tagsNorm, true)) {
                $chips[] = ['emoji' => $emoji, 'label' => $label, 'q' => $q];
            }
        }

        // 4. Categorías reales del menú (con su ícono)
        foreach ($categorias as $cat) {
            if (! empty($cat['es_promocion'])) {
                continue;
            }
            $nombre = trim($cat['nombre'] ?? '');
            if ($nombre === '') {
                continue;
            }
            // Suavizar nombres TODO EN MAYÚSCULAS ("NUESTRAS PIZZAS" -> "Nuestras pizzas")
            $label = $nombre;
            if (mb_strtoupper($label, 'UTF-8') === $label) {
                $label = \Illuminate\Support\Str::ucfirst(mb_strtolower($label, 'UTF-8'));
            }
            $chips[] = [
                'emoji' => $cat['icon'] ?? '🍽️',
                'label' => \Illuminate\Support\Str::limit($label, 22, ''),
                'q' => '¿Qué tienen en '.$nombre.'?',
            ];
        }

        // 5. Fallbacks universales (solo si no alcanzó con lo del menú)
        $chips[] = ['emoji' => '🍽️', 'label' => '¿Qué tienen?', 'q' => '¿Qué tienen para comer?'];

        // Dedupe por label y recortar a $max
        $unique = [];
        foreach ($chips as $chip) {
            $key = mb_strtolower($chip['label']);
            if (! isset($unique[$key])) {
                $unique[$key] = $chip;
            }
        }

        return array_slice(array_values($unique), 0, $max);
    }
}
