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
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'welcome_message',
        'menu_description',
        'contact_info',
        'chatbot_name',
        'show_contact_info',
        'enable_ai_responses',
        'page_title',
        'page_description',
        'favicon_url',
        'pedido_habilitado'
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
        'quick_questions' => 'array', // Decodificar JSON automáticamente
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function menus()
    {
        return $this->hasMany(Menu::class);
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
        return $value ?: "¡Hola! Soy tu asistente virtual. Puedo ayudarte con información sobre nuestro menú. ¿En qué puedo ayudarte?";
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
        return $value ?: $this->nombre . ' - Menú Digital';
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
        
        if (!$menuActivo || !$menuActivo->contexto_gpt) {
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
            
            if (!is_array($data) || !isset($data['categorias'])) {
                return [];
            }

            $categorias = [];
            $promociones = [];
            
            foreach ($data['categorias'] as $categoria) {
                if (isset($categoria['nombre']) && !empty($categoria['nombre'])) {
                    $nombreLower = strtolower(trim($categoria['nombre']));
                    $esPromocion = $nombreLower === 'promociones del día' || $nombreLower === 'promociones del dia';
                    
                    $item = [
                        'nombre' => $categoria['nombre'],
                        'icon' => $esPromocion ? '🔥' : $this->getIconForCategory($categoria['nombre']),
                        'es_promocion' => $esPromocion
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
            
            if (!$menuActivo || !$menuActivo->contexto_gpt) {
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
            
            if (!is_array($data) || !isset($data['categorias']) || !is_array($data['categorias'])) {
                return [];
            }

            $tagsUnicos = [];
            
            foreach ($data['categorias'] as $categoria) {
                if (!is_array($categoria)) {
                    continue;
                }
                
                if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                    foreach ($categoria['platos'] as $plato) {
                        if (!is_array($plato)) {
                            continue;
                        }
                        
                        if (isset($plato['tags']) && is_array($plato['tags'])) {
                            foreach ($plato['tags'] as $tag) {
                                if (!empty($tag) && is_string($tag) && !in_array($tag, $tagsUnicos)) {
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
            \Log::error('Error en getTagsDelMenu: ' . $e->getMessage(), [
                'comercio_id' => $this->id,
                'exception' => $e
            ]);
            return [];
        }
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
}
