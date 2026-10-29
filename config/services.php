<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'support' => [
        'email' => env('SUPPORT_EMAIL', 'info.servime@gmail.com'),
        'whatsapp' => env('SUPPORT_WHATSAPP_E164', ''),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'), // gpt-4o para mejor precisión en OCR
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

        /*
         * Chat de ventas de la landing. Es el único chat que no saca el modelo del plan de
         * un comercio, porque del otro lado hay un visitante sin cuenta.
         *
         * `gpt-5.6-luna` razona, así que el límite incluye los tokens de razonamiento
         * (~285 medidos en consultas reales). Con los 400 de la etapa gpt-4o-mini el
         * modelo se los gastaba pensando y devolvía la respuesta vacía o cortada.
         */
        'landing_model' => env('LANDING_CHAT_MODEL', 'gpt-5.6-luna'),
        'landing_max_tokens' => (int) env('LANDING_CHAT_MAX_TOKENS', 900),

        // Notas de voz del chat (TranscripcionService). Va aparte del modelo del plan: no
        // es un modelo de chat, y cuesta por minuto de audio, no por token.
        'transcribe_model' => env('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-mini-transcribe'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        // Procesa las cartas (PDF e imagen). Elegido en oct-2026 contra el 2.5-flash, que se
        // discontinúa el 16/10/2026: sobre cartas reales sacó los mismos platos y precios.
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    // Geolocalización de IPs para saber desde qué país/ciudad entran a la web.
    // ipwho.is: gratis, sin API key, HTTPS. Se puede apagar con GEOIP_ENABLED=false.
    'geoip' => [
        'enabled' => env('GEOIP_ENABLED', true),
        'url' => env('GEOIP_URL', 'https://ipwho.is'),
    ],

];
