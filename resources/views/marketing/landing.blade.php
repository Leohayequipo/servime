<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>ServiME — Menú digital con QR e IA para restaurantes y bares</title>
        <meta name="description" content="Creá tu carta digital con QR en minutos. Tus clientes escanean, ven el menú y consultan al chatbot de IA entrenado con tu menú. Sin apps. Desde ${{ number_format($planes->min('precio'), 0, ',', '.') }}/mes." />
        <link rel="canonical" href="{{ url('/') }}" />

        <meta property="og:title" content="ServiME — Menú digital con QR e IA para restaurantes" />
        <meta property="og:description" content="Carta digital QR + chatbot de IA entrenado con tu menú + pre-pedido para el mozo. Sin apps, listo en minutos." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url('/') }}" />
        <meta property="og:image" content="{{ asset('images/servime_ia.ico') }}" />
        <meta property="og:locale" content="es_AR" />

        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="ServiME — Menú digital con QR e IA" />
        <meta name="twitter:description" content="Carta digital QR + chatbot IA para restaurantes. Sin apps, listo en minutos." />

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "ServiME",
            "url": "https://servime.net",
            "description": "Plataforma de menú digital con QR e inteligencia artificial para restaurantes y bares. El chatbot responde preguntas del menú y facilita el pre-pedido.",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "inLanguage": "es",
            "offers": {
                "@type": "Offer",
                "price": "{{ (int) $planes->min('precio') }}",
                "priceCurrency": "ARS",
                "description": "Plan mensual ServiME"
            },
            "featureList": [
                "Menú digital con código QR",
                "Chatbot de IA entrenado con el menú",
                "Pre-pedido para el mozo",
                "Panel de gestión de menús",
                "Métricas de uso"
            ]
        }
        </script>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">

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
            @keyframes demo-bounce {
                0%, 80%, 100% { transform: scale(0); }
                40%           { transform: scale(1); }
            }
            .demo-tdot {
                width:7px; height:7px; background:#3b82f6; border-radius:50%; display:inline-block;
                animation: demo-bounce 1.4s ease-in-out infinite both;
            }
            .demo-tdot:nth-child(2) { animation-delay:.1s; }
            .demo-tdot:nth-child(3) { animation-delay:.2s; }
            @media (prefers-reduced-motion: reduce) { .demo-tdot { animation:none; opacity:.6; } }

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
                        <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" style="height:140px;width:auto;" class="opacity-90 group-hover:opacity-100 transition">
                    </a>

                    <nav class="flex items-center gap-2 sm:gap-3">
                        <a href="#como-funciona" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Cómo funciona</a>
                        <a href="#beneficios" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Beneficios</a>
                        <a href="#" data-open-contact class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Ayuda</a>
                        <a href="{{ route('login') }}" class="inline-flex text-sm text-white/80 hover:text-white transition px-3 py-2.5 rounded-lg">Entrar</a>
                        <a href="#" data-open-contact class="inline-flex text-sm font-semibold text-black bg-[var(--accent)] hover:bg-[color:var(--accent2)] transition px-4 py-2.5 rounded-xl shadow-sm shadow-amber-500/20">
                            Quiero sumarme
                        </a>
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
                                Tu menú en el celular, con IA que responde por vos.
                            </h1>

                            <p class="mt-5 text-base sm:text-lg text-[color:var(--muted)] max-w-xl">
                                Tus clientes escanean un QR, ven el menú y preguntan al instante. El bot responde con contexto del menú y permite armar un pre-pedido para el mozo.
                            </p>

                            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                                <a href="#" data-open-contact class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition shadow-sm shadow-amber-500/20">
                                    Quiero sumarme
                                </a>
                                <a href="#probar" class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                                    Probar con un slug
                                </a>
                            </div>

                        </div>

                        <div class="lg:col-span-6">
                            <div class="relative mx-auto max-w-md">
                                <div class="absolute -inset-6 bg-gradient-to-tr from-amber-500/15 via-orange-500/10 to-transparent blur-2xl"></div>

                                <div class="relative rounded-[2.5rem] bg-black/30 ring-1 ring-white/10 p-5 shadow-2xl shadow-black/60">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2.5 w-2.5 rounded-full bg-red-500/70"></div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-yellow-500/70"></div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-green-500/70"></div>
                                        </div>
                                        <p class="text-xs text-white/50">Vista del cliente</p>
                                    </div>

                                    {{-- Real-chat replica --}}
                                    <div class="mt-4 rounded-[1.5rem] overflow-hidden" style="background:#000;display:flex;flex-direction:column;height:370px;">
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
                                        <div id="demo-chat-msgs" style="flex:1;overflow:hidden;padding:10px 12px;display:flex;flex-direction:column;gap:10px;font-family:Inter,-apple-system,BlinkMacSystemFont,sans-serif;min-height:0;"></div>
                                        {{-- Input bar --}}
                                        <div style="background:#000;border-top:1px solid rgba(255,255,255,0.08);padding:8px 10px;flex-shrink:0;">
                                            <div style="display:flex;border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,0.18);">
                                                <input type="text" placeholder="Escribe tu mensaje..." disabled style="flex:1;background:#1e293b;border:none;padding:9px 12px;color:#fff;font-size:12px;outline:none;font-family:inherit;">
                                                <button type="button" disabled style="background:#3b82f6;border:none;padding:9px 14px;color:#fff;font-size:12px;font-weight:500;font-family:inherit;cursor:default;">Enviar</button>
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
                            Menos ida y vuelta, más claridad. Tus clientes exploran el menú con tranquilidad y llegan al mozo con un resumen.
                        </p>
                    </div>
                </div>

                <div class="mt-10 grid md:grid-cols-12 gap-4">
                    <div class="md:col-span-5 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">QR</p>
                        <h3 class="mt-3 font-display text-2xl">Acceso inmediato</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            Un QR por comercio y listo. Experiencia móvil optimizada, sin apps.
                        </p>
                    </div>

                    <div class="md:col-span-7 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">IA contextual</p>
                        <h3 class="mt-3 font-display text-2xl">Respuestas del menú, no genéricas</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            El bot responde en base al contenido del menú procesado: platos, precios, categorías, tags y promos.
                        </p>
                    </div>

                    <div class="md:col-span-7 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Pre-pedido</p>
                        <h3 class="mt-3 font-display text-2xl">“Lo quiero” y total estimado</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            El cliente arma un resumen para mostrar o copiar/compartir. Ideal para reducir dudas al momento de pedir.
                        </p>
                    </div>

                    <div class="md:col-span-5 rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-white/60">Panel</p>
                        <h3 class="mt-3 font-display text-2xl">Gestión simple</h3>
                        <p class="mt-3 text-sm text-[color:var(--muted)]">
                            Subí menús, activá uno por comercio, personalizá y mirá métricas.
                        </p>
                    </div>
                </div>
            </section>

            <section id="como-funciona" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="rounded-[2.75rem] bg-white/3 ring-1 ring-white/10 p-8 sm:p-10">
                    <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Cómo funciona</h2>
                    <div class="mt-8 grid md:grid-cols-3 gap-6">
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 1</p>
                            <p class="mt-3 font-semibold text-lg">Creás el comercio</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Logo y datos básicos. Listo para cargar menús.</p>
                        </div>
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 2</p>
                            <p class="mt-3 font-semibold text-lg">Subís el menú</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">PDF o imágenes. La IA extrae contenido para el chat.</p>
                        </div>
                        <div class="rounded-3xl bg-black/20 ring-1 ring-white/10 p-6">
                            <p class="text-xs text-white/60 font-semibold tracking-wide uppercase">Paso 3</p>
                            <p class="mt-3 font-semibold text-lg">Generás el QR</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Los clientes escanean y ya están en el menú público.</p>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row gap-3">
                        <a href="#" data-open-contact class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition shadow-sm shadow-amber-500/20">
                            Quiero sumarme
                        </a>
                        <a href="#" data-open-contact class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                            Ver ayuda
                        </a>
                    </div>
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

                            <a href="#" data-open-contact
                               class="mt-2 inline-flex justify-center items-center rounded-2xl font-semibold px-5 py-2.5 text-sm transition
                               {{ $esPro ? 'bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black shadow shadow-amber-500/20' : 'bg-white/5 hover:bg-white/10 text-white ring-1 ring-white/15' }}">
                                Quiero este plan
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="probar" class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="grid lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-5">
                        <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Probalo en tu comercio</h2>
                        <p class="mt-3 text-[color:var(--muted)]">
                            Si ya tenés un comercio creado, pegá el <span class="font-semibold text-white">slug</span> y abrí la vista pública.
                        </p>
                    </div>
                    <div class="lg:col-span-7 rounded-[2.25rem] bg-white/3 ring-1 ring-white/10 p-6 sm:p-8">
                        <label class="block text-sm font-semibold text-white/80" for="slug">
                            Slug del comercio
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
                                class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition whitespace-nowrap"
                            >
                                Abrir menú
                            </button>
                        </div>
                        <p class="mt-3 text-xs text-white/50">
                            Esto abre <code class="text-white/70">/menu/&lt;slug&gt;</code> en la misma pestaña.
                        </p>
                    </div>
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
                        <a class="text-white/70 hover:text-white transition" href="#" data-open-contact>Ayuda</a>
                        <a class="text-white/70 hover:text-white transition" href="{{ route('login') }}">Entrar</a>
                    </div>
                </div>
            </div>
        </footer>

        {{-- ── Demo toast ── --}}
        <div id="demo-toast" class="toast-hidden fixed top-20 right-4 sm:right-6 z-[60] w-[min(18rem,calc(100vw-2rem))] rounded-2xl bg-[#1b1814] ring-1 ring-white/15 shadow-xl shadow-black/50 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">¿Querés ver una demo?</p>
                    <p class="mt-0.5 text-xs text-white/60">Probá el menú con un slug real.</p>
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

            <button id="contact-fab-btn" type="button" aria-label="Abrir chat de ventas"
                    class="fab-live-anim h-14 w-14 rounded-full bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black shadow-lg shadow-amber-500/30 flex items-center justify-center transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z"/>
                </svg>
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
                    reply: 'Podés probar la experiencia de tus clientes ahora mismo: andá a la sección **"Probalo en tu comercio"** acá abajo e ingresá el slug de un comercio existente.\n\nSi querés una demo personalizada con tu propio menú, contactanos directamente.'
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

                const CONVOS = [
                    {
                        user: 'Somos 2 adultos y 2 niños, ¿qué nos recomendas para cenar?',
                        bot:  '¡Para una noche en familia! 🍽️ A los adultos les recomiendo la **Milanesa napolitana** ($3.800) o el **Salmón grillado** ($4.200). Para los chicos, los **Nuggets con papas** ($2.100) son un clásico. ¿Armamos el pedido?',
                    },
                    {
                        user: 'Queremos compartir el plato de los nenes',
                        bot:  '¡Sin problema! Los **Nuggets con papas** vienen en porción generosa, alcanza bien para dos. Si tienen buen apetito podés pedir doble 😄 ¿Lo agrego al resumen?',
                    },
                    {
                        user: 'Un vino tinto y una ensalada verde, ¿está bien?',
                        bot:  '¡Excelente combo! El **Malbec de la casa** ($2.500 la copa) y la **Ensalada verde** con rúcula y tomate cherry ($1.800) van perfecto juntos. ¿Los sumo al pre-pedido?',
                    },
                ];

                const BOT_AVATAR = `<div style="width:26px;height:26px;background:linear-gradient(135deg,#3b82f6,#60a5fa);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,0.5);"><span style="font-size:11px;">🤖</span></div>`;

                const BUBBLE_BOT   = 'background:linear-gradient(to right,#334155,rgba(51,65,85,0.8));border-radius:12px;padding:10px 13px;box-shadow:0 4px 12px rgba(0,0,0,0.4);';
                const BUBBLE_USER  = 'background:linear-gradient(to right,#3b82f6,#60a5fa);border-radius:12px;padding:10px 13px;max-width:82%;color:#fff;font-size:12px;line-height:1.5;box-shadow:0 4px 12px rgba(0,0,0,0.4);';
                const ANIM_LEFT    = reduced ? '' : 'animation:demo-slide-left .4s ease-out;';
                const ANIM_RIGHT   = reduced ? '' : 'animation:demo-slide-right .4s ease-out;';

                function fmt(t) { return t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>'); }
                function schedule(fn, ms) { return setTimeout(fn, reduced ? Math.min(ms, 80) : ms); }

                function showTyping() {
                    const el = document.createElement('div');
                    el.id = 'demo-typing';
                    el.style.cssText = BUBBLE_BOT + ANIM_LEFT;
                    const dots = reduced
                        ? `<span style="color:#60a5fa;font-size:14px;letter-spacing:2px;">…</span>`
                        : `<span class="demo-tdot"></span><span class="demo-tdot"></span><span class="demo-tdot"></span>`;
                    el.innerHTML = `<div style="display:flex;align-items:center;gap:8px;">${BOT_AVATAR}<div style="display:flex;align-items:center;gap:6px;"><div style="display:flex;gap:3px;">${dots}</div><span style="color:#cbd5e1;font-size:11px;font-weight:500;">Escribiendo...</span></div></div>`;
                    area.appendChild(el);
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

                    function showBotMsg() {
                        document.getElementById('demo-typing')?.remove();
                        const el = document.createElement('div');
                        el.style.cssText = BUBBLE_BOT + ANIM_LEFT;
                        el.innerHTML = `<div style="display:flex;align-items:flex-start;gap:8px;">${BOT_AVATAR}<div style="flex:1;color:#fff;font-size:12px;line-height:1.5;">${fmt(bot)}</div></div>`;
                        area.appendChild(el);
                        schedule(fadeOut, 3600);
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

    </body>
</html>


