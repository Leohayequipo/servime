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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comercio_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->index(); // Para agrupar conversaciones
            $table->text('user_message');
            $table->text('bot_response');
            $table->boolean('used_openai')->default(false); // Si usó OpenAI o fallback
            $table->integer('response_time_ms')->nullable(); // Tiempo de respuesta
            $table->string('user_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->timestamps();
            
            $table->index(['comercio_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
