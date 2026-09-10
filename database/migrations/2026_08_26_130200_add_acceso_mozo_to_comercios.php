<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acceso a la pantalla del mozo: un link secreto del local más un PIN.
     *
     * No es un usuario por persona a propósito. En un local gastronómico la tablet vive
     * en la barra y la usa el que esté en el turno; dar de alta y de baja un usuario por
     * empleado, con la rotación que tienen estos laburos, termina en una cuenta
     * compartida igual pero con más trabajo. El costo es que no queda registrado QUIÉN
     * cerró cada mesa.
     *
     * El PIN se guarda hasheado. Si el dueño se lo olvida, define uno nuevo: no hay forma
     * de recuperarlo, y para un PIN de cuatro dígitos eso es preferible a tenerlo en
     * claro en la base.
     */
    public function up(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->string('mozo_token', 32)->nullable()->unique()->after('pedido_habilitado');
            $table->string('mozo_pin_hash')->nullable()->after('mozo_token');
            $table->timestamp('mozo_token_rotado_at')->nullable()->after('mozo_pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('comercios', function (Blueprint $table) {
            $table->dropColumn(['mozo_token', 'mozo_pin_hash', 'mozo_token_rotado_at']);
        });
    }
};
