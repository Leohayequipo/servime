<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Nombre de usuario, para poder entrar con usuario además de con email.
 *
 * Nullable a propósito: los usuarios que ya existen se rellenan acá abajo, pero si el
 * backfill no puede resolver uno, esa cuenta sigue funcionando con su email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        // Backfill: se toma la parte local del email y se normaliza. Si choca con otro,
        // se le suma un número. Así los usuarios existentes también pueden entrar con
        // usuario y no solo los nuevos.
        $usados = [];
        foreach (DB::table('users')->select('id', 'email', 'name')->get() as $u) {
            $base = Str::slug(Str::before($u->email, '@'), '.') ?: Str::slug($u->name, '.');
            $base = Str::limit($base, 40, '') ?: 'usuario';

            $candidato = $base;
            $n = 1;
            while (in_array($candidato, $usados, true)) {
                $candidato = $base.(++$n);
            }
            $usados[] = $candidato;

            DB::table('users')->where('id', $u->id)->update(['username' => $candidato]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
