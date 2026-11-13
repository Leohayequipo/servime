<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Las cuentas que crea el admin nacen con la contraseña inicial, que es la misma para
 * todas y figura en el repo (que es público). Con esta marca, el panel les muestra
 * un aviso para que elijan una propia.
 *
 * Además de agregar la columna, marca las cuentas que YA existen y siguen con alguna de
 * las contraseñas de fábrica: la actual y las que hubo antes ("admin", "servime2026").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('debe_cambiar_password')->default(false)->after('password');
        });

        $deFabrica = array_unique(array_filter([
            (string) config('auth.password_inicial'),
            'servime2026',
            'admin',
        ]));

        DB::table('users')->select('id', 'password')->orderBy('id')
            ->chunkById(100, function ($usuarios) use ($deFabrica) {
                foreach ($usuarios as $usuario) {
                    foreach ($deFabrica as $password) {
                        try {
                            $coincide = Hash::check($password, (string) $usuario->password);
                        } catch (\RuntimeException) {
                            // Hash que no es bcrypt (cuenta vieja o cargada a mano): no se
                            // puede comparar, se deja como está.
                            $coincide = false;
                        }

                        if ($coincide) {
                            DB::table('users')->where('id', $usuario->id)
                                ->update(['debe_cambiar_password' => true]);
                            break;
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('debe_cambiar_password');
        });
    }
};
