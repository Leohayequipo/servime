<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F2EDE3">

    <title>{{ config('app.name', 'ServiME') }} — Elegí tu contraseña</title>

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
        /* Alias locales sobre los tokens de app.css, igual que el login. No repetir los
           hex acá es lo que hace que cambiar un color de marca sea editar una línea. */
        :root {
            --ground:      rgb(var(--c-bg));
            --surface:     rgb(var(--c-elev-2));
            --ink:         rgb(var(--c-ink));
            --muted:       rgb(var(--c-ink-soft));
            --line:        rgb(var(--c-ink) / .14);
            --accent:      rgb(var(--c-accent));
            --accent2:     rgb(var(--c-accent-deep));
            --accent-ink:  rgb(var(--c-accent-deep));
            --sage:        rgb(var(--c-sage));
            --wood:        rgb(var(--c-wood));
            --shadow-md:   0 2px 6px rgb(var(--c-ink) / .07), 0 12px 32px rgb(var(--c-ink) / .07);
            --velo: .76;
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

        .video-fondo {
            position: fixed; inset: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: -20;
            filter: blur(7px) saturate(.85);
            -webkit-filter: blur(7px) saturate(.85);
        }
        .video-velo {
            position: fixed; inset: 0; z-index: -10;
            background: rgba(242, 237, 227, var(--velo));
        }
        body::before {
            content: "";
            position: fixed; inset: 0; z-index: -5; pointer-events: none;
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
            display: block; font-size: .8rem; font-weight: 700;
            color: var(--muted); margin-bottom: .45rem;
        }

        .campo {
            width: 100%; border-radius: 1rem; padding: .8rem 1rem;
            font-size: .95rem; color: var(--ink);
            background: var(--ground); border: 1px solid var(--line);
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .campo::placeholder { color: var(--sage); }
        .campo:focus {
            outline: none; background: var(--surface);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(192, 86, 58, .16);
        }
        .campo.con-error { border-color: var(--accent-ink); }

        .error { color: var(--accent-ink); font-size: .82rem; margin-top: .4rem; }

        .requisitos {
            background: var(--ground);
            border: 1px solid var(--line);
            border-left: 3px solid var(--wood);
            border-radius: .9rem;
            padding: .9rem 1.1rem;
            margin-bottom: 1.5rem;
        }
        .requisitos p {
            margin: 0;
            font-size: .84rem;
            line-height: 1.75;
            color: var(--muted);
        }

        .boton {
            width: 100%; display: inline-flex; align-items: center; justify-content: center;
            padding: .85rem 1.5rem; border-radius: 1rem;
            font-weight: 700; font-size: 1rem; color: #fff;
            background: var(--accent); border: 0; cursor: pointer;
            transition: background .18s ease, transform .18s ease;
        }
        .boton:hover { background: var(--accent2); }
        .boton:active { transform: translateY(1px); }

        .enlace { color: var(--accent-ink); text-decoration: none; transition: color .15s ease; }
        .enlace:hover { color: var(--accent2); text-decoration: underline; }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
            border-radius: .5rem;
        }
    </style>
</head>
<body>
    {{-- El <source> lo pone el script del final sólo en pantallas grandes: son 3 MB de
         fondo decorativo y no valen los datos de quien entra del celular. --}}
    <video id="video-fondo" class="video-fondo" autoplay loop muted playsinline
           preload="none" aria-hidden="true" tabindex="-1"></video>
    <div class="video-velo" aria-hidden="true"></div>

    <main class="w-full relative" style="max-width: 26rem; z-index: 1;">

        <div class="text-center" style="margin-bottom: 1.75rem;">
            <picture>
                <source srcset="{{ asset('images/logo_servime.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME"
                     width="96" height="96" fetchpriority="high" decoding="async"
                     style="height:96px;width:96px;">
            </picture>
            <h1 class="font-display" style="font-size:1.8rem; margin:1rem 0 .35rem; line-height:1.2;">
                Elegí tu contraseña
            </h1>
            <p style="color:var(--muted); font-size:.95rem;">
                Estás usando la contraseña inicial, que es la misma para varias cuentas.
                Elegí una que sea solo tuya.
            </p>
        </div>

        <div class="tarjeta" style="padding: 2rem 1.75rem;">
            <form method="POST" action="{{ route('password.cambiar.guardar') }}">
                @csrf
                @method('PUT')

                {{-- Para que el gestor de contraseñas del navegador la guarde con el usuario correcto. --}}
                <input type="text" name="username" value="{{ auth()->user()->username ?: auth()->user()->email }}"
                       autocomplete="username" hidden readonly>

                <div style="margin-bottom:1.25rem;">
                    <label for="password" class="etiqueta">Nueva contraseña</label>
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autofocus
                           autocomplete="new-password"
                           class="campo @error('password') con-error @enderror"
                           placeholder="••••••••">
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label for="password_confirmation" class="etiqueta">Repetir la contraseña</label>
                    <input id="password_confirmation"
                           type="password"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           class="campo"
                           placeholder="••••••••">
                </div>

                <div class="requisitos">
                    <p>Al menos 8 caracteres</p>
                    <p>Distinta de la contraseña inicial</p>
                </div>

                <button type="submit" class="boton">Guardar y entrar al panel</button>
            </form>

            <div class="text-center" style="margin-top:1.5rem;">
                <a href="{{ route('dashboard') }}" class="enlace" style="font-size:.88rem;">
                    Ahora no, seguir al panel
                </a>
            </div>
        </div>

        <div class="text-center" style="margin-top:1.75rem;">
            <p style="color:var(--sage); font-size:.78rem;">
                © {{ date('Y') }} ServiME. Todos los derechos reservados.
            </p>
        </div>
    </main>

    <script>
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

            var promesa = video.play();
            if (promesa && promesa.catch) promesa.catch(function () {});
        })();
    </script>
</body>
</html>
