<?php

namespace App\Services;

use App\Models\Comercio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Traduce contenido del menú al idioma del cliente: nombres de CATEGORÍAS y DESCRIPCIONES
 * de platos.
 *
 * Es "simultáneo pero cacheado": el primer visitante en un idioma dispara UNA llamada,
 * el resultado queda cacheado y todos los demás lo reciben instantáneo. Sin caché habría
 * que esperar al modelo en cada carga, y un menú lento en inglés es peor que un menú
 * rápido en español.
 *
 * Los NOMBRES DE PLATOS no se traducen NUNCA, por dos razones: el cliente se los tiene
 * que poder pedir al mozo, y son la clave con la que se resuelven las tarjetas contra el
 * marcador [[PLATOS: ...]] que declara el modelo.
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

    /** @var list<string> Reglas para traducir nombres de categorías. */
    private const INSTRUCCION_CATEGORIAS = [
        'Son nombres de CATEGORÍAS de un menú de restaurante (postres, bebidas, pizzas...).',
        '- Traducilos naturalmente y mantené el mismo estilo de mayúsculas que el original.',
        '- Si un nombre es una marca o un nombre propio, dejalo igual.',
    ];

    /** @var list<string> Reglas para traducir descripciones de platos. */
    private const INSTRUCCION_DESCRIPCIONES = [
        'Son DESCRIPCIONES de platos de un menú (los ingredientes y la preparación).',
        '- Traducí los ingredientes: es lo que le sirve al cliente para entender qué pide.',
        '- Mantené la puntuación y el largo aproximado del original.',
        '- Copiá el uso de mayúsculas del original, letra por letra. Si el original va con inicial mayúscula y el resto en minúscula ("Salsa de tomate, mozzarella y orégano"), la traducción también ("Tomato sauce, mozzarella and oregano"). NO uses Title Case ni pases todo a minúscula.',
        '- Dejá sin traducir las marcas y los nombres de platos típicos sin equivalente (empanada, dulce de leche, fernet).',
    ];

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
        return $this->traducir($comercio, $nombres, 'cat', self::INSTRUCCION_CATEGORIAS, $locale);
    }

    /**
     * Devuelve [descripción original => descripción traducida] para los platos.
     *
     * Acá SÍ se traducen los ingredientes: es justamente lo que le sirve al turista para
     * entender qué está pidiendo. El NOMBRE del plato sigue intacto (se lo tiene que
     * poder pedir al mozo), solo se traduce la descripción.
     *
     * @param  array<int, string>  $descripciones
     * @return array<string, string>
     */
    public function descripciones(Comercio $comercio, array $descripciones, ?string $locale = null): array
    {
        return $this->traducir($comercio, $descripciones, 'desc', self::INSTRUCCION_DESCRIPCIONES, $locale);
    }

    /**
     * Traduce una lista de textos del menú con caché.
     *
     * Se traduce TODO el conjunto de una vez (no solo lo que se muestra en el momento)
     * para que haya una única entrada de caché por comercio+idioma+versión del menú: el
     * primer visitante paga la llamada y de ahí en adelante todos van instantáneo.
     *
     * @param  array<int, string>  $textos
     * @return array<string, string>
     */
    private function traducir(Comercio $comercio, array $textos, string $tipo, array $instruccion, ?string $locale): array
    {
        $locale = $locale ?: app()->getLocale();
        $textos = array_values(array_unique(array_filter(array_map('trim', $textos))));

        // En español no hay nada que traducir (el menú ya está en ese idioma)
        if ($locale === self::IDIOMA_MENU || empty($textos)) {
            return $this->identidad($textos);
        }

        $version = $comercio->menus()
            ->where('procesado', true)
            ->max('updated_at') ?? 'sin-menu';

        $clave = 'menu-trad:'.$tipo.':'.$comercio->id.':'.$locale.':'.md5($version.'|'.implode('|', $textos));

        $traducido = Cache::remember($clave, self::TTL, function () use ($textos, $locale, $instruccion) {
            return $this->pedirTraduccion($textos, $locale, $instruccion);
        });

        // Completar con el original lo que el modelo no haya devuelto
        return array_merge($this->identidad($textos), $traducido);
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
    private function pedirTraduccion(array $nombres, string $locale, array $instruccion): array
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            return [];
        }

        $idioma = \App\Http\Middleware\SetLocale::NOMBRES[$locale] ?? $locale;

        $prompt = "Traducí al {$idioma} los siguientes textos de un menú de restaurante.\n"
            ."Reglas:\n"
            .implode("\n", $instruccion)."\n"
            ."- Respondé SOLO un objeto JSON {\"original\": \"traducción\"}, con TODAS las claves recibidas y sin texto alrededor.\n\n"
            .json_encode($nombres, JSON_UNESCAPED_UNICODE);

        try {
            $respuesta = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->timeout(20)
                ->post(config('services.openai.base_url', 'https://api.openai.com/v1').'/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0,
                    'max_tokens' => 4000,
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
