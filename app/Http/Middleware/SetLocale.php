<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el idioma del cliente para el menú público.
 *
 * Prioridad: ?lang= (selector explícito) → sesión (elección previa) → cabecera
 * Accept-Language del navegador → español. Así un turista abre el QR y ya ve todo
 * en su idioma sin tener que buscar un selector.
 */
class SetLocale
{
    /**
     * Idiomas ofrecidos, con su nombre EN SU PROPIO IDIOMA para el selector.
     *
     * Esta es la única lista que hay que tocar para agregar o quitar un idioma: de acá
     * salen la detección por Accept-Language, el selector del header y la validación de
     * ?lang=. Para sumar uno hacen falta dos cosas: la entrada acá y su lang/<code>.json
     * (más la frase de ANCLAJES de abajo, que es lo que hace que el bot no se vuelva
     * al español).
     *
     * El orden es el que se muestra en el selector.
     */
    public const IDIOMAS = [
        'es' => 'Español',
        'en' => 'English',
        'pt' => 'Português',
        'it' => 'Italiano',
    ];

    /**
     * Códigos soportados. Método y no constante porque se derivan de IDIOMAS, y PHP no
     * permite llamar funciones en una expresión constante.
     *
     * @return array<int, string>
     */
    public static function codigos(): array
    {
        return array_keys(self::IDIOMAS);
    }

    /** Nombre del idioma en español, para instruir al modelo en el prompt. */
    public const NOMBRES = [
        'es' => 'español',
        'en' => 'inglés',
        'pt' => 'portugués',
        'it' => 'italiano',
        'fr' => 'francés',
        'de' => 'alemán',
    ];

    /**
     * Frase de anclaje ESCRITA EN el idioma destino.
     *
     * Decirle "respondé en portugués" (en español, dentro de un prompt en español) no
     * alcanza: el modelo se vuelve al español. Una oración en el idioma destino ancla
     * mucho mejor. Medido: portugués pasó de fallar 1 de 2 a responder siempre bien.
     */
    public const ANCLAJES = [
        'es' => 'Escribí toda tu respuesta en español.',
        'en' => 'Write your entire reply in English. Do not use Spanish.',
        'pt' => 'Escreva toda a sua resposta em português. Não use espanhol.',
        'it' => 'Scrivi tutta la tua risposta in italiano. Non usare lo spagnolo.',
        'fr' => 'Écris toute ta réponse en français. N’utilise pas l’espagnol.',
        'de' => 'Schreibe deine ganze Antwort auf Deutsch. Verwende kein Spanisch.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolver($request);

        app()->setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }

    private function resolver(Request $request): string
    {
        // 1. Selector explícito del cliente
        $pedido = $request->query('lang');
        if (is_string($pedido) && $this->esValido($pedido)) {
            return $this->normalizar($pedido);
        }

        // 2. Elección previa guardada en la sesión
        if ($request->hasSession()) {
            $guardado = $request->session()->get('locale');
            if (is_string($guardado) && $this->esValido($guardado)) {
                return $this->normalizar($guardado);
            }
        }

        // 3. Navegador. getPreferredLanguage devuelve el primero soportado según los
        //    q-values del header, así que respeta el orden real de preferencia.
        $delNavegador = $request->getPreferredLanguage(self::codigos());
        if (is_string($delNavegador) && $this->esValido($delNavegador)) {
            return $this->normalizar($delNavegador);
        }

        return config('app.locale', 'es');
    }

    /** 'pt-BR' y 'pt_br' cuentan como 'pt'. */
    private function normalizar(string $locale): string
    {
        return strtolower(substr(str_replace('_', '-', trim($locale)), 0, 2));
    }

    private function esValido(string $locale): bool
    {
        return in_array($this->normalizar($locale), self::codigos(), true);
    }
}
