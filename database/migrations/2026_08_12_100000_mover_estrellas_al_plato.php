<?php

use App\Models\Comercio;
use Illuminate\Database\Migrations\Migration;

/**
 * Las estrellas pasan de ser una lista de nombres en `comercios.chatbot_config` a ser un
 * campo del propio plato dentro del JSON del menú.
 *
 * Guardadas por nombre y aparte del menú, quedaban colgadas: renombrar un plato dejaba la
 * estrella apuntando a algo inexistente y el prompt le pedía al modelo recomendar un plato
 * que no está en la carta. Las que no matchean con ningún plato se descartan a propósito:
 * son justamente esas huérfanas.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Comercio::whereNotNull('chatbot_config')->get() as $comercio) {
            $config = $comercio->chatbot_config;
            $estrellas = is_array($config) ? ($config['estrellas'] ?? []) : [];

            if (! is_array($estrellas) || empty($estrellas)) {
                continue;
            }

            $buscadas = array_filter(array_map('trim', $estrellas));

            foreach ($comercio->menus()->whereNotNull('contexto_gpt')->get() as $menu) {
                $contenido = json_decode($menu->contexto_gpt, true);
                if (! is_array($contenido) || ! isset($contenido['categorias'])) {
                    continue;
                }

                $tocado = false;
                foreach ($contenido['categorias'] as $ci => $categoria) {
                    foreach ($categoria['platos'] ?? [] as $pi => $plato) {
                        if (in_array(trim((string) ($plato['nombre'] ?? '')), $buscadas, true)) {
                            $contenido['categorias'][$ci]['platos'][$pi]['estrella'] = true;
                            $tocado = true;
                        }
                    }
                }

                if ($tocado) {
                    $menu->contexto_gpt = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $menu->save();
                }
            }

            unset($config['estrellas']);
            $comercio->chatbot_config = $config;
            $comercio->save();
        }
    }

    public function down(): void
    {
        // Vuelve a armar la lista de nombres en chatbot_config a partir de los platos marcados.
        foreach (Comercio::all() as $comercio) {
            $nombres = [];

            foreach ($comercio->menus()->whereNotNull('contexto_gpt')->get() as $menu) {
                $contenido = json_decode($menu->contexto_gpt, true);
                if (! is_array($contenido) || ! isset($contenido['categorias'])) {
                    continue;
                }

                $tocado = false;
                foreach ($contenido['categorias'] as $ci => $categoria) {
                    foreach ($categoria['platos'] ?? [] as $pi => $plato) {
                        if (! empty($plato['estrella'])) {
                            $nombres[] = trim((string) ($plato['nombre'] ?? ''));
                            unset($contenido['categorias'][$ci]['platos'][$pi]['estrella']);
                            $tocado = true;
                        }
                    }
                }

                if ($tocado) {
                    $menu->contexto_gpt = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $menu->save();
                }
            }

            if (! empty($nombres)) {
                $config = $comercio->chatbot_config ?? [];
                $config['estrellas'] = array_values(array_unique($nombres));
                $comercio->chatbot_config = $config;
                $comercio->save();
            }
        }
    }
};
