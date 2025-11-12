<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comercio extends Model
{
    protected $fillable = [
        'nombre',
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
        'quick_questions',
        'pedido_habilitado'
    ];

    protected $casts = [
        'show_contact_info' => 'boolean',
        'enable_ai_responses' => 'boolean',
        'quick_questions' => 'array',
        'pedido_habilitado' => 'boolean'
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
     * Obtener preguntas frecuentes personalizadas o por defecto
     */
    public function getQuickQuestionsAttribute($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        
        // Preguntas por defecto
        return [
            [
                'icon' => '🍽️',
                'question' => '¿Qué tenés en el menú?'
            ],
            [
                'icon' => '💰',
                'question' => '¿Cuáles son los precios?'
            ],
            [
                'icon' => '🥗',
                'question' => '¿Tenés opciones vegetarianas?'
            ],
            [
                'icon' => '🕒',
                'question' => '¿Cuáles son los horarios?'
            ]
        ];
    }
}
