<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comercio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'logo_url',
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
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected $casts = [
        'show_contact_info' => 'boolean',
        'enable_ai_responses' => 'boolean',
        'pedido_habilitado' => 'boolean',
        'chatbot_config' => 'array',
    ];

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
        $menuActivo = $this->menus()
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
     */
    private function formatearPrecioPromo(string $precio): string
    {
        $precio = trim($precio);
        if ($precio === '' || ! ctype_digit($precio)) {
            return $precio;
        }

        return '$'.number_format((float) $precio, 0, ',', '.');
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
     * Obtener categorías del menú activo
     */
    public function getCategoriasDelMenu(): array
    {
        $menuActivo = $this->menus()
            ->where('activo', true)
            ->where('procesado', true)
            ->first();

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
     * Obtener todos los tags únicos del menú activo
     */
    public function getTagsDelMenu(): array
    {
        try {
            $menuActivo = $this->menus()
                ->where('activo', true)
                ->where('procesado', true)
                ->first();

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
