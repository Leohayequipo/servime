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
     * Idiomas con textos de interfaz traducidos.
     *
     * El CHAT no está limitado a esta lista: el modelo responde en cualquier idioma
     * (ver ChatbotService::idiomaRespuesta). Esto es solo para los textos de la app.
     */
    public const SOPORTADOS = ['es', 'en', 'pt'];

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
        $delNavegador = $request->getPreferredLanguage(self::SOPORTADOS);
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
        return in_array($this->normalizar($locale), self::SOPORTADOS, true);
    }
}
