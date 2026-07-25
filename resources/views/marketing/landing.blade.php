<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            // Precio "desde": ignoramos nulos y ceros para no publicar "Desde $0".
            $preciosReales = $planes->pluck('precio')->filter(fn ($p) => ! is_null($p) && (float) $p > 0);
            $precioDesde = $preciosReales->isNotEmpty() ? (float) $preciosReales->min() : null;
            $ogImage = asset('images/og-image.png');

            // Fotos del chat demo del hero: public/images/demo/<nombre>.(webp|jpg|png).
            // Si el archivo no está, la tarjeta se muestra sin foto — igual que en el menú real
            // cuando el comercio todavía no cargó la imagen. Nunca queda una imagen rota.
            $demoFotos = collect([
                'milanesa-napolitana',
                'fugazza',
                'ensalada-verde',
                'helado-artesanal',
                'malbec-copa',
            ])->mapWithKeys(function ($nombre) {
                foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                    $rel = "images/demo/{$nombre}.{$ext}";
                    if (file_exists(public_path($rel))) {
                        return [$nombre => asset($rel)];
                    }
                }

                return [$nombre => null];
            })->filter()->all();

            $demoFotosJson = json_encode($demoFotos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Fuente única de las FAQ: alimenta el schema FAQPage y la sección visible.
            // Google exige que el contenido del schema coincida con lo que se ve en la página.
            $faqs = [
                [
                    'q' => '¿Qué es un menú digital con QR?',
                    'a' => 'Es la carta de tu restaurante publicada en una página web a la que tus clientes entran escaneando un código QR con la cámara del celular. No necesitan descargar ninguna app ni instalar nada: ven el menú actualizado al instante.',
                ],
                [
                    'q' => '¿Mis clientes tienen que descargar una app?',
                    'a' => 'No. Escanean el QR con la cámara y el menú se abre directamente en el navegador del celular. Funciona en Android y iPhone sin instalar nada.',
                ],
                [
                    'q' => '¿Cuánto tarda en estar listo mi menú digital?',
                    'a' => 'Minutos. Subís tu carta en PDF o foto y la inteligencia artificial extrae automáticamente categorías, platos, precios y descripciones. Después podés corregir cualquier dato y agregar fotos desde el panel.',
                ],
                [
                    'q' => '¿Cómo funciona el chatbot con inteligencia artificial?',
                    'a' => 'El asistente se entrena con tu menú real, así que solo recomienda platos que existen en tu carta. Responde consultas como "¿qué opciones sin TACC tienen?" o "¿qué me recomendás para dos personas?", y sugiere maridajes y acompañamientos para aumentar el ticket promedio.',
                ],
                [
                    'q' => '¿Puedo cambiar precios y platos cuando quiera?',
                    'a' => 'Sí, y sin costo. Editás nombres, precios, descripciones, etiquetas y fotos desde el panel, y el cambio se ve al instante en el menú. No hay que reimprimir cartas ni volver a generar el QR.',
                ],
                [
                    'q' => '¿Sirve para bares, cafeterías, pizzerías y food trucks?',
                    'a' => 'Sí. Funciona para cualquier comercio gastronómico: restaurantes, bares, cafeterías, pizzerías, cervecerías, heladerías y food trucks. Cada comercio tiene su propio QR, sus colores y su logo.',
                ],
                [
                    'q' => '¿Los clientes pueden hacer pedidos?',
                    'a' => 'Pueden armar un pre-pedido: marcan los platos que quieren con el botón "Lo quiero" y se genera un resumen con el total estimado para mostrarle al mozo. Esto agiliza la toma del pedido y reduce errores.',
                ],
                [
                    'q' => '¿Cuánto cuesta ServiME?',
                    'a' => 'Hay tres planes según la cantidad de consultas al asistente y las funciones que necesites (analíticas, promociones e importación por Excel). Podés ver el detalle en la sección de planes y precios de esta página.',
                ],
            ];

            // Datos estructurados. Se arma acá (y no con @json) porque el directivo de Blade
            // corta la expresión al encontrar caracteres como '#' dentro de los strings.
            $schemaJson = json_encode([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Organization',
                        '@id' => url('/').'#organization',
                        'name' => 'ServiME',
                        'url' => url('/'),
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => asset('images/logo_servime.png'),
                        ],
                        'image' => $ogImage,
                        'description' => 'Plataforma de menús digitales con QR e inteligencia artificial para comercios gastronómicos.',
                        'areaServed' => 'AR',
                    ],
                    [
                        '@type' => 'WebSite',
                        '@id' => url('/').'#website',
                        'url' => url('/'),
                        'name' => 'ServiME',
                        'inLanguage' => 'es-AR',
                        'publisher' => ['@id' => url('/').'#organization'],
                    ],
                    array_filter([
                        '@type' => 'SoftwareApplication',
                        'name' => 'ServiME',
                        'url' => url('/'),
                        'description' => 'Plataforma de menú digital con QR e inteligencia artificial para restaurantes y bares. El asistente responde preguntas del menú y facilita el pre-pedido.',
                        'applicationCategory' => 'BusinessApplication',
                        'operatingSystem' => 'Web',
                        'inLanguage' => 'es',
                        'publisher' => ['@id' => url('/').'#organization'],
                        'offers' => $precioDesde ? [
                            '@type' => 'Offer',
                            'price' => (string) (int) $precioDesde,
                            'priceCurrency' => 'ARS',
                            'description' => 'Plan mensual ServiME',
                        ] : null,
                        'featureList' => [
                            'Menú digital con código QR',
                            'Asistente de IA entrenado con el menú',
                            'Pre-pedido para el mozo',
                            'Panel de gestión de menús',
                            'Analíticas de uso',
                        ],
                    ], fn ($v) => ! is_null($v)),
                    [
                        '@type' => 'FAQPage',
                        'mainEntity' => array_map(fn ($f) => [
                            '@type' => 'Question',
                            'name' => $f['q'],
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                        ], $faqs),
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @endphp

        <title>Menú digital con QR e IA para restaurantes y bares | ServiME</title>
        <meta name="description" content="Creá tu carta digital con código QR en minutos. Tus clientes escanean, ven el menú y le preguntan a un asistente con IA entrenado con tus platos. Sin apps.{{ $precioDesde ? ' Desde $'.number_format($precioDesde, 0, ',', '.').'/mes.' : '' }}" />
        <link rel="canonical" href="{{ url('/') }}" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />
        <link rel="alternate" hreflang="es-AR" href="{{ url('/') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />
        <meta name="theme-color" content="#0c0b0a" />

        <meta property="og:site_name" content="ServiME" />
        <meta property="og:title" content="Menú digital con QR e IA para restaurantes y bares" />
        <meta property="og:description" content="Carta digital con QR + asistente de IA entrenado con tu menú + pre-pedido para el mozo. Sin apps, listo en minutos." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url('/') }}" />
        <meta property="og:image" content="{{ $ogImage }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" content="ServiME — menú digital con QR e IA para restaurantes y bares" />
        <meta property="og:locale" content="es_AR" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Menú digital con QR e IA para restaurantes y bares" />
        <meta name="twitter:description" content="Carta digital con QR + asistente de IA entrenado con tu menú. Sin apps, listo en minutos." />
        <meta name="twitter:image" content="{{ $ogImage }}" />

        <script type="application/ld+json">{!! $schemaJson !!}</script>

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:300,400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --bg0: #0c0b0a;
                --bg1: #161311;
                --ink: #f6f4ef;
                --muted: rgba(246, 244, 239, 0.72);
                --line: rgba(246, 244, 239, 0.12);
                --accent: #f59e0b; /* ámbar */
                --accent2: #f97316; /* naranja */
            }

            body {
                font-family: "DM Sans", ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial,
                    "Apple Color Emoji", "Segoe UI Emoji";
                background: radial-gradient(1200px 700px at 20% 10%, rgba(245, 158, 11, 0.12), transparent 55%),
                    radial-gradient(900px 600px at 80% 20%, rgba(249, 115, 22, 0.10), transparent 55%),
                    linear-gradient(180deg, var(--bg0), var(--bg1));
                color: var(--ink);
            }

            .font-display {
                font-family: Fraunces, ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
            }

            .grain::before {
                content: "";
                position: fixed;
                inset: 0;
                pointer-events: none;
                background-image:
                    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)' opacity='.22'/%3E%3C/svg%3E");
                opacity: .06;
                mix-blend-mode: overlay;
            }

            /* ── Contact FAB ── */
            #contact-panel {
                transform-origin: bottom right;
                transition: opacity .15s ease, transform .15s ease;
            }
            #contact-panel.panel-closing {
                opacity: 0;
                transform: scale(.95);
            }
            #contact-panel.panel-hidden {
                display: none;
            }

            @keyframes fab-float {
                0%, 100% { transform: translateY(0);   }
                50%       { transform: translateY(-4px); }
            }
            @keyframes fab-ring {
                0%   { box-shadow: 0 0 0 0   rgba(245,158,11,.65), 0 8px 24px rgba(245,158,11,.28); }
                22%  { box-shadow: 0 0 0 13px rgba(245,158,11,0),  0 8px 24px rgba(245,158,11,.28); }
                100% { box-shadow: 0 0 0 0   rgba(245,158,11,0),  0 8px 24px rgba(245,158,11,.28); }
            }
            .fab-live-anim {
                animation: fab-float 3s ease-in-out infinite,
                           fab-ring  4.5s ease-out infinite;
                animation-delay: 0s, .9s;
            }
            @media (prefers-reduced-motion: reduce) {
                .fab-live-anim { animation: none; box-shadow: 0 8px 24px rgba(245,158,11,.28); }
            }

            /* ── Chat Widget ── */
            @media (max-width: 639px) {
                #contact-panel {
                    position: fixed !important;
                    bottom: 0 !important; left: 0 !important; right: 0 !important;
                    width: 100% !important; height: 75dvh !important;
                    border-bottom-left-radius: 0 !important;
                    border-bottom-right-radius: 0 !important;
                    z-index: 51;
                }
                #contact-panel.panel-closing { transform: translateY(40px); }
            }
            @media (min-width: 640px) {
                #contact-panel {
                    position: absolute;
                    bottom: calc(100% + 12px);
                    right: 0;
                }
            }

            @keyframes chat-dot-bounce {
                0%, 80%, 100% { transform: translateY(0);    opacity: .4; }
                40%           { transform: translateY(-5px); opacity: 1;  }
            }
            .chat-typing-dot {
                width: 6px; height: 6px; background: rgba(245,158,11,.7);
                border-radius: 50%; display: inline-block;
                animation: chat-dot-bounce 1.2s ease-in-out infinite;
            }
            .chat-typing-dot:nth-child(2) { animation-delay: .15s; }
            .chat-typing-dot:nth-child(3) { animation-delay: .30s; }
            @media (prefers-reduced-motion: reduce) {
                .chat-typing-dot { animation: none; opacity: .6; }
            }

            @keyframes chat-msg-in {
                from { opacity: 0; transform: translateY(6px); }
                to   { opacity: 1; transform: translateY(0);   }
            }
            .chat-msg-animate { animation: chat-msg-in .2s ease-out; }
            @media (prefers-reduced-motion: reduce) { .chat-msg-animate { animation: none; } }

            /* ── Hero demo: real-chat animations ── */
            @keyframes demo-slide-left  { from { opacity:0; transform:translateX(-16px); } to { opacity:1; transform:translateX(0); } }
            @keyframes demo-slide-right { from { opacity:0; transform:translateX(16px);  } to { opacity:1; transform:translateX(0); } }
            /* Misma onda de 3 puntos que usa el chat real del menú */
            @keyframes demo-typing-wave {
                0%, 60%, 100% { transform: translateY(0);    opacity:.35; }
                30%           { transform: translateY(-4px); opacity:1;   }
            }
            .demo-tdot {
                width:6px; height:6px; background:#cbd5e1; border-radius:50%; display:inline-block;
                animation: demo-typing-wave 1.3s ease-in-out infinite;
            }
            .demo-tdot:nth-child(2) { animation-delay:.18s; }
            .demo-tdot:nth-child(3) { animation-delay:.36s; }
            /* El chat del hero scrollea solo, pero sin barra visible (como un celular) */
            #demo-chat-msgs { scrollbar-width: none; -ms-overflow-style: none; }
            #demo-chat-msgs::-webkit-scrollbar { display: none; }

            /* Cursor del efecto "escribiendo" token a token */
            @keyframes demo-caret { 0%,49% { opacity:1; } 50%,100% { opacity:0; } }
            .demo-caret {
                display:inline-block; width:2px; height:11px; margin-left:2px; vertical-align:-1px;
                background:#f59e0b; animation: demo-caret 1s step-end infinite;
            }
            @media (prefers-reduced-motion: reduce) {
                .demo-tdot { animation:none; opacity:.6; }
                .demo-caret { animation:none; opacity:0; }
            }

            .chat-qr-chip {
                display: inline-flex; align-items: center; padding: 5px 12px;
                border-radius: 9999px; font-size: .75rem; font-weight: 500;
                background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
                color: rgba(246,244,239,.85); cursor: pointer; white-space: nowrap;
                transition: background .15s, border-color .15s, color .15s;
            }
            .chat-qr-chip:hover {
                background: rgba(245,158,11,.15); border-color: rgba(245,158,11,.35); color: #f59e0b;
            }

            #chat-messages::-webkit-scrollbar       { width: 4px; }
            #chat-messages::-webkit-scrollbar-track  { background: transparent; }
            #chat-messages::-webkit-scrollbar-thumb  { background: rgba(255,255,255,.12); border-radius: 4px; }
            #chat-input { field-sizing: content; }

            /* ── Demo toast ── */
            #demo-toast {
                transition: opacity .2s ease;
            }
            #demo-toast.toast-hidden {
                display: none;
            }
        </style>
    </head>
    @php
        $supportEmail     = config('services.support.email');
        $supportWhatsapp  = config('services.support.whatsapp');
    @endphp
    <body class="grain antialiased">
        <header class="sticky top-0 z-50 backdrop-blur supports-[backdrop-filter]:bg-black/20 border-b border-white/5">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-3">
                    <a href="{{ url('/') }}" class="flex items-center group">
                        <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME — menú digital con QR e IA"
                             width="210" height="140" fetchpriority="high" decoding="async"
                             style="height:140px;width:auto;" class="opacity-90 group-hover:opacity-100 transition">
                    </a>

                    <nav class="flex items-center gap-2 sm:gap-3">
                        <a href="#como-funciona" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Cómo funciona</a>
                        <a href="#beneficios" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Beneficios</a>
                        <a href="#preguntas-frecuentes" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Preguntas</a>
                        <button type="button" data-open-contact class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Ayuda</button>
                        <a href="{{ route('login') }}" class="inline-flex text-sm text-white/80 hover:text-white transition px-3 py-2.5 rounded-lg">Entrar</a>
                        <button type="button" data-open-contact class="inline-flex text-sm font-semibold text-black bg-[var(--accent)] hover:bg-[color:var(--accent2)] transition px-4 py-2.5 rounded-xl shadow-sm shadow-amber-500/20">
                            Quiero sumarme
                        </button>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            @if(session('info'))
                <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pt-5">
                    <div class="rounded-2xl bg-amber-500/15 ring-1 ring-amber-500/30 px-5 py-3 text-sm text-amber-200">
                        {{ session('info') }}
                    </div>
                </div>
            @endif

            <section class="relative">
                <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pt-14 sm:pt-20 pb-14">
                    <div class="grid lg:grid-cols-12 gap-10 items-center">
                        <div class="lg:col-span-6">
                            <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl tracking-tight">
                                Menú digital con QR y un mozo virtual que atiende 24/7.
                            </h1>

                            <p class="mt-5 text-base sm:text-lg text-[color:var(--muted)] max-w-xl">
                                Tus clientes escanean el código QR de la mesa, ven tu carta digital y preguntan al instante.
                                El asistente recomienda con los platos reales de tu menú, sugiere el maridaje y arma el
                                pre-pedido para el mozo. Sin apps, sin login y sin reimprimir cartas.
                            </p>

                            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                                <button type="button" data-open-contact class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition shadow-sm shadow-amber-500/20">
                                    Quiero sumarme
                                </button>
                                @if($demoSlug)
                                    <a href="{{ route('menu.public', $demoSlug) }}" class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                                        Ver un menú de verdad
                                    </a>
                                @else
                                    <a href="#como-funciona" class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                                        Ver cómo funciona
                                    </a>
                                @endif
                            </div>

                            {{-- Anclas rápidas de valor (narrativa de la propuesta comercial) --}}
                            <dl class="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-xl">
                                @foreach ([
                                    ['QR', 'Sin apps ni login'],
                                    ['IA', 'Recomienda tu menú real'],
                                    ['24/7', 'Atiende siempre'],
                                    ['100%', 'Con tu marca'],
                                ] as [$dato, $detalle])
                                    <div>
                                        <dt class="font-display text-2xl sm:text-3xl text-[var(--accent)]">{{ $dato }}</dt>
                                        <dd class="mt-1 text-xs sm:text-sm text-[color:var(--muted)]">{{ $detalle }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                        </div>

                        <div class="lg:col-span-6">
                            <div class="relative mx-auto" style="max-width:340px;">
                                <div class="absolute -inset-6 bg-gradient-to-tr from-amber-500/15 via-orange-500/10 to-transparent blur-2xl"></div>

                                <div class="relative rounded-[2.5rem] bg-black/30 ring-1 ring-white/10 p-4 shadow-2xl shadow-black/60">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2.5 w-2.5 rounded-full bg-red-500/70"></div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-yellow-500/70"></div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-green-500/70"></div>
                                        </div>
                                        <p class="text-xs text-white/50">Vista del cliente</p>
                                    </div>

                                    {{-- Real-chat replica --}}
                                    <div class="mt-3 rounded-[1.5rem] overflow-hidden" style="background:#000;display:flex;flex-direction:column;height:620px;">
                                        {{-- App header --}}
                                        <div style="background:#000;padding:10px 14px 8px;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0;">
                                            <div style="display:flex;justify-content:center;margin-bottom:6px;">
                                                <div style="width:42px;height:42px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 18px rgba(59,130,246,0.35);">
                                                    <span style="font-size:17px;">🍽️</span>
                                                </div>
                                            </div>
                                            <div style="text-align:center;">
                                                <p style="color:#fff;font-weight:600;font-size:13px;margin:0;line-height:1.3;">La Cantina</p>
                                                <p style="color:#cbd5e1;font-size:11px;margin:2px 0 0;line-height:1.3;">Menú Digital</p>
                                            </div>
                                            <div style="margin-top:7px;background:linear-gradient(to right,#3b82f6,#334155);border-radius:10px;padding:6px 10px;display:flex;align-items:center;gap:7px;box-shadow:0 0 14px rgba(59,130,246,0.22);">
                                                <div style="width:18px;height:18px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <span style="font-size:10px;">🤖</span>
                                                </div>
                                                <span style="color:#fff;font-size:12px;font-weight:500;">Asistente IA</span>
                                            </div>
                                        </div>
                                        {{-- Messages area --}}
                                        <div id="demo-chat-msgs" style="flex:1;overflow-y:auto;scroll-behavior:smooth;padding:10px 12px;display:flex;flex-direction:column;gap:10px;font-family:Inter,-apple-system,BlinkMacSystemFont,sans-serif;min-height:0;"></div>
                                        {{-- Input bar (réplica de la barra real: mic ○ · píldora · enviar ○) --}}
                                        <div style="background:#000;border-top:1px solid rgba(255,255,255,0.08);padding:8px 10px;flex-shrink:0;">
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <div aria-hidden="true" style="width:32px;height:32px;flex:0 0 auto;border-radius:9999px;background:#06b6d4;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.35);">
                                                    <svg viewBox="0 0 384 512" style="width:13px;height:13px;fill:#fff;"><path d="M192 352c53 0 96-43 96-96V96c0-53-43-96-96-96S96 43 96 96v160c0 53 43 96 96 96zm128-128v32c0 70.7-57.3 128-128 128S64 326.7 64 256v-32H32v32c0 83.8 63.2 152.9 144 161.3V480h-48v32h128v-32h-48v-62.7c80.8-8.4 144-77.5 144-161.3v-32h-32z"/></svg>
                                                </div>
                                                <div style="flex:1;min-width:0;border-radius:9999px;background:#1f1d1b;border:1px solid rgba(255,255,255,0.2);padding:8px 14px;color:rgba(255,255,255,.45);font-size:12px;font-family:inherit;">Escribe tu mensaje...</div>
                                                <div aria-hidden="true" style="width:32px;height:32px;flex:0 0 auto;border-radius:9999px;background:#3b82f6;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.35);">
                                                    <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#fff;margin-left:1px;"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-4 text-xs text-white/45 text-center">
                                    El contenido se alimenta del menú del comercio (PDF / imágenes) procesado por IA.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="beneficios" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="flex items-end justify-between gap-6">
                    <div>
                        <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Beneficios que se notan en el salón</h2>
                        <p class="mt-3 text-[color:var(--muted)] max-w-2xl">
                            No es un PDF con QR: es un mozo digital que recomienda, toma el pre-pedido
                            y te muestra qué funciona y qué no.
                        </p>
                    </div>
                </div>

                <div class="mt-10 grid md:grid-cols-12 gap-4">
                    <div class="md:col-span-7 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Ticket promedio</p>
                        <h3 class="mt-3 font-display text-2xl">Más ventas por mesa</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            El asistente sugiere el maridaje, la guarnición o el postre en el momento justo.
                            Sube el ticket promedio sin que nadie sienta presión de venta.
                        </p>
                    </div>

                    <div class="md:col-span-5 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Operación</p>
                        <h3 class="mt-3 font-display text-2xl">Menos carga para el mozo</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            El pre-pedido llega armado y ordenado: el mozo confirma y sirve.
                            Menos idas y vueltas justo en las horas pico.
                        </p>
                    </div>

                    <div class="md:col-span-5 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Identidad</p>
                        <h3 class="mt-3 font-display text-2xl">Tu marca, no una plantilla</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            Colores, logo y hasta el tono de voz del asistente se configuran por local.
                            La experiencia se siente parte de tu negocio.
                        </p>
                    </div>

                    <div class="md:col-span-7 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Datos</p>
                        <h3 class="mt-3 font-display text-2xl">Decisiones con datos reales</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            Vas a saber qué preguntan tus clientes, qué platos generan más interés y
                            qué promo funciona, para ajustar la carta con criterio y no a ojo.
                        </p>
                    </div>
                </div>
            </section>

            <section id="como-funciona" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="rounded-[2.75rem] bg-white/3 ring-1 ring-white/10 p-8 sm:p-10">
                    <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Cómo funciona</h2>
                    <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 1</p>
                            <p class="mt-3 font-semibold text-lg">Cargás tu local</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Logo, colores y datos básicos. Listo en un par de minutos.</p>
                        </div>
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 2</p>
                            <p class="mt-3 font-semibold text-lg">Subís el menú</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">PDF o foto de tu carta. La IA extrae platos, precios y categorías solita.</p>
                        </div>
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 3</p>
                            <p class="mt-3 font-semibold text-lg">Ponés el QR en la mesa</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">El cliente escanea y ya está en tu menú. Sin apps ni registro.</p>
                        </div>
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 4</p>
                            <p class="mt-3 font-semibold text-lg">Medís resultados</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Qué preguntan, qué piden y qué promo funciona, con sugerencias para vender más.</p>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row gap-3">
                        <button type="button" data-open-contact class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition shadow-sm shadow-amber-500/20">
                            Quiero sumarme
                        </button>
                        <button type="button" data-open-contact class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                            Ver ayuda
                        </button>
                    </div>
                </div>
            </section>

            <!-- ── Casos de uso por rubro (long-tail SEO) ─────────── -->
            <section id="casos-de-uso" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Un menú digital para cada rubro</h2>
                <p class="mt-3 text-[color:var(--muted)] max-w-2xl">
                    La carta con QR se adapta a cómo trabaja tu local. Estos son los usos más comunes.
                </p>

                <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ([
                        ['🍽️', 'Restaurantes', 'Carta completa por categorías, con fotos de los platos y recomendaciones del asistente para subir el ticket promedio.'],
                        ['🍺', 'Bares y cervecerías', 'Tu carta de tragos y cervezas siempre actualizada. El asistente sugiere el maridaje con la picada o la tabla.'],
                        ['☕', 'Cafeterías y panaderías', 'Menú de desayunos y meriendas con opciones sin TACC, veganas o sin lactosa bien señalizadas.'],
                        ['🍕', 'Pizzerías y rotiserías', 'Variedades, tamaños y promociones del día visibles al instante, sin reimprimir la carta.'],
                        ['🚚', 'Food trucks', 'Un QR que funciona en cualquier lado. Cambiás el menú del día desde el celular en segundos.'],
                        ['🍦', 'Heladerías y postres', 'Sabores y combinaciones del día, con las opciones sin azúcar destacadas para quien las busca.'],
                    ] as [$emoji, $titulo, $texto])
                        <div class="rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                            <span class="text-2xl" aria-hidden="true">{{ $emoji }}</span>
                            <h3 class="mt-3 font-display text-xl">{{ $titulo }}</h3>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- ── Planes ─────────────────────────────────────────── -->
            <section id="planes" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="text-center mb-10">
                    <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Planes y precios</h2>
                    <p class="mt-3 text-[color:var(--muted)] max-w-xl mx-auto">Elegí el que mejor se adapta a tu negocio. Podés cambiar en cualquier momento.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($planes as $plan)
                        @php
                            $esPro     = $plan->key === 'pro';
                            $precio    = $plan->precio ? '$'.number_format($plan->precio, 0, ',', '.').'/mes' : 'A consultar';
                            $msgs      = $plan->mensajes_ia_mes ? number_format($plan->mensajes_ia_mes).' msgs IA/mes' : 'IA ilimitada';
                            $features  = [
                                'analytics'   => 'Analytics avanzado',
                                'excel'       => 'Exportar a Excel',
                                'promociones' => 'Gestión de promociones',
                                'intencion_ia'=> 'Clasificación de intención con IA',
                            ];
                        @endphp
                        <div class="relative rounded-3xl ring-1 p-7 flex flex-col gap-5
                            {{ $esPro ? 'bg-[var(--accent)]/10 ring-[var(--accent)]/40' : 'bg-white/3 ring-white/10' }}">

                            @if($esPro)
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[var(--accent)] text-black text-xs font-bold px-3 py-1 rounded-full">
                                    Más popular
                                </span>
                            @endif

                            <div>
                                <p class="text-xs text-white/50 font-semibold tracking-widest uppercase mb-2">{{ $plan->nombre }}</p>
                                <p class="text-3xl font-bold {{ $esPro ? 'text-[var(--accent)]' : 'text-white' }}">{{ $precio }}</p>
                                <p class="mt-2 text-sm text-[color:var(--muted)]">{{ $plan->descripcion }}</p>
                            </div>

                            <ul class="space-y-2 flex-1">
                                <li class="flex items-center gap-2 text-sm text-white/80">
                                    <span class="{{ $esPro ? 'text-[var(--accent)]' : 'text-white/40' }}">✓</span>
                                    {{ $msgs }}
                                </li>
                                @foreach($plan->features ?? [] as $feature)
                                    <li class="flex items-center gap-2 text-sm text-white/80">
                                        <span class="{{ $esPro ? 'text-[var(--accent)]' : 'text-white/40' }}">✓</span>
                                        {{ $features[$feature] ?? $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <button type="button" data-open-contact
                               class="mt-2 inline-flex justify-center items-center rounded-2xl font-semibold px-5 py-2.5 text-sm transition
                               {{ $esPro ? 'bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black shadow shadow-amber-500/20' : 'bg-white/5 hover:bg-white/10 text-white ring-1 ring-white/15' }}">
                                Quiero este plan
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Comparativa. Las columnas salen de las features reales de cada plan,
                     así que si agregás o quitás una en el panel, la tabla se actualiza sola. --}}
                @php
                    $etiquetas = [
                        'analytics' => 'Analíticas',
                        'excel' => 'Excel',
                        'promociones' => 'Promociones',
                        'intencion_ia' => 'Intención IA',
                    ];
                    $presentes = $planes->flatMap(fn ($p) => $p->features ?? [])->unique();
                    $columnas = collect(array_keys($etiquetas))->filter(fn ($f) => $presentes->contains($f))
                        ->merge($presentes->diff(array_keys($etiquetas)))->values();
                @endphp

                <div class="mt-12">
                    <h3 class="font-display text-2xl tracking-tight">Comparación rápida</h3>
                    <div class="mt-5 overflow-x-auto rounded-3xl ring-1 ring-white/10 bg-white/3">
                        <table class="w-full text-sm min-w-[640px]">
                            <thead>
                                <tr class="text-left text-white/50">
                                    <th class="font-semibold px-5 py-4">Plan</th>
                                    <th class="font-semibold px-5 py-4">Precio</th>
                                    <th class="font-semibold px-5 py-4">Consultas al asistente</th>
                                    <th class="font-semibold px-5 py-4">Calidad de IA</th>
                                    @foreach($columnas as $col)
                                        <th class="font-semibold px-5 py-4 whitespace-nowrap">{{ $etiquetas[$col] ?? ucfirst(str_replace('_', ' ', $col)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($planes as $plan)
                                    <tr class="border-t border-white/8 {{ $plan->key === 'pro' ? 'bg-[var(--accent)]/5' : '' }}">
                                        <td class="px-5 py-4 font-semibold text-white whitespace-nowrap">{{ $plan->nombre }}</td>
                                        <td class="px-5 py-4 text-white/80 whitespace-nowrap tabular-nums">
                                            {{ $plan->precio ? '$'.number_format($plan->precio, 0, ',', '.') : 'A consultar' }}
                                        </td>
                                        <td class="px-5 py-4 text-white/80 whitespace-nowrap tabular-nums">
                                            {{ $plan->mensajes_ia_mes ? number_format($plan->mensajes_ia_mes, 0, ',', '.').' / mes' : 'Ilimitadas' }}
                                        </td>
                                        <td class="px-5 py-4 text-white/80 whitespace-nowrap">
                                            {{ $plan->modelo_ia === 'gpt-4o' ? 'Avanzada' : 'Estándar' }}
                                        </td>
                                        @foreach($columnas as $col)
                                            <td class="px-5 py-4">
                                                @if(in_array($col, $plan->features ?? [], true))
                                                    <span class="text-[var(--accent)] font-bold" title="Incluido">✓</span>
                                                @else
                                                    <span class="text-white/25" title="No incluido">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-xs text-white/45">
                        Podés cambiar de plan en cualquier momento. Un local sin plan asignado queda en el plan inicial.
                    </p>
                </div>
            </section>

            <section id="probar" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="grid lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-5">
                        <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Probalo vos mismo</h2>
                        <p class="mt-3 text-[color:var(--muted)]">
                            Abrí un menú real y hacele preguntas al asistente, igual que lo haría un cliente
                            sentado en la mesa. No hace falta registrarse.
                        </p>
                    </div>
                    <div class="lg:col-span-7 rounded-[2.25rem] bg-white/3 ring-1 ring-white/10 p-6 sm:p-8">
                        @if($demoSlug)
                            <p class="text-sm font-semibold text-white/80">Menú de demostración</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">
                                Escaneá con los ojos, no con la cámara: entrá y probá el chat con una carta real.
                            </p>
                            <a href="{{ route('menu.public', $demoSlug) }}"
                               class="mt-4 inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition whitespace-nowrap">
                                Abrir el menú de demostración
                            </a>
                            <div class="mt-6 pt-6 border-t border-white/10"></div>
                        @endif

                        <label class="block text-sm font-semibold text-white/80" for="slug">
                            ¿Ya sos cliente? Entrá a tu menú
                        </label>
                        <div class="mt-3 flex flex-col sm:flex-row gap-3">
                            <input
                                id="slug"
                                name="slug"
                                placeholder="ej: mi-restaurante"
                                class="w-full rounded-2xl bg-black/30 ring-1 ring-white/12 focus:ring-white/25 px-4 py-3 text-white placeholder:text-white/40 outline-none"
                                autocomplete="off"
                            />
                            <button
                                id="openMenu"
                                type="button"
                                class="inline-flex justify-center items-center rounded-2xl bg-white/8 hover:bg-white/12 ring-1 ring-white/15 text-white font-semibold px-6 py-3 transition whitespace-nowrap"
                            >
                                Abrir menú
                            </button>
                        </div>
                        <p class="mt-3 text-xs text-white/50">
                            Es el nombre corto de tu local, el que aparece en la dirección de tu menú:
                            <code class="text-white/70">servime.net/menu/tu-local</code>
                        </p>
                    </div>
                </div>
            </section>

            <!-- ── Preguntas frecuentes (alimenta el schema FAQPage del <head>) ── -->
            <section id="preguntas-frecuentes" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Preguntas frecuentes</h2>
                <p class="mt-3 text-[color:var(--muted)] max-w-2xl">
                    Lo que más nos consultan sobre la carta digital con QR y el asistente con IA.
                </p>

                <div class="mt-10 grid lg:grid-cols-2 gap-4">
                    @foreach ($faqs as $faq)
                        <details class="group rounded-3xl bg-white/3 ring-1 ring-white/10 p-6 open:bg-white/5 transition">
                            <summary class="flex items-start justify-between gap-4 cursor-pointer list-none">
                                <h3 class="font-display text-lg sm:text-xl">{{ $faq['q'] }}</h3>
                                <span class="mt-1 shrink-0 text-[var(--accent)] transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                            </summary>
                            <p class="mt-3 text-sm text-[color:var(--muted)]">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

        </main>

        <footer class="border-t border-white/5">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div>
                        <p class="font-display text-lg">{{ config('app.name', 'ServiME') }}</p>
                        <p class="mt-1 text-sm text-white/55">Menú digital con IA para restaurantes y bares.</p>
                    </div>
                    <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm">
                        <a class="text-white/70 hover:text-white transition" href="#beneficios">Beneficios</a>
                        <a class="text-white/70 hover:text-white transition" href="#como-funciona">Cómo funciona</a>
                        <a class="text-white/70 hover:text-white transition" href="#casos-de-uso">Para tu rubro</a>
                        <a class="text-white/70 hover:text-white transition" href="#preguntas-frecuentes">Preguntas frecuentes</a>
                        <button type="button" class="text-white/70 hover:text-white transition" data-open-contact>Ayuda</button>
                        <a class="text-white/70 hover:text-white transition" href="{{ route('login') }}">Entrar</a>
                    </div>
                </div>

                {{-- ── Contador en vivo ──
                     Oculto hasta superar SiteVisit::MIN_VISITAS_CONTADOR: la prueba social con
                     números chicos resta credibilidad. El tracking sigue corriendo igual, así que
                     cuando haya volumen el contador aparece solo, sin tocar nada. --}}
                @if ($stats['mostrar'] ?? false)
                <div class="mt-10 pt-8 border-t border-white/5">
                    <div id="live-counter"
                         class="mx-auto flex w-full max-w-3xl flex-wrap items-center justify-center gap-x-6 gap-y-4
                                rounded-2xl bg-white/[0.03] ring-1 ring-white/10 px-6 py-4">

                        {{-- Navegando ahora --}}
                        <span class="inline-flex items-center gap-2.5">
                            <span class="relative flex h-2 w-2" aria-hidden="true">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60 animate-ping"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            </span>
                            <strong data-counter data-value="{{ $stats['activos'] ?? 0 }}"
                                    class="font-display text-lg tabular-nums text-white">0</strong>
                            <span class="text-sm text-white/55">navegando ahora</span>
                        </span>

                        <span class="hidden sm:block h-5 w-px bg-white/10" aria-hidden="true"></span>

                        {{-- Visitas totales --}}
                        <span class="inline-flex items-center gap-2.5">
                            <strong data-counter data-value="{{ $stats['visitas'] ?? 0 }}"
                                    class="font-display text-lg tabular-nums text-white">0</strong>
                            <span class="text-sm text-white/55">visitas</span>
                        </span>

                        <span class="hidden sm:block h-5 w-px bg-white/10" aria-hidden="true"></span>

                        {{-- Consultas resueltas por IA --}}
                        <span class="inline-flex items-center gap-2.5">
                            <svg class="h-4 w-4 text-[color:var(--accent)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 1.5l1.9 4.6 4.6 1.9-4.6 1.9L10 14.5 8.1 9.9 3.5 8l4.6-1.9L10 1.5zM4.5 13l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z"/>
                            </svg>
                            <strong data-counter data-value="{{ $stats['consultas_ia'] ?? 0 }}"
                                    class="font-display text-lg tabular-nums text-white">0</strong>
                            <span class="text-sm text-white/55">consultas resueltas por IA</span>
                        </span>
                    </div>

                </div>
                @endif
            </div>
        </footer>

        {{-- ── Demo toast ── --}}
        <div id="demo-toast" class="toast-hidden fixed top-20 right-4 sm:right-6 z-[60] w-[min(18rem,calc(100vw-2rem))] rounded-2xl bg-[#1b1814] ring-1 ring-white/15 shadow-xl shadow-black/50 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">¿Querés ver una demo?</p>
                    <p class="mt-0.5 text-xs text-white/60">Entrá a un menú real y probá el asistente.</p>
                </div>
                <button id="demo-toast-close" type="button" aria-label="Cerrar"
                        class="mt-0.5 text-white/40 hover:text-white/80 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <div class="mt-3 flex gap-2">
                <button id="demo-toast-yes" type="button"
                        class="flex-1 rounded-xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black text-xs font-semibold px-3 py-2 transition">
                    Ver demo
                </button>
                <button id="demo-toast-no" type="button"
                        class="flex-1 rounded-xl bg-white/6 ring-1 ring-white/12 hover:bg-white/10 text-white text-xs font-semibold px-3 py-2 transition">
                    No, gracias
                </button>
            </div>
        </div>

        {{-- ── Contact FAB ── --}}
        <div id="contact-fab" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
            {{-- Chat widget --}}
            <div id="contact-panel"
                 class="panel-hidden flex flex-col w-full sm:w-[380px] rounded-3xl bg-[#1b1814] ring-1 ring-white/15 shadow-2xl shadow-black/60 overflow-hidden"
                 style="height: min(520px, 75vh);"
                 role="dialog" aria-label="Chat ServiME" aria-modal="false">

                {{-- Header --}}
                <div class="shrink-0 flex items-center gap-3 px-4 py-3 border-b border-white/8 bg-gradient-to-r from-amber-500/10 via-transparent to-transparent">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 ring-1 ring-amber-500/40 shrink-0">
                        <span class="font-display text-base text-[color:var(--accent)]">S</span>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold leading-none">ServiME</p>
                        <p class="mt-0.5 text-xs text-white/55">Equipo de ventas</p>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs text-green-400 shrink-0">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-400 shadow-[0_0_6px_rgba(74,222,128,.7)]"></span>
                        En línea
                    </span>
                    <button id="chat-close-btn" type="button" aria-label="Cerrar chat"
                            class="ml-1 shrink-0 text-white/40 hover:text-white/80 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                {{-- Messages area (lazy populated by JS) --}}
                <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 overscroll-contain"
                     style="scroll-behavior: smooth;"></div>

                {{-- Sticky footer: direct contact buttons --}}
                <div class="shrink-0 px-4 pt-2 pb-2 border-t border-white/8 flex gap-2">
                    <a id="chat-footer-email"
                       href="https://mail.google.com/mail/?view=cm&fs=1&to={{ $supportEmail }}&su=Consulta%20sobre%20ServiME"
                       target="_blank" rel="noopener"
                       class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl bg-white/8 hover:bg-white/12 ring-1 ring-white/12 px-3 py-2 text-xs font-medium transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Enviar email
                    </a>
                    @if($supportWhatsapp)
                    <a id="chat-footer-wa"
                       href="https://wa.me/{{ $supportWhatsapp }}?text=Hola%2C%20quiero%20info%20sobre%20ServiME"
                       target="_blank" rel="noopener"
                       class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl bg-green-600/20 hover:bg-green-600/30 ring-1 ring-green-500/30 px-3 py-2 text-xs font-medium transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-green-400 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>
                    @endif
                </div>

                {{-- Input bar --}}
                <div class="shrink-0 px-4 pb-4 pt-2 flex gap-2 items-end border-t border-white/5">
                    <textarea id="chat-input" rows="1" placeholder="Escribí tu consulta..."
                              class="flex-1 resize-none rounded-2xl bg-black/30 ring-1 ring-white/12 focus:ring-amber-500/40 px-4 py-2.5 text-sm text-white placeholder:text-white/40 outline-none transition leading-snug overflow-hidden"
                              style="min-height: 2.5rem; max-height: 6rem;"></textarea>
                    <button id="chat-send-btn" type="button" aria-label="Enviar"
                            class="shrink-0 h-10 w-10 flex items-center justify-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black transition shadow-sm shadow-amber-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button id="contact-fab-btn" type="button" aria-label="Abrir chat de ayuda"
                    class="fab-live-anim flex items-center gap-2.5 h-14 pl-3 pr-5 rounded-full
                           bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black
                           shadow-lg shadow-amber-500/30 transition-colors">
                {{-- Ícono + punto verde: "hay alguien del otro lado" --}}
                <span class="relative flex h-9 w-9 items-center justify-center rounded-full bg-black/10 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z"/>
                    </svg>
                    <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-70 animate-ping"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-[color:var(--accent)]"></span>
                    </span>
                </span>

                {{-- Lo que faltaba: decir qué es --}}
                <span class="text-sm font-semibold whitespace-nowrap leading-none">¿Te ayudo?</span>
            </button>
        </div>

        <script>
            // Config bridge from PHP
            const SERVIME_CONFIG = {
                email:    '{{ $supportEmail }}',
                whatsapp: '{{ $supportWhatsapp }}'
            };
        </script>

        <script>
            // ── Slug menu ──────────────────────────────────────────
            const slugInput      = document.getElementById('slug');
            const openMenuButton = document.getElementById('openMenu');

            function openPublicMenu() {
                const slug = (slugInput?.value || '').trim();
                if (!slug) return;
                window.location.href = `${window.location.origin}/menu/${encodeURIComponent(slug)}`;
            }
            openMenuButton?.addEventListener('click', openPublicMenu);
            slugInput?.addEventListener('keydown', e => { if (e.key === 'Enter') openPublicMenu(); });

            // ── FAB open/close ─────────────────────────────────────
            const fabBtn       = document.getElementById('contact-fab-btn');
            const contactFab   = document.getElementById('contact-fab');
            const contactPanel = document.getElementById('contact-panel');
            let panelOpen = false;

            const ICON_CHAT  = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z"/></svg>`;
            const ICON_CLOSE = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;

            function openPanel() {
                panelOpen = true;
                contactPanel.classList.remove('panel-hidden');
                requestAnimationFrame(() => contactPanel.classList.remove('panel-closing'));
                fabBtn.classList.remove('fab-live-anim');
                fabBtn.innerHTML = ICON_CLOSE;
                fabBtn.setAttribute('aria-label', 'Cerrar chat');
                initChat();
            }
            function closePanel() {
                panelOpen = false;
                contactPanel.classList.add('panel-closing');
                fabBtn.innerHTML = ICON_CHAT;
                fabBtn.setAttribute('aria-label', 'Abrir chat de ventas');
                setTimeout(() => {
                    contactPanel.classList.add('panel-hidden');
                    fabBtn.classList.add('fab-live-anim');
                }, 200);
            }

            fabBtn?.addEventListener('click', e => {
                e.stopPropagation();
                panelOpen ? closePanel() : openPanel();
            });
            document.getElementById('chat-close-btn')?.addEventListener('click', e => {
                e.stopPropagation();
                closePanel();
            });

            contactPanel?.addEventListener('click', e => e.stopPropagation());

            document.addEventListener('click', e => {
                if (panelOpen && !contactFab.contains(e.target)) closePanel();
            });

            document.querySelectorAll('[data-open-contact]').forEach(el => {
                el.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    openPanel();
                    contactFab.scrollIntoView({ behavior: 'smooth', block: 'end' });
                });
            });

            // ── Demo toast ─────────────────────────────────────────
            const DEMO_KEY  = 'servime_landing_demo_seen';
            const demoToast = document.getElementById('demo-toast');

            function dismissDemoToast() {
                localStorage.setItem(DEMO_KEY, '1');
                demoToast?.classList.add('toast-hidden');
            }
            if (!localStorage.getItem(DEMO_KEY)) {
                setTimeout(() => demoToast?.classList.remove('toast-hidden'), 2000);
            }
            document.getElementById('demo-toast-yes')?.addEventListener('click', () => {
                dismissDemoToast();
                document.getElementById('probar')?.scrollIntoView({ behavior: 'smooth' });
                setTimeout(() => document.getElementById('slug')?.focus(), 600);
            });
            document.getElementById('demo-toast-no')?.addEventListener('click', dismissDemoToast);
            document.getElementById('demo-toast-close')?.addEventListener('click', dismissDemoToast);

            // ── Título al cambiar de pestaña ───────────────────────
            const ORIGINAL_TITLE = document.title;
            const AWAY_TITLE = '👀 Volvé, que el futuro te espera';
            document.addEventListener('visibilitychange', () => {
                document.title = document.hidden ? AWAY_TITLE : ORIGINAL_TITLE;
            });

            // ── Chat Logic ─────────────────────────────────────────
            const chatMessages = document.getElementById('chat-messages');
            const chatInput    = document.getElementById('chat-input');
            const chatSendBtn  = document.getElementById('chat-send-btn');
            const EMAIL    = SERVIME_CONFIG.email;
            const WHATSAPP = SERVIME_CONFIG.whatsapp;

            const QUICK_REPLIES = [
                {
                    label: '¿Cuánto cuesta?',
                    reply: 'Los planes de ServiME están diseñados para negocios de todos los tamaños. Ofrecemos distintos niveles según la cantidad de menús, comercios y funcionalidades activas.\n\n¿Querés que te cuente más detalles o preferís hablar con el equipo directamente?'
                },
                {
                    label: '¿Cómo funciona?',
                    reply: 'Es muy simple:\n\n**1.** Creás tu comercio con logo y datos básicos.\n**2.** Subís el menú (PDF o imágenes) — la IA extrae platos, precios y categorías automáticamente.\n**3.** Generás el QR — tus clientes lo escanean, ven el menú y pueden chatear con el bot en tiempo real.\n\n¿Querés probarlo ahora mismo?'
                },
                {
                    label: 'Quiero una demo',
                    reply: 'Podés probar la experiencia de tus clientes ahora mismo: bajá hasta la sección **"Probalo vos mismo"** y abrí el menú de demostración.\n\nSi querés una demo personalizada con tu propio menú, contactanos directamente.'
                },
                {
                    label: 'Tengo un restaurante',
                    reply: '¡Perfecto! ServiME está hecho para vos. Tus clientes escanean un QR en la mesa, ven tu menú en su celular y pueden preguntar al bot — que conoce tu menú completo.\n\nTambién pueden armar un pre-pedido para el mozo. Sin apps, sin fricción.\n\n¿Empezamos?'
                }
            ];

            let chatInitialized = false;
            let chatHistory     = [];
            let chatBusy        = false;

            // Session persistente por dispositivo
            const LANDING_SESSION_KEY = 'servime_landing_chat_session';
            function getLandingSessionId() {
                let id = localStorage.getItem(LANDING_SESSION_KEY);
                if (!id) {
                    id = 'lc_' + Date.now() + '_' + Math.random().toString(36).slice(2, 9);
                    localStorage.setItem(LANDING_SESSION_KEY, id);
                }
                return id;
            }

            const EMAIL_SVG = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>`;
            const WA_SVG   = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-green-400 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`;

            function escapeHtml(str) {
                return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                          .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            function buildContactButtons(userMsg) {
                const subj   = encodeURIComponent('Consulta sobre ServiME');
                const body   = encodeURIComponent(userMsg ? 'Hola, tengo una consulta: ' + userMsg : 'Hola, quiero más información sobre ServiME');
                const waText = encodeURIComponent(userMsg ? 'Hola, tengo una consulta sobre ServiME: ' + userMsg : 'Hola, quiero info sobre ServiME');
                const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(EMAIL)}&su=${subj}&body=${body}`;
                let html = `<div class="mt-2.5 flex flex-wrap gap-2">`;
                html += `<a href="${gmailUrl}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-white/8 hover:bg-white/12 ring-1 ring-white/12 px-3 py-1.5 text-xs font-medium transition">${EMAIL_SVG} Email</a>`;
                if (WHATSAPP) {
                    html += `<a href="https://wa.me/${WHATSAPP}?text=${waText}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-green-600/20 hover:bg-green-600/30 ring-1 ring-green-500/30 px-3 py-1.5 text-xs font-medium transition">${WA_SVG} WhatsApp</a>`;
                }
                html += `</div>`;
                return html;
            }

            function scrollChatToBottom() {
                requestAnimationFrame(() => { chatMessages.scrollTop = chatMessages.scrollHeight; });
            }

            function renderBotMessage(text, withButtons, userMsg) {
                const wrap = document.createElement('div');
                wrap.className = 'chat-msg-animate flex items-start gap-2.5';
                const formatted = text
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
                const avatar = `<span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white/5 ring-1 ring-amber-500/30 shrink-0 mt-0.5"><span class="font-display text-xs text-[color:var(--accent)]">S</span></span>`;
                wrap.innerHTML = `${avatar}<div class="rounded-2xl bg-white/5 ring-1 ring-white/10 px-4 py-3 text-sm leading-relaxed text-white/90 max-w-full">${formatted}${withButtons ? buildContactButtons(userMsg || null) : ''}</div>`;
                chatMessages.appendChild(wrap);
                scrollChatToBottom();
            }

            function renderUserMessage(text) {
                const wrap = document.createElement('div');
                wrap.className = 'chat-msg-animate flex justify-end';
                wrap.innerHTML = `<div class="rounded-2xl bg-[var(--accent)] text-black px-4 py-2.5 text-sm font-medium leading-snug max-w-[78%]">${escapeHtml(text)}</div>`;
                chatMessages.appendChild(wrap);
                scrollChatToBottom();
            }

            function renderQuickReplies() {
                const container = document.createElement('div');
                container.id = 'chat-qr-container';
                container.className = 'chat-msg-animate flex flex-wrap gap-2 pb-1';
                QUICK_REPLIES.forEach((qr, i) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'chat-qr-chip';
                    btn.textContent = qr.label;
                    btn.addEventListener('click', () => handleQuickReply(i));
                    container.appendChild(btn);
                });
                chatMessages.appendChild(container);
                scrollChatToBottom();
            }

            function showTyping() {
                const div = document.createElement('div');
                div.id = 'chat-typing';
                div.className = 'chat-msg-animate flex items-start gap-2.5';
                div.innerHTML = `<span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white/5 ring-1 ring-amber-500/30 shrink-0 mt-0.5"><span class="font-display text-xs text-[color:var(--accent)]">S</span></span><div class="rounded-2xl bg-white/5 ring-1 ring-white/10 px-4 py-3 flex items-center gap-1.5"><span class="chat-typing-dot"></span><span class="chat-typing-dot"></span><span class="chat-typing-dot"></span></div>`;
                chatMessages.appendChild(div);
                scrollChatToBottom();
            }
            function hideTyping() { document.getElementById('chat-typing')?.remove(); }

            function initChat() {
                if (chatInitialized) return;
                chatInitialized = true;
                showTyping();
                setTimeout(() => {
                    hideTyping();
                    renderBotMessage('¡Hola! 👋 Soy el asistente de ServiME. ¿En qué te puedo ayudar?', false);
                    renderQuickReplies();
                }, 900);
            }

            function fadeOutQuickReplies(then) {
                const c = document.getElementById('chat-qr-container');
                if (!c) { then?.(); return; }
                c.style.transition = 'opacity .15s ease, transform .15s ease';
                c.style.opacity    = '0';
                c.style.transform  = 'translateY(-4px)';
                setTimeout(() => { c.remove(); then?.(); }, 160);
            }

            function handleQuickReply(index) {
                fadeOutQuickReplies(() => {
                    const label = QUICK_REPLIES[index].label;
                    const reply = QUICK_REPLIES[index].reply;
                    renderUserMessage(label);
                    showTyping();
                    setTimeout(() => {
                        hideTyping();
                        renderBotMessage(reply, true);
                        renderQuickReplies();
                        // Registrar en historial para que la IA tenga contexto
                        chatHistory.push({ role: 'user',      content: label });
                        chatHistory.push({ role: 'assistant', content: reply });
                    }, 1000 + Math.random() * 400);
                });
            }

            function handleFreeText() {
                const msg = (chatInput?.value || '').trim();
                if (!msg || chatBusy) return;
                chatBusy = true;
                chatInput.value = '';
                autoGrowInput();
                chatSendBtn.disabled = true;

                fadeOutQuickReplies(() => {
                    renderUserMessage(msg);
                    showTyping();

                    fetch('{{ route("landing.chat") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type':  'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ message: msg, history: chatHistory, session_id: getLandingSessionId() }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        hideTyping();
                        const botReply = data.response || 'Hubo un problema. Contactanos directamente.';
                        chatHistory.push({ role: 'user',      content: msg      });
                        chatHistory.push({ role: 'assistant', content: botReply });
                        renderBotMessage(botReply, true, msg);
                        renderQuickReplies();
                    })
                    .catch(() => {
                        hideTyping();
                        renderBotMessage('Hubo un problema de conexión. Contactanos directamente:', true, msg);
                        renderQuickReplies();
                    })
                    .finally(() => {
                        chatBusy = false;
                        chatSendBtn.disabled = false;
                    });
                });
            }

            function autoGrowInput() {
                if (!chatInput) return;
                chatInput.style.height = 'auto';
                chatInput.style.height = Math.min(chatInput.scrollHeight, 96) + 'px';
            }

            chatInput?.addEventListener('input', autoGrowInput);
            chatInput?.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleFreeText(); }
            });
            chatSendBtn?.addEventListener('click', handleFreeText);

            // Auto-open when landing is loaded with ?chat=1 (e.g. from login "Ayuda" link)
            if (new URLSearchParams(window.location.search).get('chat') === '1') {
                setTimeout(openPanel, 500);
            }

            // ── Hero demo loop (real-chat aesthetics) ──────────────
            (function () {
                const area = document.getElementById('demo-chat-msgs');
                if (!area) return;

                const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                // Igual que en el producto real: el bot NO escribe precios ni listas.
                // Solo nombra los platos y la app renderiza la tarjeta con foto, precio
                // y el botón "Lo quiero" cuando termina de responder.
                const CONVOS = [
                    {
                        user: 'Somos cuatro, ¿qué nos recomendás para compartir?',
                        bot:  '¡Para una mesa de cuatro! 🍽️ Les propongo la **Milanesa napolitana**, que rinde muchísimo, y una **Fugazza** para arrancar mientras esperan. ¿Armamos el pedido?',
                        platos: [
                            { nombre: 'Milanesa napolitana', precio: '$3.800', desc: 'Con jamón, salsa y muzzarella gratinada.', foto: 'milanesa-napolitana' },
                            { nombre: 'Fugazza', precio: '$2.900', desc: 'Cebolla caramelizada y muzzarella.', foto: 'fugazza' },
                        ],
                    },
                    {
                        user: '¿Tienen algo sin TACC?',
                        bot:  '¡Sí! La **Ensalada verde** es sin TACC y bien fresca. Y si querés cerrar con algo dulce, el **Helado artesanal** también lo es. ¿Te sumo alguna?',
                        platos: [
                            { nombre: 'Ensalada verde', precio: '$1.800', desc: 'Rúcula, tomate cherry y parmesano.', foto: 'ensalada-verde' },
                            { nombre: 'Helado artesanal', precio: '$2.400', desc: '', foto: 'helado-artesanal' },
                        ],
                    },
                    {
                        user: 'Un vino para acompañar la milanesa',
                        bot:  'Va perfecto el **Malbec de la casa**: tiene cuerpo y equilibra la napolitana sin taparla. ¿Lo agrego al pedido?',
                        platos: [
                            { nombre: 'Malbec de la casa', precio: '$2.500', desc: 'Copa. Ideal para carnes y pastas.', foto: 'malbec-copa' },
                        ],
                    },
                ];

                // Fotos disponibles (las que existan en public/images/demo/)
                const DEMO_FOTOS = {!! $demoFotosJson !!};

                // Réplica de las tarjetas de plato del chat real
                function platoCard(p) {
                    const src = p.foto ? DEMO_FOTOS[p.foto] : null;
                    const img = src
                        ? `<img src="${src}" alt="${p.nombre}" width="600" height="340" loading="lazy" decoding="async"
                               style="width:100%;height:150px;object-fit:cover;display:block;"
                               onerror="this.style.display='none'">`
                        : '';

                    return `<div style="border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,0.1);background:rgba(0,0,0,0.25);box-shadow:0 2px 8px rgba(0,0,0,.35);">
                        ${img}
                        <div style="padding:9px 11px;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                <span style="font-weight:600;color:#fff;font-size:11.5px;line-height:1.3;">${p.nombre}</span>
                                <span style="color:#3b82f6;font-weight:700;white-space:nowrap;font-size:11.5px;">${p.precio}</span>
                            </div>
                            ${p.desc ? `<p style="color:#cbd5e1;font-size:10.5px;margin:3px 0 0;line-height:1.45;">${p.desc}</p>` : ''}
                            <span style="display:inline-block;margin-top:7px;padding:3px 10px;font-size:10.5px;border-radius:9999px;color:#fff;background:#3b82f6;border:1px solid rgba(255,255,255,.15);">Lo quiero</span>
                        </div>
                    </div>`;
                }

                // Réplica exacta del chat real: burbuja del bot en gris cálido con "cola"
                // arriba a la izquierda, y la del cliente en el azul de marca con cola a la derecha.
                const BUBBLE_BOT   = 'background:#37332e;border-radius:6px 20px 20px 20px;padding:11px 14px;border:1px solid rgba(255,255,255,0.05);box-shadow:0 4px 12px rgba(0,0,0,0.4);color:#fff;font-size:12px;line-height:1.55;';
                const BUBBLE_USER  = 'background:#3b82f6;border-radius:20px 6px 20px 20px;padding:10px 13px;max-width:82%;color:#fff;font-size:12px;line-height:1.5;box-shadow:0 4px 12px rgba(0,0,0,0.4);';
                const ANIM_LEFT    = reduced ? '' : 'animation:demo-slide-left .4s ease-out;';
                const ANIM_RIGHT   = reduced ? '' : 'animation:demo-slide-right .4s ease-out;';

                function fmt(t) { return t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>'); }

                // Al escribir token a token el corte puede caer dentro de un **negrita**.
                // Descartamos los asteriscos sueltos del final y cerramos la negrita abierta,
                // así nunca se ven los marcadores crudos mientras se escribe.
                function fmtPartial(t) {
                    t = t.replace(/\*+$/, '');
                    if (((t.match(/\*\*/g) || []).length) % 2 === 1) {
                        t += '**';
                    }

                    return fmt(t);
                }
                function schedule(fn, ms) { return setTimeout(fn, reduced ? Math.min(ms, 80) : ms); }

                // Indicador compacto de 3 puntos, igual al del menú real (sin texto)
                function showTyping() {
                    const row = document.createElement('div');
                    row.id = 'demo-typing';
                    row.style.cssText = 'display:flex;' + ANIM_LEFT;
                    const dots = reduced
                        ? `<span style="color:#cbd5e1;font-size:14px;letter-spacing:2px;">…</span>`
                        : `<span class="demo-tdot"></span><span class="demo-tdot"></span><span class="demo-tdot"></span>`;
                    row.innerHTML = `<div style="${BUBBLE_BOT}display:inline-flex;align-items:center;gap:5px;padding:12px 14px;">${dots}</div>`;
                    area.appendChild(row);
                }

                function runConvo(idx) {
                    const { user, bot } = CONVOS[idx % CONVOS.length];
                    area.innerHTML = '';

                    // user bubble container (right-aligned)
                    const uRow = document.createElement('div');
                    uRow.style.cssText = 'display:flex;justify-content:flex-end;' + ANIM_RIGHT;
                    const uBubble = document.createElement('div');
                    uBubble.style.cssText = BUBBLE_USER;
                    uRow.appendChild(uBubble);
                    area.appendChild(uRow);

                    // typewriter
                    let i = 0;
                    function typeUser() {
                        if (i <= user.length) {
                            uBubble.textContent = user.slice(0, i++);
                            schedule(typeUser, 38);
                        } else {
                            schedule(() => {
                                showTyping();
                                schedule(showBotMsg, 1100);
                            }, 280);
                        }
                    }

                    // La respuesta se escribe token a token, igual que el streaming real (SSE).
                    function showBotMsg() {
                        document.getElementById('demo-typing')?.remove();
                        const el = document.createElement('div');
                        el.style.cssText = BUBBLE_BOT + ANIM_LEFT;
                        const txt = document.createElement('span');
                        const caret = document.createElement('span');
                        caret.className = 'demo-caret';
                        el.appendChild(txt);
                        el.appendChild(caret);
                        area.appendChild(el);

                        // Las tarjetas de plato llegan al final, como el evento "done" del stream real
                        function showPlatos() {
                            const platos = CONVOS[idx % CONVOS.length].platos || [];
                            if (! platos.length) {
                                schedule(fadeOut, 3200);

                                return;
                            }
                            const wrap = document.createElement('div');
                            wrap.style.cssText = 'margin-top:9px;display:flex;flex-direction:column;gap:8px;' + ANIM_LEFT;
                            wrap.innerHTML = platos.map(platoCard).join('')
                                + `<span style="align-self:flex-start;display:inline-flex;align-items:center;padding:4px 10px;border-radius:9999px;border:1px solid rgba(59,130,246,.4);color:#3b82f6;font-size:10.5px;">Ver más opciones ↓</span>`;
                            el.appendChild(wrap);
                            area.scrollTop = area.scrollHeight;
                            schedule(fadeOut, 3400);
                        }

                        if (reduced) {
                            txt.innerHTML = fmt(bot);
                            caret.remove();
                            showPlatos();

                            return;
                        }

                        // Trozos de 1 a 3 caracteres: imita el ritmo irregular de los tokens
                        let n = 0;
                        (function stream() {
                            if (n >= bot.length) {
                                caret.remove();
                                setTimeout(showPlatos, 420);

                                return;
                            }
                            n = Math.min(bot.length, n + 1 + Math.floor(Math.random() * 3));
                            txt.innerHTML = fmtPartial(bot.slice(0, n));
                            area.scrollTop = area.scrollHeight;
                            setTimeout(stream, 16 + Math.random() * 26);
                        })();
                    }

                    function fadeOut() {
                        area.style.transition = 'opacity .4s';
                        area.style.opacity = '0';
                        schedule(() => {
                            area.style.transition = '';
                            area.style.opacity = '1';
                            runConvo(idx + 1);
                        }, 420);
                    }

                    schedule(typeUser, 0);
                }

                schedule(() => runConvo(0), 600);
            })();
        </script>

        {{-- ── Confirmación de visita humana ──
             Los bots y crawlers no ejecutan JS, así que nunca llegan a este fetch:
             su visita queda guardada pero no suma en ninguna métrica. --}}
        <script>
            (() => {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!token) return;

                // Un pequeño delay evita contar cargas que se abandonan al instante
                setTimeout(() => {
                    fetch('{{ route('visit.confirm') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token },
                        keepalive: true,
                    }).catch(() => { /* nunca romper la página por el contador */ });
                }, 1500);
            })();
        </script>

        {{-- ── Contador en vivo del footer ── --}}
        <script>
            (() => {
                const panel = document.getElementById('live-counter');
                if (!panel) return;

                const nodes = [...panel.querySelectorAll('[data-counter]')];
                const nf = new Intl.NumberFormat('es-AR');
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                // Anima de su valor actual al nuevo (easing suave, ~1.1s)
                function animar(el, hasta) {
                    const desde = Number(el.dataset.shown || 0);
                    if (desde === hasta) return;

                    el.dataset.shown = String(hasta);

                    if (reduce) {
                        el.textContent = nf.format(hasta);
                        return;
                    }

                    const dur = 1100;
                    const t0 = performance.now();

                    function frame(t) {
                        const p = Math.min((t - t0) / dur, 1);
                        const eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
                        el.textContent = nf.format(Math.round(desde + (hasta - desde) * eased));
                        if (p < 1) requestAnimationFrame(frame);
                    }
                    requestAnimationFrame(frame);
                }

                function pintar() {
                    nodes.forEach(el => animar(el, Number(el.dataset.value || 0)));
                }

                // Trae los números frescos del server (incluye la visita actual)
                async function refrescar() {
                    if (document.hidden) return;
                    try {
                        const r = await fetch('{{ route('site.stats') }}', { headers: { 'Accept': 'application/json' } });
                        if (!r.ok) return;
                        const s = await r.json();
                        const map = [s.activos, s.visitas, s.consultas_ia];
                        nodes.forEach((el, i) => {
                            if (typeof map[i] === 'number') {
                                el.dataset.value = String(map[i]);
                                animar(el, map[i]);
                            }
                        });
                    } catch (_) { /* el contador nunca rompe la página */ }
                }

                // Contar recién cuando el footer entra en pantalla
                const io = new IntersectionObserver((entries, obs) => {
                    if (!entries.some(e => e.isIntersecting)) return;
                    obs.disconnect();

                    pintar();                       // anima los valores del server-render
                    setTimeout(refrescar, 1500);    // ya con la visita actual contada
                    setInterval(refrescar, 30000);  // y en vivo de ahí en más
                }, { threshold: 0.35 });
                io.observe(panel);
            })();
        </script>

    </body>
</html>


