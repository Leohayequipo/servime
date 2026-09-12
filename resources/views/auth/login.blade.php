<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F2EDE3">

    <title>{{ config('app.name', 'ServiME') }} — Entrar</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=nunito-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Identidad ServiME. Los nombres cortos son alias locales: los valores salen
           de los tokens de app.css, que esta pantalla ya carga con Vite. Antes eran
           los hex copiados a mano y cambiar un color de marca obligaba a editar cuatro
           archivos — que para cuando se revisó ya estaban desincronizados: acá el papel
           había quedado en #FBF9F5, el viejo, cuando el resto ya usaba blanco puro.

           Los estilos siguen siendo propios y no usan .btn-primary / .input-field:
           esta pantalla tiene su propio diseño, pero no su propia paleta. */
        :root {
            --ground:      rgb(var(--c-bg));
            --surface:     rgb(var(--c-elev-2));
            --ink:         rgb(var(--c-ink));
            --muted:       rgb(var(--c-ink-soft));
            --line:        rgb(var(--c-ink) / .14);
            --accent:      rgb(var(--c-accent));
            --accent2:     rgb(var(--c-accent-deep));
            /* Era #B4462A, un cuarto terracota que existía sólo acá. `accent-deep` es
               el mismo rol (texto sobre crema) y mide mejor: 5,03:1 contra 4,67:1. */
            --accent-ink:  rgb(var(--c-accent-deep));
            --sage:        rgb(var(--c-sage));
            --wood:        rgb(var(--c-wood));
            --shadow-md:   0 2px 6px rgb(var(--c-ink) / .07), 0 12px 32px rgb(var(--c-ink) / .07);
        }

        html, body { min-height: 100%; }

        body {
            font-family: "Nunito Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--ground);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.25rem;
            position: relative;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Fondo ──
           El video se mantiene, pero el velo pasa de negro al 75% a crema: con el velo
           oscuro la pantalla se iba a la estética anterior por más que la tarjeta fuera
           clara. Ajustá --velo si lo querés más o menos presente. */
        :root { --velo: .76; }

        .video-fondo {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -20;
            filter: blur(7px) saturate(.85);
            -webkit-filter: blur(7px) saturate(.85);
        }

        .video-velo {
            position: fixed;
            inset: 0;
            z-index: -10;
            background: rgba(242, 237, 227, var(--velo));
        }

        /* El halo cálido de la landing, por encima del velo */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -5;
            pointer-events: none;
            background:
                radial-gradient(820px 520px at 78% 12%, rgba(212, 176, 140, .34), transparent 62%),
                radial-gradient(680px 480px at 14% 88%, rgba(167, 188, 168, .26), transparent 62%);
        }

        .font-display {
            font-family: Fraunces, ui-serif, Georgia, "Times New Roman", serif;
            font-weight: 600;
            letter-spacing: -0.015em;
        }

        .tarjeta {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 1.75rem;
            box-shadow: var(--shadow-md);
        }

        .etiqueta {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: .45rem;
        }

        .campo {
            width: 100%;
            border-radius: 1rem;
            padding: .8rem 1rem;
            font-size: .95rem;
            color: var(--ink);
            background: var(--ground);
            border: 1px solid var(--line);
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .campo::placeholder { color: var(--sage); }
        .campo:focus {
            outline: none;
            background: var(--surface);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(192, 86, 58, .16);
        }
        .campo.con-error { border-color: var(--accent-ink); }

        .error {
            color: var(--accent-ink);
            font-size: .82rem;
            margin-top: .4rem;
        }

        .boton {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .85rem 1.5rem;
            border-radius: 1rem;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            background: var(--accent);
            border: 0;
            cursor: pointer;
            transition: background .18s ease, transform .18s ease;
        }
        .boton:hover { background: var(--accent2); }
        .boton:active { transform: translateY(1px); }

        .enlace {
            color: var(--accent-ink);
            text-decoration: none;
            transition: color .15s ease;
        }
        .enlace:hover { color: var(--accent2); text-decoration: underline; }

        /* Casilla propia: la de app.css se pinta en ámbar */
        .casilla {
            width: 1.05rem;
            height: 1.05rem;
            border-radius: .3rem;
            border: 1px solid var(--sage);
            background: var(--ground);
            accent-color: var(--accent);
            cursor: pointer;
        }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
            border-radius: .5rem;
        }
    </style>
</head>
<body>
    {{-- El video pesa 3 MB, así que el <source> lo pone el script del final solo en
         pantallas grandes: en un celular con datos no se descarga. Sin JS tampoco carga,
         y la pantalla queda igual de usable sobre el fondo crema. --}}
    <video id="video-fondo" class="video-fondo" autoplay loop muted playsinline
           preload="none" aria-hidden="true" tabindex="-1"></video>
    <div class="video-velo" aria-hidden="true"></div>

    <main class="w-full relative" style="max-width: 26rem; z-index: 1;">

        <div class="text-center" style="margin-bottom: 1.75rem;">
            <a href="{{ url('/') }}" aria-label="ServiME, ir al inicio" class="inline-block">
                <picture>
                    <source srcset="{{ asset('images/logo_servime.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME"
                         width="96" height="96" fetchpriority="high" decoding="async"
                         style="height:96px;width:96px;">
                </picture>
            </a>
            <h1 class="font-display" style="font-size:1.9rem; margin:1rem 0 .35rem; line-height:1.2;">
                Entrá a tu panel
            </h1>
            <p style="color:var(--muted); font-size:.95rem;">
                Gestioná tu menú, tus promociones y tus resultados.
            </p>
        </div>

        @if(session('status'))
            <div style="margin-bottom:1.25rem; border-radius:1rem; padding:.8rem 1rem; font-size:.9rem; text-align:center;
                        background:rgba(107,140,122,.16); border:1px solid rgba(107,140,122,.45); color:var(--ink);">
                {{ session('status') }}
            </div>
        @endif

        <div class="tarjeta" style="padding: 2rem 1.75rem;">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Acepta email o nombre de usuario. El type pasa de "email" a "text"
                     porque con type=email el navegador rechaza "juan.perez" antes de
                     que el formulario llegue al servidor. --}}
                <div style="margin-bottom:1.25rem;">
                    <label for="email" class="etiqueta">Email o usuario</label>
                    <input id="email"
                           type="text"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autofocus
                           autocapitalize="none"
                           autocomplete="username"
                           class="campo @error('email') con-error @enderror"
                           placeholder="tu@email.com o tu.usuario">
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label for="password" class="etiqueta">Contraseña</label>
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           class="campo @error('password') con-error @enderror"
                           placeholder="••••••••">
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="margin-bottom:1.5rem;">
                    <label for="remember_me" class="flex items-center gap-2" style="cursor:pointer;">
                        <input id="remember_me" name="remember" type="checkbox" class="casilla">
                        <span style="font-size:.9rem; color:var(--muted);">Recordarme</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="enlace"
                           style="font-size:.88rem;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <button type="submit" class="boton">Iniciar sesión</button>
            </form>

            {{-- Registro cerrado: las altas las hace el equipo. Se deja para reactivarlo.
            @if (Route::has('register'))
                <div class="mt-6 text-center">
                    <p style="color:var(--muted); font-size:.9rem;">
                        ¿No tenés cuenta?
                        <a href="{{ route('register') }}" class="enlace">Registrate acá</a>
                    </p>
                </div>
            @endif --}}
        </div>

        <div class="text-center" style="margin-top:1.75rem; display:flex; flex-direction:column; gap:.5rem;">
            <a href="{{ url('/?chat=1') }}" class="enlace" style="font-size:.88rem;">
                ¿Necesitás ayuda? Escribinos
            </a>
            <p style="color:var(--sage); font-size:.78rem;">
                © {{ date('Y') }} ServiME. Todos los derechos reservados.
            </p>
        </div>
    </main>

    <script>
        // Carga el video sólo donde tiene sentido: en pantallas grandes y si el visitante
        // no pidió menos movimiento. Es un fondo decorativo — no vale gastarle 3 MB de
        // datos a alguien que entra del celular a escribir su contraseña.
        (function () {
            var video = document.getElementById('video-fondo');
            if (!video) return;

            var pantallaGrande = window.matchMedia('(min-width: 900px)').matches;
            var menosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!pantallaGrande || menosMovimiento) return;

            var source = document.createElement('source');
            source.type = 'video/mp4';
            source.src = @json(asset('videos/background_principal.mp4'));
            video.appendChild(source);
            video.load();

            // Si el navegador bloquea el autoplay, el fondo crema ya está debajo.
            var promesa = video.play();
            if (promesa && promesa.catch) promesa.catch(function () {});
        })();
    </script>
</body>
</html>
