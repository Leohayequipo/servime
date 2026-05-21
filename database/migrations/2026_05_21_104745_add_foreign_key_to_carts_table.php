<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove orphaned carts that reference non-existent comercios
        DB::table('carts')
            ->whereNotIn('comercio_id', DB::table('comercios')->pluck('id'))
            ->delete();

        Schema::table('carts', function (Blueprint $table) {
            $table->foreign('comercio_id')
                  ->references('id')->on('comercios')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['comercio_id']);
        });
    }
};
