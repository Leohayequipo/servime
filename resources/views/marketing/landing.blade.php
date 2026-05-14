<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ServiME') }} - Menú digital con IA para restaurantes</title>
        <meta name="description" content="Tu menú en el celular con IA: QR, chatbot del menú, y pre-pedido para el mozo. Panel para cargar menús, activar, y ver métricas." />

        <meta property="og:title" content="{{ config('app.name', 'ServiME') }} - Menú digital con IA" />
        <meta property="og:description" content="QR + chatbot del menú + pre-pedido. Una experiencia móvil moderna para tus clientes." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url('/') }}" />
        <meta property="og:image" content="{{ asset('images/ServiME_IA.ico') }}" />

        <meta name="twitter:card" content="summary" />

        <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">

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
        </style>
    </head>
    <body class="grain antialiased">
        <header class="sticky top-0 z-50 backdrop-blur supports-[backdrop-filter]:bg-black/20 border-b border-white/5">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="h-16 flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/5 ring-1 ring-white/10 group-hover:ring-white/20 transition">
                            <span class="font-display text-lg">S</span>
                        </span>
                        <span class="font-display text-lg tracking-tight">{{ config('app.name', 'ServiME') }}</span>
                    </a>

                    <nav class="flex items-center gap-2 sm:gap-3">
                        <a href="#como-funciona" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Cómo funciona</a>
                        <a href="#beneficios" class="hidden sm:inline-flex text-sm text-white/70 hover:text-white transition px-3 py-2 rounded-lg">Beneficios</a>
                        <a href="{{ route('login') }}" class="inline-flex text-sm text-white/80 hover:text-white transition px-3 py-2 rounded-lg">Entrar</a>
                        <a href="{{ route('register') }}" class="inline-flex text-sm font-semibold text-black bg-[var(--accent)] hover:bg-[color:var(--accent2)] transition px-4 py-2 rounded-xl shadow-sm shadow-amber-500/20">
                            Crear cuenta
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <section class="relative">
                <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pt-14 sm:pt-20 pb-14">
                    <div class="grid lg:grid-cols-12 gap-10 items-center">
                        <div class="lg:col-span-6">
                            <p class="inline-flex items-center gap-2 text-xs font-semibold tracking-wide uppercase text-white/60">
                                <span class="h-1.5 w-1.5 rounded-full bg-[var(--accent)] shadow-[0_0_16px_rgba(245,158,11,.6)]"></span>
                                Menú digital + IA + QR
                            </p>

                            <h1 class="mt-4 font-display text-4xl sm:text-5xl lg:text-6xl tracking-tight">
                                Tu menú en el celular, con IA que responde por vos.
                            </h1>

                            <p class="mt-5 text-base sm:text-lg text-[color:var(--muted)] max-w-xl">
                                Tus clientes escanean un QR, ven el menú y preguntan al instante. El bot responde con contexto del menú y permite armar un pre-pedido para el mozo.
                            </p>

                            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                                <a href="{{ route('register') }}" class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition shadow-sm shadow-amber-500/20">
                                    Empezar ahora
                                </a>
                                <a href="#probar" class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                                    Probar con un slug
                                </a>
                            </div>

                            <div class="mt-8 grid grid-cols-3 gap-4 max-w-lg">
                                <div class="rounded-2xl bg-white/3 ring-1 ring-white/10 p-4">
                                    <p class="text-xs text-white/60">Acceso</p>
                                    <p class="mt-1 font-semibold">QR instantáneo</p>
                                </div>
                                <div class="rounded-2xl bg-white/3 ring-1 ring-white/10 p-4">
                                    <p class="text-xs text-white/60">Experiencia</p>
                                    <p class="mt-1 font-semibold">Móvil primero</p>
                                </div>
                                <div class="rounded-2xl bg-white/3 ring-1 ring-white/10 p-4">
                                    <p class="text-xs text-white/60">Operación</p>
                                    <p class="mt-1 font-semibold">Menos preguntas</p>
                                </div>
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

                                    <div class="mt-4 rounded-[2rem] bg-[#0f0e0d] ring-1 ring-white/10 overflow-hidden">
                                        <div class="p-4 border-b border-white/8">
                                            <p class="text-sm font-semibold">¿Qué puedo pedir hoy?</p>
                                            <p class="mt-1 text-xs text-white/60">Preguntá por opciones, precios y promos.</p>
                                        </div>

                                        <div class="p-4 space-y-3">
                                            <div class="rounded-2xl bg-white/5 ring-1 ring-white/10 p-3">
                                                <p class="text-xs text-white/60">Cliente</p>
                                                <p class="mt-1 text-sm">¿Tenés algo vegetariano y sin picante?</p>
                                            </div>

                                            <div class="rounded-2xl bg-gradient-to-br from-amber-500/15 to-orange-500/10 ring-1 ring-white/10 p-3">
                                                <p class="text-xs text-white/70">ServiME IA</p>
                                                <p class="mt-1 text-sm leading-relaxed">
                                                    Te recomiendo la <span class="font-semibold">Ensalada Mediterránea</span> y la <span class="font-semibold">Tarta de verduras</span>.
                                                    ¿Querés que te muestre precios y agregamos al pre-pedido?
                                                </p>
                                                <div class="mt-3 flex gap-2">
                                                    <button type="button" class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-[var(--accent)] text-black">Lo quiero</button>
                                                    <button type="button" class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-white/6 ring-1 ring-white/12 text-white">Ver más</button>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl bg-white/5 ring-1 ring-white/10 p-3">
                                                <p class="text-xs text-white/60">Carrito</p>
                                                <p class="mt-1 text-sm">1 item • Total estimado: $ 12.500</p>
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
                        <a href="{{ route('register') }}" class="inline-flex justify-center items-center rounded-2xl bg-[var(--accent)] hover:bg-[color:var(--accent2)] text-black font-semibold px-6 py-3 transition shadow-sm shadow-amber-500/20">
                            Crear cuenta
                        </a>
                        <a href="{{ route('help.index') }}" class="inline-flex justify-center items-center rounded-2xl bg-white/5 hover:bg-white/8 text-white font-semibold px-6 py-3 transition ring-1 ring-white/12 hover:ring-white/20">
                            Ver ayuda
                        </a>
                    </div>
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

            <section class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-16">
                <div class="rounded-[2.75rem] bg-black/20 ring-1 ring-white/10 p-8 sm:p-10">
                    <h2 class="font-display text-3xl sm:text-4xl tracking-tight">Preguntas comunes</h2>
                    <div class="mt-8 grid md:grid-cols-2 gap-6">
                        <div class="rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                            <p class="font-semibold">¿Funciona sin app?</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Sí. Es web, pensado para móvil, accesible por QR.</p>
                        </div>
                        <div class="rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                            <p class="font-semibold">¿El bot responde con mi menú?</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Se alimenta del contenido procesado del menú (PDF/imágenes) y puede sugerir platos para el pre-pedido.</p>
                        </div>
                        <div class="rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                            <p class="font-semibold">¿Puedo tener más de un menú?</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">Sí, y podés marcar uno como activo para el comercio.</p>
                        </div>
                        <div class="rounded-3xl bg-white/3 ring-1 ring-white/10 p-6">
                            <p class="font-semibold">¿Dónde pido soporte?</p>
                            <p class="mt-2 text-sm text-[color:var(--muted)]">
                                Escribinos a <a class="underline decoration-white/25 hover:decoration-white/60" href="mailto:{{ env('SUPPORT_EMAIL', 'info.servime@gmail.com') }}">{{ env('SUPPORT_EMAIL', 'info.servime@gmail.com') }}</a>.
                            </p>
                        </div>
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
                        <a class="text-white/70 hover:text-white transition" href="{{ route('help.index') }}">Ayuda</a>
                        <a class="text-white/70 hover:text-white transition" href="{{ route('login') }}">Entrar</a>
                    </div>
                </div>
            </div>
        </footer>

        <script>
            const slugInput = document.getElementById('slug');
            const openMenuButton = document.getElementById('openMenu');

            function openPublicMenu() {
                const slug = (slugInput?.value || '').trim();
                if (!slug) return;
                const safeSlug = encodeURIComponent(slug);
                window.location.href = `${window.location.origin}/menu/${safeSlug}`;
            }

            openMenuButton?.addEventListener('click', openPublicMenu);
            slugInput?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') openPublicMenu();
            });
        </script>
    </body>
</html>

