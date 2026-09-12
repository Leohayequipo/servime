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
                    'q' => '¿El menú funciona en otros idiomas?',
                    // Se deriva de IDIOMAS y no de NOMBRES: NOMBRES tiene entradas de más
                    // (francés, alemán) que todavía no están habilitadas ni tienen su
                    // lang/<code>.json. Anunciarlas sería prometer algo que no funciona.
                    'a' => 'Sí, y de forma automática. El menú y el asistente detectan el idioma del celular del cliente y le responden en '.
                        \Illuminate\Support\Str::replaceLast(', ', ' o ', implode(', ', array_intersect_key(
                            \App\Http\Middleware\SetLocale::NOMBRES,
                            \App\Http\Middleware\SetLocale::IDIOMAS
                        ))).
                        '. El cliente también puede elegirlo a mano desde el menú. Vos cargás la carta una sola vez y no tenés que mantener ni traducir otra versión: el asistente se encarga solo.',
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
                    'a' => 'Pueden armar un pre-pedido: marcan los platos que quieren con el botón "Lo quiero" y se genera un resumen con el total estimado para mostrarle al camarero. Esto agiliza la toma del pedido y reduce errores.',
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
                            'Pre-pedido para el camarero',
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
        <meta name="theme-color" content="#F2EDE3" />

        <meta property="og:site_name" content="ServiME" />
        <meta property="og:title" content="Menú digital con QR e IA para restaurantes y bares" />
        <meta property="og:description" content="Carta digital con QR + asistente de IA entrenado con tu menú + pre-pedido para el camarero. Sin apps, listo en minutos." />
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
        <link href="https://fonts.bunny.net/css?family=nunito-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* ── Identidad ServiME ──
               Verde profundo como color principal, crema en los fondos y terracota
               como acento y llamado a la acción.

               Los dos terracotas NO son un descuido: el #E07A5F de la paleta de marca
               da 2.53:1 sobre crema y 2.95:1 con texto blanco, así que no se puede usar
               para texto ni para el fondo de un botón (el mínimo WCAG es 4.5:1).
               Queda como color decorativo, y para todo lo que se lee van los dos
               derivados oscurecidos. Mismo caso con el salvia. */
            /* Los nombres de acá son alias: los valores salen de los tokens de app.css,
               que la landing ya carga con Vite. Antes eran hex escritos a mano y los mismos
               colores estaban copiados en el login, el «olvidé mi contraseña» y el reseteo,
               así que cambiar el terracota eran cuatro ediciones a mano. Las variantes
               (--dark-*, --line-soft, los shadows) se derivan del mismo token con alfa en
               vez de tener su propio hex: así no pueden quedar desfasadas del color base. */
            :root {
                --ground:       rgb(var(--c-bg));        /* crema: fondo dominante */
                --ground-2:     rgb(var(--c-bg-2));      /* crema apagado: zonas alternas */
                --surface:      rgb(var(--c-elev-2));    /* papel: tarjetas sobre el crema */
                --ink:          rgb(var(--c-ink));       /* verde profundo: texto principal */
                --muted:        rgb(var(--c-ink-soft));  /* salvia oscurecido: texto secundario (4,65:1) */
                --line:         rgb(var(--c-ink) / .14);
                --line-soft:    rgb(var(--c-ink) / .07);

                --accent:       rgb(var(--c-accent));       /* terracota acción: fondo de CTA (4,53:1 con blanco) */
                --accent2:      rgb(var(--c-accent-deep));  /* terracota acción en hover */
                /* Era #B4462A (4,67:1). `accent-deep` cumple el mismo rol —terracota para
                   texto sobre crema— y mide 5,03:1, así que sobraba tener un cuarto. */
                --accent-ink:   rgb(var(--c-accent-deep));  /* terracota texto: links y acentos */
                --accent-soft:  rgb(var(--c-accent-soft));  /* terracota de marca: solo decorativo */
                --on-accent:    rgb(var(--c-elev-2));

                --sage:         rgb(var(--c-sage));
                --sage-soft:    rgb(var(--c-sage-soft));
                --wood:         rgb(var(--c-wood));

                /* Zonas de contraste: verde profundo de fondo, crema de texto */
                --dark-ground:  rgb(var(--c-ink));
                --dark-ink:     rgb(var(--c-bg));
                --dark-muted:   rgb(var(--c-sage-soft));
                --dark-line:    rgb(var(--c-bg) / .16);
                --dark-surface: rgb(var(--c-bg) / .06);

                --shadow-sm: 0 1px 2px rgb(var(--c-ink) / .06), 0 4px 12px rgb(var(--c-ink) / .05);
                --shadow-md: 0 2px 6px rgb(var(--c-ink) / .07), 0 12px 32px rgb(var(--c-ink) / .07);
                --shadow-lg: 0 8px 24px rgb(var(--c-ink) / .10), 0 24px 60px rgb(var(--c-ink) / .10);
            }

            body {
                font-family: "Nunito Sans", ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial,
                    "Apple Color Emoji", "Segoe UI Emoji";
                background: var(--ground);
                color: var(--ink);
            }

            .font-display {
                font-family: Fraunces, ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
                font-weight: 600;
                letter-spacing: -0.015em;
                text-wrap: balance;
            }

            /* Textura de papel: sobre crema va en multiply, no en overlay */
            .grain::before {
                content: "";
                position: fixed;
                inset: 0;
                pointer-events: none;
                background-image:
                    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)' opacity='.22'/%3E%3C/svg%3E");
                opacity: .035;
                mix-blend-mode: multiply;
                z-index: 0;
            }

            /* ── Utilidades de la identidad ──
               Las superficies están acá y no en clases de Tailwind sueltas para que el
               próximo cambio de identidad sean estos tokens y no 250 clases repartidas. */
            .surface      { background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-sm); }
            .surface-flat { background: var(--surface); border: 1px solid var(--line); }
            .surface-alt  { background: var(--ground-2); border: 1px solid var(--line); }

            .band-dark { background: var(--dark-ground); color: var(--dark-ink); }
            .band-dark .text-muted { color: var(--dark-muted); }

            .text-muted { color: var(--muted); }
            .text-accent { color: var(--accent-ink); }

            .btn {
                display: inline-flex; align-items: center; justify-content: center;
                font-weight: 600; border-radius: 1rem; padding: .8rem 1.6rem;
                transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease;
                text-align: center; line-height: 1.2;
            }
            .btn:active { transform: translateY(1px); }

            .btn-primary { background: var(--accent); color: var(--on-accent); box-shadow: var(--shadow-sm); }
            .btn-primary:hover { background: var(--accent2); }

            .btn-ghost { background: transparent; color: var(--ink); border: 1px solid var(--line); }
            .btn-ghost:hover { background: var(--surface); border-color: var(--sage); }

            /* Sobre las bandas verdes hay que invertir: el ghost oscuro no se ve */
            .band-dark .btn-ghost { color: var(--dark-ink); border-color: var(--dark-line); }
            .band-dark .btn-ghost:hover { background: var(--dark-surface); border-color: var(--dark-muted); }

            .btn-sm { padding: .6rem 1.2rem; font-size: .875rem; border-radius: .875rem; }

            /* Foco visible en toda la página: sin esto el teclado navega a ciegas */
            a:focus-visible, button:focus-visible, input:focus-visible,
            textarea:focus-visible, summary:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 2px;
                border-radius: .5rem;
            }

            /* Subrayado del titular del hero. Va como background del propio span y no como
               pseudo-elemento con z-index negativo: así queda siempre detrás del glifo,
               sin depender del contexto de apilado del hero. */
            .brush-underline {
                background-image: linear-gradient(
                    to top,
                    rgba(224, 122, 95, .34) 0,
                    rgba(224, 122, 95, .34) .30em,
                    transparent .30em
                );
                border-radius: .18em;
                padding: 0 .05em;
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
                0%   { box-shadow: 0 0 0 0   rgba(192,86,58,.55), 0 8px 24px rgba(40,66,54,.28); }
                22%  { box-shadow: 0 0 0 13px rgba(192,86,58,0),  0 8px 24px rgba(40,66,54,.28); }
                100% { box-shadow: 0 0 0 0   rgba(192,86,58,0),   0 8px 24px rgba(40,66,54,.28); }
            }
            .fab-live-anim {
                animation: fab-float 3s ease-in-out infinite,
                           fab-ring  4.5s ease-out infinite;
                animation-delay: 0s, .9s;
            }
            @media (prefers-reduced-motion: reduce) {
                .fab-live-anim { animation: none; box-shadow: 0 8px 24px rgba(40,66,54,.28); }
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
                width: 6px; height: 6px; background: var(--sage);
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
                width:6px; height:6px; background:var(--sage-soft); border-radius:50%; display:inline-block;
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
                background:var(--accent-soft); animation: demo-caret 1s step-end infinite;
            }
            @media (prefers-reduced-motion: reduce) {
                .demo-tdot { animation:none; opacity:.6; }
                .demo-caret { animation:none; opacity:0; }
            }

            .chat-qr-chip {
                display: inline-flex; align-items: center; padding: 5px 12px;
                border-radius: 9999px; font-size: .75rem; font-weight: 600;
                background: var(--ground); border: 1px solid var(--line);
                color: var(--muted); cursor: pointer; white-space: nowrap;
                transition: background .15s, border-color .15s, color .15s;
            }
            .chat-qr-chip:hover {
                background: rgba(224,122,95,.12); border-color: rgba(192,86,58,.4); color: var(--accent-ink);
            }

            #chat-messages::-webkit-scrollbar       { width: 4px; }
            #chat-messages::-webkit-scrollbar-track  { background: transparent; }
            #chat-messages::-webkit-scrollbar-thumb  { background: var(--line); border-radius: 4px; }
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
        {{-- El header baja de 140px a 48px de alto: a 140 se comía la mitad del hero --}}
        <header id="site-header" class="sticky top-0 z-50 transition-shadow"
                style="background: rgba(242,237,227,.88); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line);">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-2.5">
                    {{-- El logo es circular y a 48px el texto de adentro no se leía.
                         El WebP pesa 16 KB contra 121 del PNG, y es la imagen que abre la
                         página: acá el peso se paga en el LCP. --}}
                    <a href="{{ url('/') }}" class="flex items-center group" aria-label="ServiME, ir al inicio">
                        <picture>
                            <source srcset="{{ asset('images/logo_servime.webp') }}" type="image/webp">
                            <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME — menú digital con QR e IA"
                                 width="64" height="64" fetchpriority="high" decoding="async"
                                 style="height:64px;width:64px;" class="transition group-hover:scale-105">
                        </picture>
                    </a>

                    <nav class="flex items-center gap-1 sm:gap-2">
                        <a href="#como-funciona" class="hidden md:inline-flex text-sm font-medium text-muted hover:text-[color:var(--ink)] transition px-3 py-2 rounded-lg">Cómo funciona</a>
                        <a href="#beneficios" class="hidden md:inline-flex text-sm font-medium text-muted hover:text-[color:var(--ink)] transition px-3 py-2 rounded-lg">Beneficios</a>
                        <a href="#planes" class="hidden md:inline-flex text-sm font-medium text-muted hover:text-[color:var(--ink)] transition px-3 py-2 rounded-lg">Planes</a>
                        <button type="button" data-open-contact class="hidden lg:inline-flex text-sm font-medium text-muted hover:text-[color:var(--ink)] transition px-3 py-2 rounded-lg">Ayuda</button>
                        <a href="{{ route('login') }}" class="inline-flex text-sm font-medium text-muted hover:text-[color:var(--ink)] transition px-3 py-2 rounded-lg">Entrar</a>
                        <button type="button" data-open-contact class="btn btn-primary btn-sm">
                            Quiero sumarme
                        </button>
                    </nav>
                </div>
            </div>
        </header>

        <main class="relative" style="z-index:1;">
            @if(session('info'))
                <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pt-5">
                    <div class="rounded-2xl px-5 py-3 text-sm" style="background:rgba(212,176,140,.22); border:1px solid rgba(212,176,140,.55); color:var(--ink);">
                        {{ session('info') }}
                    </div>
                </div>
            @endif

            <section class="relative overflow-hidden">
                {{-- Halo cálido detrás del teléfono. Muy suave: la calidez la da el crema,
                     no un degradado marcado. --}}
                <div aria-hidden="true" class="pointer-events-none absolute inset-0"
                     style="background:
                        radial-gradient(900px 520px at 78% 18%, rgba(212,176,140,.30), transparent 62%),
                        radial-gradient(700px 480px at 12% 8%, rgba(167,188,168,.22), transparent 60%);"></div>

                <div class="relative mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pt-12 sm:pt-16 pb-16">
                    <div class="grid lg:grid-cols-12 gap-12 lg:gap-10 items-center">
                        <div class="lg:col-span-6">
                            {{-- Deja claro desde el primer renglón a quién le hablamos: al dueño
                                 del local, no al comensal. --}}
                            <p class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-semibold tracking-wide"
                               style="background:var(--surface); border:1px solid var(--line); color:var(--muted);">
                                <span class="inline-block h-1.5 w-1.5 rounded-full" style="background:var(--accent-soft)"></span>
                                Para restaurantes, bares y cafeterías
                            </p>

                            <h1 class="font-display mt-5 text-[2.6rem] leading-[1.06] sm:text-5xl lg:text-6xl">
                                Tu menú habla.<br>
                                La <span class="brush-underline">IA recomienda</span>.<br>
                                Tus clientes piden.
                            </h1>

                            <p class="mt-6 text-base sm:text-lg text-muted max-w-xl">
                                ServiME convierte tu carta en un asistente que conversa con tus clientes,
                                les recomienda con los platos reales de tu menú y arma el pre-pedido para el camarero.
                                Sin apps, sin login y sin reimprimir cartas.
                            </p>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="button" data-open-contact class="btn btn-primary">
                                    Quiero sumarme
                                </button>
                                @if($demoSlug)
                                    <a href="{{ route('menu.public', $demoSlug) }}" class="btn btn-ghost">
                                        Ver un menú de verdad
                                    </a>
                                @else
                                    <a href="#como-funciona" class="btn btn-ghost">
                                        Ver cómo funciona
                                    </a>
                                @endif
                            </div>

                            {{-- Anclas rápidas de valor (narrativa de la propuesta comercial).
                                 El número de idiomas sale de SetLocale::IDIOMAS para que no se
                                 desincronice si mañana se suma uno. --}}
                            @php $totalIdiomas = count(\App\Http\Middleware\SetLocale::IDIOMAS); @endphp
                            <dl class="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-5 max-w-xl">
                                @foreach ([
                                    ['QR', 'Sin apps ni login'],
                                    ['IA', 'Recomienda tu menú real'],
                                    [$totalIdiomas.' idiomas', 'Detecta el del cliente'],
                                    ['24/7', 'Atiende siempre'],
                                ] as [$dato, $detalle])
                                    <div class="border-t pt-3" style="border-color:var(--line);">
                                        <dt class="font-display text-2xl sm:text-3xl" style="color:var(--accent-ink);">{{ $dato }}</dt>
                                        <dd class="mt-1 text-xs sm:text-sm text-muted">{{ $detalle }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>

                        <div class="lg:col-span-6">
                            <div class="relative mx-auto" style="max-width:330px;">
                                {{-- Teléfono real: bisel oscuro, pantalla en crema y sombra proyectada.
                                     Antes era una "ventana" con los tres puntitos de macOS, que no es
                                     lo que ve el cliente. --}}
                                <div class="relative" style="transform: rotate(-1.6deg);">
                                    <div style="border-radius:2.6rem; background:linear-gradient(160deg,#2F4A3C,#1E3328); padding:11px;
                                                box-shadow: var(--shadow-lg), 0 0 0 1px rgba(40,66,54,.35);">
                                        <div class="relative overflow-hidden" style="border-radius:2rem; background:var(--surface); display:flex; flex-direction:column; height:600px;">

                                            {{-- Barra de estado: hace que se lea como teléfono y no como widget --}}
                                            <div style="flex-shrink:0; display:flex; align-items:center; justify-content:space-between; padding:9px 18px 3px; font-size:11px; font-weight:700; color:var(--ink);">
                                                <span>9:41</span>
                                                <span style="display:flex; gap:3px; align-items:center;" aria-hidden="true">
                                                    <svg viewBox="0 0 18 12" style="width:16px;height:11px;fill:var(--ink);"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="4.5" y="6" width="3" height="6" rx="1"/><rect x="9" y="3" width="3" height="9" rx="1"/><rect x="13.5" y="0" width="3" height="12" rx="1" opacity=".35"/></svg>
                                                    <svg viewBox="0 0 24 12" style="width:20px;height:10px;fill:none;stroke:var(--ink);stroke-width:1.2;"><rect x="1" y="1" width="19" height="10" rx="3"/><rect x="2.8" y="2.8" width="13" height="6.4" rx="1.6" fill="var(--ink)" stroke="none"/><path d="M21.5 4.5v3" stroke-linecap="round"/></svg>
                                                </span>
                                            </div>

                                            {{-- Cabecera del menú: acá manda la marca del comercio, por eso
                                                 va el verde y no el terracota de ServiME. --}}
                                            <div style="background:var(--dark-ground); padding:12px 14px 11px; flex-shrink:0;">
                                                <div style="display:flex; justify-content:center; margin-bottom:7px;">
                                                    <div style="width:44px;height:44px;background:var(--wood);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,.25);">
                                                        <span style="font-size:18px;">🍽️</span>
                                                    </div>
                                                </div>
                                                <div style="text-align:center;">
                                                    <p style="color:var(--dark-ink);font-weight:700;font-size:13.5px;margin:0;line-height:1.3;">La Cantina</p>
                                                    <p style="color:var(--dark-muted);font-size:11px;margin:2px 0 0;line-height:1.3;">Menú digital</p>
                                                </div>
                                                <div style="margin-top:9px;background:var(--accent);border-radius:10px;padding:6px 10px;display:flex;align-items:center;gap:7px;">
                                                    <div style="width:18px;height:18px;background:rgba(255,255,255,0.22);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                        <span style="font-size:10px;">✨</span>
                                                    </div>
                                                    <span style="color:#fff;font-size:12px;font-weight:600;">Asistente ServiME</span>
                                                </div>
                                            </div>

                                            {{-- Messages area --}}
                                            <div id="demo-chat-msgs" style="flex:1;overflow-y:auto;scroll-behavior:smooth;padding:12px;display:flex;flex-direction:column;gap:10px;background:var(--ground);min-height:0;"></div>

                                            {{-- Input bar (réplica de la barra real: mic ○ · píldora · enviar ○) --}}
                                            <div style="background:var(--surface);border-top:1px solid var(--line);padding:9px 10px 11px;flex-shrink:0;">
                                                <div style="display:flex;align-items:center;gap:6px;">
                                                    <div aria-hidden="true" style="width:32px;height:32px;flex:0 0 auto;border-radius:9999px;background:var(--sage);display:flex;align-items:center;justify-content:center;">
                                                        <svg viewBox="0 0 384 512" style="width:13px;height:13px;fill:#fff;"><path d="M192 352c53 0 96-43 96-96V96c0-53-43-96-96-96S96 43 96 96v160c0 53 43 96 96 96zm128-128v32c0 70.7-57.3 128-128 128S64 326.7 64 256v-32H32v32c0 83.8 63.2 152.9 144 161.3V480h-48v32h128v-32h-48v-62.7c80.8-8.4 144-77.5 144-161.3v-32h-32z"/></svg>
                                                    </div>
                                                    <div style="flex:1;min-width:0;border-radius:9999px;background:var(--ground);border:1px solid var(--line);padding:8px 14px;color:var(--muted);font-size:12px;">Escribí tu mensaje...</div>
                                                    <div aria-hidden="true" style="width:32px;height:32px;flex:0 0 auto;border-radius:9999px;background:var(--accent);display:flex;align-items:center;justify-content:center;">
                                                        <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#fff;margin-left:1px;"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-6 text-xs text-muted text-center max-w-[17rem] mx-auto">
                                    El asistente se alimenta del menú del comercio (PDF o fotos) procesado por IA.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="beneficios" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                <div class="flex items-end justify-between gap-6">
                    <div>
                        <h2 class="font-display text-3xl sm:text-4xl">Beneficios que se notan en el salón</h2>
                        <p class="mt-3 text-muted max-w-2xl">
                            No es un PDF con QR: es un camarero digital que recomienda, toma el pre-pedido
                            y te muestra qué funciona y qué no.
                        </p>
                    </div>
                </div>

                {{-- Ilustraciones dibujadas al mismo peso de trazo que el logo (1.6, puntas
                     redondeadas) para que se lean como familia y no como íconos de banco. --}}
                @php
                    $ilustraciones = [
                        'ventas' => '<circle cx="24" cy="30" r="11"/><path d="M13 30h22"/><path d="M20 12h11a4 4 0 0 1 4 4v3a4 4 0 0 1-4 4h-6l-4 3v-3a4 4 0 0 1-4-4v-3a4 4 0 0 1 3-3.87Z"/><path d="M25.5 15.5v4M23.5 17.5h4"/>',
                        'mozo'   => '<rect x="12" y="9" width="24" height="27" rx="4"/><path d="M18 17h12M18 23h12M18 29h7"/><path d="M27.5 33.5l3 3 6-6.5"/>',
                        'marca'  => '<path d="M13 16h18v13a7 7 0 0 1-7 7h-4a7 7 0 0 1-7-7Z"/><path d="M31 20h3a4 4 0 0 1 0 8h-3"/><path d="M17 12c0-1.5 1.5-1.5 1.5-3M22 12c0-1.5 1.5-1.5 1.5-3M27 12c0-1.5 1.5-1.5 1.5-3"/>',
                        'datos'  => '<path d="M11 37h26"/><rect x="14" y="26" width="5" height="11" rx="2"/><rect x="23" y="19" width="5" height="18" rx="2"/><rect x="32" y="12" width="5" height="25" rx="2"/><path d="M12 21c4-6 9 2 13-4s5-5 10-6"/>',
                        'idiomas'=> '<circle cx="24" cy="24" r="14"/><path d="M10 24h28"/><path d="M24 10c3.8 4.2 5.6 8.9 5.6 14S27.8 33.8 24 38c-3.8-4.2-5.6-8.9-5.6-14S20.2 14.2 24 10Z"/>',
                    ];
                @endphp

                <div class="mt-10 grid md:grid-cols-12 gap-4">
                    {{-- La clase del grid va entera en el array: Tailwind escanea el archivo como
                         texto, así que una clase armada con interpolación nunca se compila. --}}
                    @foreach ([
                        ['ventas', 'md:col-span-7', 'Ticket promedio', 'Más ventas por mesa', 'El asistente sugiere el maridaje, la guarnición o el postre en el momento justo. Sube el ticket promedio sin que nadie sienta presión de venta.'],
                        ['mozo',   'md:col-span-5', 'Operación',       'Menos carga para el camarero', 'El pre-pedido llega armado y ordenado: el camarero confirma y sirve. Menos idas y vueltas justo en las horas pico.'],
                        ['marca',  'md:col-span-5', 'Identidad',       'Tu marca, no una plantilla', 'Colores, logo y hasta el tono de voz del asistente se configuran por local. La experiencia se siente parte de tu negocio.'],
                        ['datos',  'md:col-span-7', 'Datos',           'Decisiones con datos reales', 'Vas a saber qué preguntan tus clientes, qué platos generan más interés y qué promo funciona, para ajustar la carta con criterio y no a ojo.'],
                    ] as [$dibujo, $cols, $eyebrow, $titulo, $texto])
                        <div class="{{ $cols }} surface rounded-3xl p-7 flex items-start gap-5">
                            <span class="shrink-0 inline-flex items-center justify-center rounded-2xl"
                                  style="width:56px;height:56px;background:rgba(224,122,95,.12);" aria-hidden="true">
                                <svg viewBox="0 0 48 48" style="width:34px;height:34px;fill:none;stroke:var(--accent-ink);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;">
                                    {!! $ilustraciones[$dibujo] !!}
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--sage);">{{ $eyebrow }}</p>
                                <h3 class="mt-2 font-display text-2xl">{{ $titulo }}</h3>
                                <p class="mt-2.5 text-sm text-muted">{{ $texto }}</p>
                            </div>
                        </div>
                    @endforeach

                    {{-- El multi-idioma va en una tarjeta ancha propia: para un local con
                         turistas es de los argumentos más fuertes, y compartiendo fila con
                         otra quedaba como una nota al pie.

                         Los idiomas van como línea tipográfica y no como píldoras: las
                         cápsulas parecen botones y acá no hay nada que clickear. --}}
                    <div class="md:col-span-12 rounded-3xl p-7 sm:p-9 band-dark">
                        <div class="flex flex-col md:flex-row md:items-center gap-7 md:gap-9">
                            <span class="shrink-0 inline-flex items-center justify-center rounded-2xl"
                                  style="width:64px;height:64px;background:rgba(242,237,227,.10);" aria-hidden="true">
                                <svg viewBox="0 0 48 48" style="width:38px;height:38px;fill:none;stroke:var(--wood);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;">
                                    {!! $ilustraciones['idiomas'] !!}
                                </svg>
                            </span>
                            <div class="flex-1">
                                <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--dark-muted);">Turistas</p>
                                <h3 class="mt-2 font-display text-2xl">Tu carta habla el idioma de quien la lee</h3>
                                <p class="mt-2.5 text-sm max-w-2xl" style="color:var(--dark-muted);">
                                    El menú y el asistente detectan el idioma del celular del cliente y le responden
                                    en el suyo, al instante. Vos cargás la carta una sola vez: no hay una versión
                                    en inglés que mantener aparte ni nada que traducir a mano.
                                </p>
                                <p class="mt-4 font-display text-lg sm:text-xl" style="color:var(--wood);">
                                    {{ implode(' · ', \App\Http\Middleware\SetLocale::IDIOMAS) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Banda verde de ancho completo: es el respiro oscuro del medio de la página
                 y hace que el crema de arriba y abajo se sienta más luminoso. --}}
            <section id="como-funciona" class="band-dark relative overflow-hidden mt-4">
                <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                    <h2 class="font-display text-3xl sm:text-4xl">Cómo funciona</h2>
                    <p class="mt-3 text-muted max-w-2xl">
                        Del papel al QR en una tarde. Nosotros hacemos el trabajo pesado.
                    </p>

                    <div class="relative mt-12">
                        {{-- Línea orgánica que hilvana los cuatro pasos. Solo en desktop:
                             apilados en mobile no conecta nada. --}}
                        <svg class="hidden lg:block absolute inset-x-0 pointer-events-none" style="top:26px;height:40px;width:100%;" viewBox="0 0 1200 40" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M150 20 C 340 -6, 410 44, 600 18 S 860 -4, 1050 22"
                                  fill="none" stroke="var(--dark-muted)" stroke-width="1.5"
                                  stroke-linecap="round" stroke-dasharray="5 7" opacity=".5"/>
                        </svg>

                        <div class="relative grid sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
                            @foreach ([
                                ['Cargás tu local', 'Logo, colores y datos básicos. Listo en un par de minutos.'],
                                ['Subís el menú', 'PDF o foto de tu carta. La IA extrae platos, precios y categorías solita.'],
                                ['Ponés el QR en la mesa', 'El cliente escanea y ya está en tu menú. Sin apps ni registro.'],
                                ['Medís resultados', 'Qué preguntan, qué piden y qué promo funciona, con sugerencias para vender más.'],
                            ] as $i => [$titulo, $texto])
                                <div>
                                    <span class="font-display inline-flex items-center justify-center rounded-full text-lg"
                                          style="width:52px;height:52px;background:var(--wood);color:var(--dark-ground);font-weight:700;">
                                        {{ $i + 1 }}
                                    </span>
                                    <p class="mt-5 font-display text-xl">{{ $titulo }}</p>
                                    <p class="mt-2 text-sm text-muted">{{ $texto }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-14 flex flex-col sm:flex-row gap-3">
                        <button type="button" data-open-contact class="btn btn-primary">
                            Quiero sumarme
                        </button>
                        <button type="button" data-open-contact class="btn btn-ghost">
                            Ver ayuda
                        </button>
                    </div>
                </div>
            </section>

            <!-- ── Casos de uso por rubro (long-tail SEO) ─────────── -->
            <section id="casos-de-uso" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                <h2 class="font-display text-3xl sm:text-4xl">Un menú digital para cada rubro</h2>
                <p class="mt-3 text-muted max-w-2xl">
                    La carta con QR se adapta a cómo trabaja tu local. Estos son los usos más comunes.
                </p>

                @php
                    // Reusa las fotos de public/images/demo/. Si falta alguna, la tarjeta cae a un
                    // fondo cálido con la inicial: nunca queda una imagen rota.
                    $fotoRubro = function (?string $nombre) {
                        if (! $nombre) {
                            return null;
                        }
                        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                            $rel = "images/demo/{$nombre}.{$ext}";
                            if (file_exists(public_path($rel))) {
                                return asset($rel);
                            }
                        }

                        return null;
                    };
                @endphp

                <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ([
                        ['Restaurantes', 'milanesa-napolitana', 'Carta completa por categorías, con fotos de los platos y recomendaciones del asistente para subir el ticket promedio.'],
                        ['Bares y cervecerías', 'malbec-copa', 'Tu carta de tragos y cervezas siempre actualizada. El asistente sugiere el maridaje con la picada o la tabla.'],
                        ['Cafeterías y panaderías', 'ensalada-verde', 'Menú de desayunos y meriendas con opciones sin TACC, veganas o sin lactosa bien señalizadas.'],
                        ['Pizzerías y rotiserías', 'fugazza', 'Variedades, tamaños y promociones del día visibles al instante, sin reimprimir la carta.'],
                        ['Food trucks', null, 'Un QR que funciona en cualquier lado. Cambiás el menú del día desde el celular en segundos.'],
                        ['Heladerías y postres', 'helado-artesanal', 'Sabores y combinaciones del día, con las opciones sin azúcar destacadas para quien las busca.'],
                    ] as [$titulo, $foto, $texto])
                        @php $src = $fotoRubro($foto); @endphp
                        <article class="surface rounded-3xl overflow-hidden group transition-shadow hover:shadow-[var(--shadow-md)]">
                            <div class="overflow-hidden" style="height:170px;background:var(--ground-2);">
                                @if($src)
                                    <img src="{{ $src }}" alt="{{ $titulo }}" width="600" height="400"
                                         loading="lazy" decoding="async"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"
                                         style="background:linear-gradient(135deg, var(--wood), var(--sage-soft));">
                                        <span class="font-display text-4xl" style="color:var(--dark-ground);opacity:.55;">{{ $titulo }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-6">
                                <h3 class="font-display text-xl">{{ $titulo }}</h3>
                                <p class="mt-2 text-sm text-muted">{{ $texto }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <!-- ── El panel del comercio ──────────────────────────
                 La landing mostraba solo la vista del comensal: el dueño del local, que es
                 quien compra, recorría la página sin ver nunca lo que va a usar él. -->
            <section id="panel" class="relative" style="background:var(--ground-2); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
                <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                    <div class="grid lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                        <div class="lg:col-span-5">
                            <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--sage);">Tu panel</p>
                            <h2 class="mt-3 font-display text-3xl sm:text-4xl">Del otro lado del QR, tu negocio en números</h2>
                            <p class="mt-4 text-muted">
                                Mientras tus clientes conversan con el asistente, vos ves qué preguntan,
                                qué platos generan más interés y qué promo está funcionando. Sin planillas
                                y sin adivinar.
                            </p>

                            <ul class="mt-7 flex flex-col gap-3.5">
                                @foreach ([
                                    'Los platos más consultados de la semana',
                                    'Qué buscan y no encontraron en tu carta',
                                    'Pre-pedidos armados desde la mesa',
                                    'Exportá todo a Excel cuando lo necesites',
                                ] as $item)
                                    <li class="flex items-start gap-3 text-sm">
                                        <svg viewBox="0 0 20 20" class="shrink-0 mt-0.5" style="width:19px;height:19px;fill:none;stroke:var(--accent-ink);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;" aria-hidden="true">
                                            <circle cx="10" cy="10" r="8"/><path d="M6.5 10.2l2.4 2.4 4.6-5"/>
                                        </svg>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>

                            <a href="#planes" class="btn btn-ghost mt-8">Ver qué incluye cada plan</a>
                        </div>

                        <div class="lg:col-span-7">
                            {{-- Maqueta del panel con datos de ejemplo. Cuando haya una captura
                                 real de analytics, reemplaza este bloque completo. --}}
                            <div class="rounded-[1.75rem] p-5 sm:p-7" style="background:var(--dark-ground); color:var(--dark-ink); box-shadow:var(--shadow-lg);">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-display text-lg">La Cantina</p>
                                        <p class="text-xs" style="color:var(--dark-muted);">Resumen del mes</p>
                                    </div>
                                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full"
                                          style="background:rgba(242,237,227,.1); color:var(--dark-muted); border:1px solid var(--dark-line);">
                                        Datos de ejemplo
                                    </span>
                                </div>

                                <div class="mt-6 grid grid-cols-3 gap-3">
                                    @foreach ([['1.284', 'consultas al asistente'], ['312', 'pre-pedidos'], ['47', 'platos en la carta']] as [$n, $l])
                                        <div class="rounded-2xl p-4" style="background:rgba(242,237,227,.07); border:1px solid var(--dark-line);">
                                            <p class="font-display text-2xl sm:text-3xl tabular-nums" style="color:var(--wood);">{{ $n }}</p>
                                            <p class="mt-1 text-[11px] leading-tight" style="color:var(--dark-muted);">{{ $l }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-6">
                                    <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--dark-muted);">Platos más consultados</p>
                                    <div class="mt-4 flex flex-col gap-3">
                                        @foreach ([
                                            ['Milanesa napolitana', 100, '412'],
                                            ['Fugazza', 74, '305'],
                                            ['Ensalada verde', 52, '214'],
                                            ['Helado artesanal', 35, '144'],
                                            ['Malbec de la casa', 24, '99'],
                                        ] as $i => [$plato, $pct, $veces])
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs w-40 shrink-0 truncate" style="color:var(--dark-ink);">{{ $plato }}</span>
                                                <span class="flex-1 h-2.5 rounded-full overflow-hidden" style="background:rgba(242,237,227,.09);">
                                                    <span class="block h-full rounded-full"
                                                          style="width:{{ $pct }}%; background:{{ $i === 0 ? 'var(--accent-soft)' : 'var(--sage)' }};"></span>
                                                </span>
                                                <span class="text-xs tabular-nums w-9 text-right shrink-0" style="color:var(--dark-muted);">{{ $veces }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-6 rounded-2xl p-4" style="background:rgba(224,122,95,.14); border:1px solid rgba(224,122,95,.3);">
                                    <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--wood);">Lo que preguntan y no está en la carta</p>
                                    <p class="mt-2 text-sm" style="color:var(--dark-ink);">
                                        <strong>38 clientes</strong> preguntaron por opciones veganas este mes.
                                        Puede ser una oportunidad para sumar un plato.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Planes ─────────────────────────────────────────── -->
            <section id="planes" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                <div class="text-center mb-12">
                    <h2 class="font-display text-3xl sm:text-4xl">Planes y precios</h2>
                    <p class="mt-3 text-muted max-w-xl mx-auto">Elegí el que mejor se adapta a tu negocio. Podés cambiar en cualquier momento.</p>
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
                        {{-- El plan Pro se destaca por contraste de material (fondo verde profundo),
                             no por un borde de color. --}}
                        <div class="relative rounded-3xl p-7 flex flex-col gap-5 {{ $esPro ? 'band-dark' : 'surface' }}"
                             @if($esPro) style="box-shadow:var(--shadow-lg);" @endif>

                            @if($esPro)
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap"
                                      style="background:var(--accent); color:var(--on-accent);">
                                    Más popular
                                </span>
                            @endif

                            <div>
                                <p class="text-xs font-bold tracking-widest uppercase mb-2"
                                   style="color:{{ $esPro ? 'var(--dark-muted)' : 'var(--sage)' }};">{{ $plan->nombre }}</p>
                                <p class="font-display text-3xl tabular-nums" style="color:{{ $esPro ? 'var(--wood)' : 'var(--ink)' }};">{{ $precio }}</p>
                                <p class="mt-2 text-sm text-muted">{{ $plan->descripcion }}</p>
                            </div>

                            <ul class="flex flex-col gap-2.5 flex-1">
                                <li class="flex items-start gap-2.5 text-sm">
                                    <span class="shrink-0" style="color:{{ $esPro ? 'var(--wood)' : 'var(--accent-ink)' }};">✓</span>
                                    {{ $msgs }}
                                </li>
                                @foreach($plan->features ?? [] as $feature)
                                    <li class="flex items-start gap-2.5 text-sm">
                                        <span class="shrink-0" style="color:{{ $esPro ? 'var(--wood)' : 'var(--accent-ink)' }};">✓</span>
                                        {{ $features[$feature] ?? $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <button type="button" data-open-contact class="btn btn-sm mt-2 {{ $esPro ? 'btn-primary' : 'btn-ghost' }}">
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

                <div class="mt-14">
                    <h3 class="font-display text-2xl">Comparación rápida</h3>
                    <div class="mt-5 overflow-x-auto rounded-3xl surface-flat">
                        <table class="w-full text-sm min-w-[640px]">
                            <thead>
                                <tr class="text-left" style="color:var(--sage);">
                                    <th class="font-bold text-xs uppercase tracking-wider px-5 py-4">Plan</th>
                                    <th class="font-bold text-xs uppercase tracking-wider px-5 py-4">Precio</th>
                                    <th class="font-bold text-xs uppercase tracking-wider px-5 py-4">Consultas al asistente</th>
                                    <th class="font-bold text-xs uppercase tracking-wider px-5 py-4">Calidad de IA</th>
                                    @foreach($columnas as $col)
                                        <th class="font-bold text-xs uppercase tracking-wider px-5 py-4 whitespace-nowrap">{{ $etiquetas[$col] ?? ucfirst(str_replace('_', ' ', $col)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($planes as $plan)
                                    <tr style="border-top:1px solid var(--line-soft); {{ $plan->key === 'pro' ? 'background:rgba(224,122,95,.07);' : '' }}">
                                        <td class="px-5 py-4 font-bold whitespace-nowrap">{{ $plan->nombre }}</td>
                                        <td class="px-5 py-4 text-muted whitespace-nowrap tabular-nums">
                                            {{ $plan->precio ? '$'.number_format($plan->precio, 0, ',', '.') : 'A consultar' }}
                                        </td>
                                        <td class="px-5 py-4 text-muted whitespace-nowrap tabular-nums">
                                            {{ $plan->mensajes_ia_mes ? number_format($plan->mensajes_ia_mes, 0, ',', '.').' / mes' : 'Ilimitadas' }}
                                        </td>
                                        <td class="px-5 py-4 text-muted whitespace-nowrap">
                                            {{ $plan->modelo_ia === 'gpt-4o' ? 'Avanzada' : 'Estándar' }}
                                        </td>
                                        @foreach($columnas as $col)
                                            <td class="px-5 py-4">
                                                @if(in_array($col, $plan->features ?? [], true))
                                                    <span class="font-bold" style="color:var(--accent-ink);" title="Incluido">✓</span>
                                                @else
                                                    <span style="color:var(--sage-soft);" title="No incluido">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-xs text-muted">
                        Podés cambiar de plan en cualquier momento. Un local sin plan asignado queda en el plan inicial.
                    </p>
                </div>
            </section>

            <section id="probar" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    <div class="lg:col-span-5">
                        <h2 class="font-display text-3xl sm:text-4xl">Probalo vos mismo</h2>
                        <p class="mt-3 text-muted">
                            Abrí un menú real y hacele preguntas al asistente, igual que lo haría un cliente
                            sentado en la mesa. No hace falta registrarse.
                        </p>
                    </div>
                    <div class="lg:col-span-7 rounded-[2.25rem] surface p-6 sm:p-8">
                        @if($demoSlug)
                            @php $demoUrl = route('menu.public', $demoSlug); @endphp
                            <div class="flex flex-col sm:flex-row gap-6 items-start">
                                {{-- QR de verdad: escaneable con la cámara, apunta al menú demo.
                                     Usa el mismo servicio que el generador de QR del panel. --}}
                                <div class="shrink-0 mx-auto sm:mx-0 rounded-2xl p-3" style="background:var(--ground); border:1px solid var(--line);">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data={{ urlencode($demoUrl) }}&color=284236&bgcolor=F2EDE3&margin=8&ecc=H&format=png"
                                         alt="Código QR del menú de demostración de ServiME"
                                         width="140" height="140" loading="lazy" decoding="async"
                                         style="width:140px;height:140px;display:block;">
                                </div>
                                <div>
                                    <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--sage);">Menú de demostración</p>
                                    <p class="mt-2 font-display text-xl">Escaneá con el celular</p>
                                    <p class="mt-2 text-sm text-muted">
                                        Es el mismo QR que iría en la mesa de tu local. O entrá directo desde acá
                                        y probá el chat con una carta real.
                                    </p>
                                    <a href="{{ $demoUrl }}" class="btn btn-primary btn-sm mt-4">
                                        Abrir el menú de demostración
                                    </a>
                                </div>
                            </div>
                        @else
                            {{-- Sin comercio demo configurado la tarjeta quedaba vacía. Pasa
                                 cuando el slug de config/servime.php no existe o su menú no
                                 está procesado. --}}
                            <p class="text-xs font-bold tracking-widest uppercase" style="color:var(--sage);">Demo</p>
                            <p class="mt-2 font-display text-xl">Te la mostramos en vivo</p>
                            <p class="mt-2 text-sm text-muted max-w-md">
                                Escribinos y te abrimos un menú de ejemplo con la carta de tu rubro,
                                para que veas cómo le responde el asistente a tus clientes.
                            </p>
                            <button type="button" data-open-contact class="btn btn-primary btn-sm mt-4">
                                Pedir una demo
                            </button>
                        @endif
                    </div>
                </div>
            </section>

            <!-- ── Preguntas frecuentes (alimenta el schema FAQPage del <head>) ── -->
            <section id="preguntas-frecuentes" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16 sm:py-20">
                <h2 class="font-display text-3xl sm:text-4xl">Preguntas frecuentes</h2>
                <p class="mt-3 text-muted max-w-2xl">
                    Lo que más nos consultan sobre la carta digital con QR y el asistente con IA.
                </p>

                {{-- Lista con hairlines en vez de tarjetas: más liviano y más editorial.
                     El <details> se mantiene tal cual porque el schema FAQPage depende de él. --}}
                <div class="mt-10 grid lg:grid-cols-2 lg:gap-x-12">
                    @foreach ($faqs as $faq)
                        <details class="group py-5" style="border-top:1px solid var(--line);">
                            <summary class="flex items-start justify-between gap-4 cursor-pointer list-none">
                                <h3 class="font-display text-lg sm:text-xl">{{ $faq['q'] }}</h3>
                                <span class="mt-1 shrink-0 text-xl leading-none transition-transform group-open:rotate-45"
                                      style="color:var(--accent-ink);" aria-hidden="true">+</span>
                            </summary>
                            <p class="mt-3 text-sm text-muted max-w-prose">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

        </main>

        <footer class="band-dark">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div>
                        <p class="font-display text-xl">{{ config('app.name', 'ServiME') }}</p>
                        <p class="mt-1 text-sm" style="color:var(--dark-muted);">Menú digital con IA para restaurantes y bares.</p>
                    </div>
                    <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm">
                        <a class="transition hover:opacity-100 opacity-75" href="#beneficios">Beneficios</a>
                        <a class="transition hover:opacity-100 opacity-75" href="#como-funciona">Cómo funciona</a>
                        <a class="transition hover:opacity-100 opacity-75" href="#panel">Tu panel</a>
                        <a class="transition hover:opacity-100 opacity-75" href="#casos-de-uso">Para tu rubro</a>
                        <a class="transition hover:opacity-100 opacity-75" href="#preguntas-frecuentes">Preguntas frecuentes</a>
                        <button type="button" class="transition hover:opacity-100 opacity-75" data-open-contact>Ayuda</button>
                        <a class="transition hover:opacity-100 opacity-75" href="{{ route('login') }}">Entrar</a>
                    </div>
                </div>

                {{-- ── Contador en vivo ──
                     Oculto hasta superar SiteVisit::MIN_VISITAS_CONTADOR: la prueba social con
                     números chicos resta credibilidad. El tracking sigue corriendo igual, así que
                     cuando haya volumen el contador aparece solo, sin tocar nada. --}}
                @if ($stats['mostrar'] ?? false)
                <div class="mt-10 pt-8" style="border-top:1px solid var(--dark-line);">
                    <div id="live-counter"
                         class="mx-auto flex w-full max-w-3xl flex-wrap items-center justify-center gap-x-6 gap-y-4
                                rounded-2xl px-6 py-4"
                         style="background:var(--dark-surface); border:1px solid var(--dark-line);">

                        {{-- Navegando ahora --}}
                        <span class="inline-flex items-center gap-2.5">
                            <span class="relative flex h-2 w-2" aria-hidden="true">
                                <span class="absolute inline-flex h-full w-full rounded-full opacity-60 animate-ping" style="background:var(--accent-soft);"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full" style="background:var(--accent-soft);"></span>
                            </span>
                            <strong data-counter data-value="{{ $stats['activos'] ?? 0 }}"
                                    class="font-display text-lg tabular-nums">0</strong>
                            <span class="text-sm" style="color:var(--dark-muted);">navegando ahora</span>
                        </span>

                        <span class="hidden sm:block h-5 w-px" style="background:var(--dark-line);" aria-hidden="true"></span>

                        {{-- Visitas totales --}}
                        <span class="inline-flex items-center gap-2.5">
                            <strong data-counter data-value="{{ $stats['visitas'] ?? 0 }}"
                                    class="font-display text-lg tabular-nums">0</strong>
                            <span class="text-sm" style="color:var(--dark-muted);">visitas</span>
                        </span>

                        <span class="hidden sm:block h-5 w-px" style="background:var(--dark-line);" aria-hidden="true"></span>

                        {{-- Consultas resueltas por IA --}}
                        <span class="inline-flex items-center gap-2.5">
                            <svg class="h-4 w-4" style="color:var(--wood);" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 1.5l1.9 4.6 4.6 1.9-4.6 1.9L10 14.5 8.1 9.9 3.5 8l4.6-1.9L10 1.5zM4.5 13l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z"/>
                            </svg>
                            <strong data-counter data-value="{{ $stats['consultas_ia'] ?? 0 }}"
                                    class="font-display text-lg tabular-nums">0</strong>
                            <span class="text-sm" style="color:var(--dark-muted);">consultas resueltas por IA</span>
                        </span>
                    </div>

                </div>
                @endif
            </div>
        </footer>

        {{-- ── Demo toast ── --}}
        <div id="demo-toast" class="toast-hidden fixed top-20 right-4 sm:right-6 z-[60] w-[min(18rem,calc(100vw-2rem))] rounded-2xl p-4"
             style="background:var(--surface); border:1px solid var(--line); box-shadow:var(--shadow-lg);">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold">¿Querés ver una demo?</p>
                    <p class="mt-0.5 text-xs text-muted">Entrá a un menú real y probá el asistente.</p>
                </div>
                <button id="demo-toast-close" type="button" aria-label="Cerrar"
                        class="mt-0.5 transition" style="color:var(--sage);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <div class="mt-3 flex gap-2">
                <button id="demo-toast-yes" type="button" class="btn btn-primary flex-1 text-xs px-3 py-2 rounded-xl">
                    Ver demo
                </button>
                <button id="demo-toast-no" type="button" class="btn btn-ghost flex-1 text-xs px-3 py-2 rounded-xl">
                    No, gracias
                </button>
            </div>
        </div>

        {{-- ── Contact FAB ── --}}
        <div id="contact-fab" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
            {{-- Chat widget --}}
            <div id="contact-panel"
                 class="panel-hidden flex flex-col w-full sm:w-[380px] rounded-3xl overflow-hidden"
                 style="height: min(520px, 75vh); background:var(--surface); border:1px solid var(--line); box-shadow:var(--shadow-lg);"
                 role="dialog" aria-label="Chat ServiME" aria-modal="false">

                {{-- Header --}}
                <div class="shrink-0 flex items-center gap-3 px-4 py-3 band-dark">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl shrink-0"
                          style="background:var(--accent);">
                        <span class="font-display text-base" style="color:var(--on-accent);">S</span>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold leading-none">ServiME</p>
                        <p class="mt-1 text-xs" style="color:var(--dark-muted);">Equipo de ventas</p>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs shrink-0" style="color:var(--sage-soft);">
                        <span class="h-1.5 w-1.5 rounded-full" style="background:var(--sage-soft);"></span>
                        En línea
                    </span>
                    <button id="chat-close-btn" type="button" aria-label="Cerrar chat"
                            class="ml-1 shrink-0 transition opacity-70 hover:opacity-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                {{-- Messages area (lazy populated by JS) --}}
                <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 overscroll-contain"
                     style="scroll-behavior: smooth;"></div>

                {{-- Sticky footer: direct contact buttons --}}
                <div class="shrink-0 px-4 pt-2 pb-2 flex gap-2" style="border-top:1px solid var(--line);">
                    <a id="chat-footer-email"
                       href="https://mail.google.com/mail/?view=cm&fs=1&to={{ $supportEmail }}&su=Consulta%20sobre%20ServiME"
                       target="_blank" rel="noopener"
                       class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition hover:brightness-95"
                       style="background:var(--ground); border:1px solid var(--line); color:var(--ink);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" style="color:var(--accent-ink);" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Enviar email
                    </a>
                    @if($supportWhatsapp)
                    <a id="chat-footer-wa"
                       href="https://wa.me/{{ $supportWhatsapp }}?text=Hola%2C%20quiero%20info%20sobre%20ServiME"
                       target="_blank" rel="noopener"
                       class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition hover:brightness-95"
                       style="background:rgba(107,140,122,.16); border:1px solid rgba(107,140,122,.4); color:var(--ink);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" style="color:var(--sage);" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>
                    @endif
                </div>

                {{-- Input bar --}}
                <div class="shrink-0 px-4 pb-4 pt-2 flex gap-2 items-end" style="border-top:1px solid var(--line-soft);">
                    <textarea id="chat-input" rows="1" placeholder="Escribí tu consulta..."
                              class="flex-1 resize-none rounded-2xl px-4 py-2.5 text-sm outline-none transition leading-snug overflow-hidden"
                              style="min-height: 2.5rem; max-height: 6rem; background:var(--ground); border:1px solid var(--line); color:var(--ink);"></textarea>
                    <button id="chat-send-btn" type="button" aria-label="Enviar"
                            class="shrink-0 h-10 w-10 flex items-center justify-center rounded-2xl transition"
                            style="background:var(--accent); color:var(--on-accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button id="contact-fab-btn" type="button" aria-label="Abrir chat de ayuda"
                    class="fab-live-anim flex items-center gap-2.5 h-14 pl-3 pr-5 rounded-full transition-colors"
                    style="background:var(--accent); color:var(--on-accent);"
                    onmouseover="this.style.background='var(--accent2)'" onmouseout="this.style.background='var(--accent)'">
                {{-- Ícono + punto verde: "hay alguien del otro lado" --}}
                <span class="relative flex h-9 w-9 items-center justify-center rounded-full shrink-0" style="background:rgba(255,255,255,.18);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z"/>
                    </svg>
                    <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full rounded-full opacity-70 animate-ping" style="background:var(--sage-soft);"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full" style="background:var(--sage-soft); box-shadow:0 0 0 2px var(--accent);"></span>
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

            // Menú demo: única fuente para todos los accesos a la demo de la página.
            // Vacío si no hay comercio demo configurado.
            const DEMO_MENU_URL = @json($demoSlug ? route('menu.public', $demoSlug) : null);
        </script>

        <script>
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
            // "Ver demo" abre el menú demo directamente, igual que el botón y el QR de la
            // sección "Probalo vos mismo". Sin comercio demo configurado no hay adónde ir:
            // ahí baja a la sección, que igual explica de qué se trata.
            document.getElementById('demo-toast-yes')?.addEventListener('click', () => {
                dismissDemoToast();

                if (DEMO_MENU_URL) {
                    window.location.href = DEMO_MENU_URL;
                    return;
                }

                document.getElementById('probar')?.scrollIntoView({ behavior: 'smooth' });
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
                    reply: '¡Perfecto! ServiME está hecho para vos. Tus clientes escanean un QR en la mesa, ven tu menú en su celular y pueden preguntar al bot — que conoce tu menú completo.\n\nTambién pueden armar un pre-pedido para el camarero. Sin apps, sin fricción.\n\n¿Empezamos?'
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

            function escapeHtml(str) {
                return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                          .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            // Email y WhatsApp viven SOLO en la barra fija de abajo. Antes se repetían
            // dentro de cada respuesta del bot, así que a la segunda pregunta el panel
            // tenía cuatro botones haciendo lo mismo.
            //
            // Lo que sí se conserva de aquella versión es el contexto: la barra arranca
            // con un texto genérico y, en cuanto el visitante escribe, sus enlaces pasan a
            // llevar la consulta redactada, para que no tenga que repetirla.
            const footerEmail = document.getElementById('chat-footer-email');
            const footerWa    = document.getElementById('chat-footer-wa');

            function actualizarContactoFooter(userMsg) {
                const subj   = encodeURIComponent('Consulta sobre ServiME');
                const body   = encodeURIComponent(userMsg ? 'Hola, tengo una consulta: ' + userMsg : 'Hola, quiero más información sobre ServiME');
                const waText = encodeURIComponent(userMsg ? 'Hola, tengo una consulta sobre ServiME: ' + userMsg : 'Hola, quiero info sobre ServiME');

                if (footerEmail) {
                    footerEmail.href = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(EMAIL)}&su=${subj}&body=${body}`;
                }
                if (footerWa && WHATSAPP) {
                    footerWa.href = `https://wa.me/${WHATSAPP}?text=${waText}`;
                }
            }

            function scrollChatToBottom() {
                requestAnimationFrame(() => { chatMessages.scrollTop = chatMessages.scrollHeight; });
            }

            function renderBotMessage(text) {
                const wrap = document.createElement('div');
                wrap.className = 'chat-msg-animate flex items-start gap-2.5';
                const formatted = text
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
                const avatar = `<span class="inline-flex h-7 w-7 items-center justify-center rounded-xl shrink-0 mt-0.5" style="background:var(--accent);"><span class="font-display text-xs" style="color:var(--on-accent);">S</span></span>`;
                wrap.innerHTML = `${avatar}<div class="rounded-2xl px-4 py-3 text-sm leading-relaxed max-w-full" style="background:var(--ground); border:1px solid var(--line); color:var(--ink);">${formatted}</div>`;
                chatMessages.appendChild(wrap);
                scrollChatToBottom();
            }

            function renderUserMessage(text) {
                const wrap = document.createElement('div');
                wrap.className = 'chat-msg-animate flex justify-end';
                wrap.innerHTML = `<div class="rounded-2xl px-4 py-2.5 text-sm font-medium leading-snug max-w-[78%]" style="background:var(--accent); color:var(--on-accent);">${escapeHtml(text)}</div>`;
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
                div.innerHTML = `<span class="inline-flex h-7 w-7 items-center justify-center rounded-xl shrink-0 mt-0.5" style="background:var(--accent);"><span class="font-display text-xs" style="color:var(--on-accent);">S</span></span><div class="rounded-2xl px-4 py-3 flex items-center gap-1.5" style="background:var(--ground); border:1px solid var(--line);"><span class="chat-typing-dot"></span><span class="chat-typing-dot"></span><span class="chat-typing-dot"></span></div>`;
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
                    renderBotMessage('¡Hola! 👋 Soy el asistente de ServiME. ¿En qué te puedo ayudar?');
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
                    actualizarContactoFooter(label);
                    renderUserMessage(label);
                    showTyping();
                    setTimeout(() => {
                        hideTyping();
                        renderBotMessage(reply);
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

                // Los botones de la barra pasan a llevar esta consulta
                actualizarContactoFooter(msg);

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
                        renderBotMessage(botReply);
                        renderQuickReplies();
                    })
                    .catch(() => {
                        hideTyping();
                        renderBotMessage('Hubo un problema de conexión. Escribinos por email o WhatsApp con los botones de abajo y te respondemos.');
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

                    return `<div style="border-radius:14px;overflow:hidden;border:1px solid var(--line);background:var(--surface);box-shadow:var(--shadow-sm);">
                        ${img}
                        <div style="padding:9px 11px;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                <span style="font-weight:700;color:var(--ink);font-size:11.5px;line-height:1.3;">${p.nombre}</span>
                                <span style="color:var(--accent-ink);font-weight:700;white-space:nowrap;font-size:11.5px;">${p.precio}</span>
                            </div>
                            ${p.desc ? `<p style="color:var(--muted);font-size:10.5px;margin:3px 0 0;line-height:1.45;">${p.desc}</p>` : ''}
                            <span style="display:inline-block;margin-top:7px;padding:3px 10px;font-size:10.5px;border-radius:9999px;color:#fff;background:var(--accent);">Lo quiero</span>
                        </div>
                    </div>`;
                }

                // Réplica del chat real: burbuja del bot en papel con "cola" arriba a la
                // izquierda, y la del cliente en terracota con cola a la derecha.
                const BUBBLE_BOT   = 'background:var(--surface);border-radius:6px 20px 20px 20px;padding:11px 14px;border:1px solid var(--line);box-shadow:var(--shadow-sm);color:var(--ink);font-size:12px;line-height:1.55;';
                const BUBBLE_USER  = 'background:var(--accent);border-radius:20px 6px 20px 20px;padding:10px 13px;max-width:82%;color:#fff;font-size:12px;line-height:1.5;box-shadow:var(--shadow-sm);';
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
                        ? `<span style="color:var(--muted);font-size:14px;letter-spacing:2px;">…</span>`
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
                                + `<span style="align-self:flex-start;display:inline-flex;align-items:center;padding:4px 10px;border-radius:9999px;border:1px solid rgba(192,86,58,.4);color:var(--accent-ink);font-size:10.5px;background:var(--surface);">Ver más opciones ↓</span>`;
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


