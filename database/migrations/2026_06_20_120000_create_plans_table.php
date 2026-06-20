<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();              // basico / pro / premium (estable, no se edita)
            $table->string('nombre');                     // nombre marketinero (editable)
            $table->string('descripcion')->nullable();
            $table->decimal('precio', 10, 2)->nullable(); // informativo (no hay cobro online aún)
            $table->unsignedInteger('mensajes_ia_mes')->nullable(); // null = ilimitado
            $table->string('modelo_ia')->default('gpt-4o-mini');
            $table->unsignedInteger('max_tokens_chat')->default(900);
            $table->json('features')->nullable();         // ['analytics', 'customizacion']
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Sembrar los 3 planes por defecto (también en producción, donde el seeder no corre).
        $now = now();
        DB::table('plans')->insert([
            [
                'key' => 'basico',
                'nombre' => 'Básico',
                'descripcion' => 'Para arrancar con tu menú digital.',
                'precio' => 0,
                'mensajes_ia_mes' => 500,
                'modelo_ia' => 'gpt-4o-mini',
                'max_tokens_chat' => 600,
                'features' => json_encode([]),
                'orden' => 1,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'pro',
                'nombre' => 'Pro',
                'descripcion' => 'El más elegido: más conversaciones y analytics.',
                'precio' => null,
                'mensajes_ia_mes' => 5000,
                'modelo_ia' => 'gpt-4o-mini',
                'max_tokens_chat' => 900,
                'features' => json_encode(['analytics', 'excel', 'promociones']),
                'orden' => 2,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'premium',
                'nombre' => 'Premium',
                'descripcion' => 'Todo incluido: IA premium, analytics y personalización.',
                'precio' => null,
                'mensajes_ia_mes' => null, // ilimitado
                'modelo_ia' => 'gpt-4o',
                'max_tokens_chat' => 1200,
                'features' => json_encode(['analytics', 'excel', 'promociones']),
                'orden' => 3,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
