<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avisos de la campanita del panel (notificaciones de Laravel guardadas en base).
 *
 * El índice extra sobre `read_at` es el que usa la campanita en cada carga de página:
 * los no leídos de un usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_sin_leer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
