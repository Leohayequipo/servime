<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            // Configuración de tema
            $table->string('primary_color')->default('#3B82F6'); // Color primario
            $table->string('secondary_color')->default('#6B7280'); // Color secundario
            $table->string('accent_color')->default('#10B981'); // Color de acento
            $table->string('background_color')->default('#FFFFFF'); // Color de fondo
            
            // Mensajes personalizados
            $table->text('welcome_message')->nullable(); // Mensaje de bienvenida del chatbot
            $table->text('menu_description')->nullable(); // Descripción personalizada del menú
            $table->text('contact_info')->nullable(); // Información de contacto
            
            // Configuración del chatbot
            $table->string('chatbot_name')->default('Asistente IA'); // Nombre del chatbot
            $table->boolean('show_contact_info')->default(true); // Mostrar info de contacto
            $table->boolean('enable_ai_responses')->default(true); // Habilitar respuestas con IA
            
            // Configuración de la página pública
            $table->string('page_title')->nullable(); // Título personalizado de la página
            $table->text('page_description')->nullable(); // Descripción de la página
            $table->string('favicon_url')->nullable(); // Favicon personalizado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn([
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
                'favicon_url'
            ]);
        });
    }
};
