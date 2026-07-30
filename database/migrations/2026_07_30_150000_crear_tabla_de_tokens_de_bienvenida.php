<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla propia para los tokens del mail de bienvenida.
 *
 * Ese broker vence a los 7 días y el de "olvidé mi contraseña" a la hora. Mientras
 * compartían tabla, el vencimiento lo definía el broker con el que se validaba: bastaba
 * agregarle "?broker=bienvenida" a la URL de un link de recuperación ya vencido para
 * revivirlo durante una semana.
 *
 * Con tablas separadas eso no se puede: un token de recuperación no existe en esta
 * tabla, así que elegir el broker no le cambia la vida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens_bienvenida', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens_bienvenida');
    }
};
