<?php

namespace App\Services;

use App\Models\Comercio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Traduce contenido del menú (nombres de categorías) al idioma del cliente.
 *
 * Es "simultáneo pero cacheado": el primer visitante en un idioma dispara UNA llamada,
 * el resultado queda cacheado y todos los demás lo reciben instantáneo. Sin caché habría
 * que esperar al modelo en cada carga, y un menú lento en inglés es peor que un menú
 * rápido en español.
 *
 * Los NOMBRES DE PLATOS no se traducen nunca (el cliente los tiene que poder pedir al
 * mozo); esto es solo para las categorías, que son genéricas y pocas.
 */
class MenuTranslatorService
{
    /** El caché se invalida solo cuando cambia el menú (va su updated_at en la clave). */
    private const TTL = 60 * 60 * 24 * 30;

    /**
     * Idioma en el que están escritos los menús: los comercios los suben en español.
     *
     * Va como constante y NO como config('app.locale') porque app()->setLocale() del
     * middleware también reescribe esa config: compararla contra el locale actual daba
     * siempre true y nunca se traducía nada.
     */
    private const IDIOMA_MENU = 'es';

    /**
     * Devuelve [nombre original => nombre traducido] para las categorías dadas.
     *
     * Ante cualquier problema (sin API key, error de red, respuesta rara) devuelve los
     * nombres originales: el menú nunca se rompe por una traducción.
     *
     * @param  array<int, string>  $nombres
     * @return array<string, string>
     */
    public function categorias(Comercio $comercio, array $nombres, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $nombres = array_values(array_unique(array_filter(array_map('trim', $nombres))));

        // En español no hay nada que traducir (el menú ya está en ese idioma)
        if ($locale === self::IDIOMA_MENU || empty($nombres)) {
            return $this->identidad($nombres);
        }

        $version = $comercio->menus()
            ->where('procesado', true)
            ->max('updated_at') ?? 'sin-menu';

        $clave = 'menu-cat-trad:'.$comercio->id.':'.$locale.':'.md5($version.'|'.implode('|', $nombres));

        $traducido = Cache::remember($clave, self::TTL, function () use ($nombres, $locale) {
            return $this->pedirTraduccion($nombres, $locale);
        });

        // Completar con el original lo que el modelo no haya devuelto
        return array_merge($this->identidad($nombres), $traducido);
    }

    /** @param array<int, string> $nombres */
    private function identidad(array $nombres): array
    {
        return array_combine($nombres, $nombres) ?: [];
    }

    /**
     * @param  array<int, string>  $nombres
     * @return array<string, string>
     */
    private function pedirTraduccion(array $nombres, string $locale): array
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            return [];
        }

        $idioma = \App\Http\Middleware\SetLocale::NOMBRES[$locale] ?? $locale;

        $prompt = "Traducí al {$idioma} estos nombres de CATEGORÍAS de un menú de restaurante.\n"
            ."Reglas:\n"
            ."- Son categorías genéricas (postres, bebidas, pizzas...), traducilas naturalmente.\n"
            ."- Mantené el mismo estilo de mayúsculas que tiene el original.\n"
            ."- Si un nombre es una marca o un nombre propio, dejalo igual.\n"
            ."- Respondé SOLO un objeto JSON {\"original\": \"traducción\"}, sin texto alrededor.\n\n"
            .json_encode($nombres, JSON_UNESCAPED_UNICODE);

        try {
            $respuesta = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->timeout(20)
                ->post(config('services.openai.base_url', 'https://api.openai.com/v1').'/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $respuesta->successful()) {
                Log::warning('MenuTranslator: respuesta no exitosa', ['status' => $respuesta->status()]);

                return [];
            }

            $json = json_decode($respuesta->json('choices.0.message.content') ?? '', true);

            if (! is_array($json)) {
                return [];
            }

            // Quedarse solo con lo pedido y descartar valores que no sean texto
            $limpio = [];
            foreach ($nombres as $nombre) {
                $t = $json[$nombre] ?? null;
                if (is_string($t) && trim($t) !== '') {
                    $limpio[$nombre] = trim($t);
                }
            }

            return $limpio;
        } catch (\Throwable $e) {
            Log::warning('MenuTranslator: '.$e->getMessage());

            return [];
        }
    }
}
