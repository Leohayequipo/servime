<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $seoDesc = $comercio->descripcion
            ? \Illuminate\Support\Str::limit(strip_tags($comercio->descripcion), 155)
            : 'Mirá el menú de '.$comercio->nombre.' desde tu celular: platos, precios y promociones actualizadas. Consultá al asistente y armá tu pedido.';
        $seoImg = ! empty($comercio->logo_url) ? asset('storage/'.$comercio->logo_url) : asset('images/og-image.png');

        // Se arma acá (no con @json) porque el directivo de Blade corta la expresión.
        $schemaJson = json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => $comercio->nombre,
            'url' => route('menu.public', $comercio->slug),
            'hasMenu' => route('menu.classic', $comercio->slug),
            'image' => $seoImg,
            'description' => $seoDesc,
            'telephone' => $comercio->telefono ?? null,
            'address' => $comercio->direccion ?? null,
        ], fn ($v) => ! is_null($v) && $v !== ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp

    <title>Menú de {{ $comercio->nombre }} — carta digital online</title>
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ route('menu.public', $comercio->slug) }}">
    <meta name="robots" content="index, follow, max-image-preview:large">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $comercio->nombre }}">
    <meta property="og:title" content="Menú de {{ $comercio->nombre }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ route('menu.public', $comercio->slug) }}">
    <meta property="og:image" content="{{ $seoImg }}">
    <meta property="og:locale" content="es_AR">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">{!! $schemaJson !!}</script>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

        /* Misma paleta que el chat y el menú clásico: neutros cálidos fijos + UN acento.
           El fondo era #07080c (un negro frío) y no coincidía con las otras dos vistas. */
        :root {
            --bg:        #0F0D0C;
            --surface:   #1A1614;
            --surface-2: #241F1B;
            --text:      #F5F2EF;
            --text-dim:  #A39A93;
            --accent: {{ $comercio->colorAcento() }};
            --accent-soft: #E0A458;
            --accent-soft: color-mix(in srgb, var(--accent) 55%, #FFF3E2);
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        @media (prefers-reduced-motion: reduce) {
            .orb { animation: none !important; }
        }

        /* ── Orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(100px);
            z-index: 0;
        }
        .orb-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, color-mix(in srgb, var(--accent) 15%, transparent) 0%, transparent 65%);
            top: -200px; left: -200px;
            animation: drift-a 14s ease-in-out infinite;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(200,127,53,0.07) 0%, transparent 65%);
            bottom: -80px; right: -80px;
            animation: drift-b 17s ease-in-out infinite;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, color-mix(in srgb, var(--accent) 5%, transparent) 0%, transparent 65%);
            top: 50%; left: 35%;
            animation: drift-c 20s ease-in-out infinite;
        }

        @keyframes drift-a {
            0%,100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(50px,-40px) scale(1.08); }
        }
        @keyframes drift-b {
            0%,100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(-30px,40px) scale(0.92); }
        }
        @keyframes drift-c {
            0%,100% { transform: translate(0,0); }
            50%      { transform: translate(-50px,-25px); }
        }

        /* ── Grain ── */
        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: 0.025;
            pointer-events: none;
            z-index: 1;
        }

        /* ── Entradas ── */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fade-in {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .fu { animation: fade-up 0.55s cubic-bezier(0.22,1,0.36,1) both; }
        .fi { animation: fade-in 0.6s ease both; }
        .d0 { animation-delay: 0.00s; }
        .d1 { animation-delay: 0.09s; }
        .d2 { animation-delay: 0.18s; }
        .d3 { animation-delay: 0.28s; }
        .d4 { animation-delay: 0.38s; }
        .d5 { animation-delay: 0.50s; }
        .d6 { animation-delay: 0.62s; }

        /* ── Logo ── */
        @keyframes ring-pulse {
            0%,100% { transform: scale(1);    opacity: 0.4; }
            50%      { transform: scale(1.12); opacity: 0.1; }
        }
        .logo-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .logo-ring {
            position: absolute; inset: -10px;
            border-radius: 50%;
            border: 1.5px solid color-mix(in srgb, var(--accent) 35%, transparent);
            animation: ring-pulse 3s ease-in-out infinite;
        }
        .logo-ring-2 {
            position: absolute; inset: -20px;
            border-radius: 50%;
            border: 1px solid color-mix(in srgb, var(--accent) 15%, transparent);
            animation: ring-pulse 3s ease-in-out infinite 0.9s;
        }
        .logo-glow {
            position: absolute; inset: -30px;
            border-radius: 50%;
            background: radial-gradient(circle, color-mix(in srgb, var(--accent) 12%, transparent) 0%, transparent 70%);
            animation: ring-pulse 3s ease-in-out infinite 0.4s;
        }

        /* ── Layout ── */
        .page {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
        }
        .content {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        /* ── Sección identidad ── */
        .identity { margin-bottom: 32px; }

        /* ── Label pregunta ── */
        .question-label {
            font-size: 11px;
            letter-spacing: 0.12em;
            font-weight: 600;
            color: #5C534C;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        /* ── Dot open ── */
        @keyframes dot-pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
            50%      { box-shadow: 0 0 0 4px rgba(34,197,94,0); }
        }
        .dot-open {
            display: inline-block;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--accent);
            animation: dot-pulse 2.2s ease-in-out infinite;
            flex-shrink: 0;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 36px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #3A322C;
        }
        .footer-dot {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: #3A322C;
        }

        /* ── Barra IA (buscador/chat) ── */
        @keyframes bar-glow {
            0%, 100% { box-shadow: 0 0 0 1.5px color-mix(in srgb, var(--accent) 50%, transparent), 0 0 16px color-mix(in srgb, var(--accent) 10%, transparent); }
            50%      { box-shadow: 0 0 0 1.5px color-mix(in srgb, var(--accent) 95%, transparent), 0 0 34px color-mix(in srgb, var(--accent) 42%, transparent); }
        }
        .ai-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding: 11px 13px;
            border-radius: 20px;
            background: rgba(255,255,255,0.035);
            border: 1.5px solid color-mix(in srgb, var(--accent) 60%, transparent);
            backdrop-filter: blur(10px);
            text-decoration: none;
            animation: bar-glow 1.2s ease-in-out infinite;
            transition: transform 0.2s cubic-bezier(0.22,1,0.36,1);
            margin-bottom: 18px;
        }
        .ai-bar:hover  { transform: translateY(-2px); }
        .ai-bar:active { transform: scale(0.99); }
        .ai-bar-icon {
            flex-shrink: 0;
            width: 46px; height: 46px;
            border-radius: 13px;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 16px color-mix(in srgb, var(--accent) 35%, transparent);
        }
        .ai-bar-text {
            flex: 1;
            min-width: 0;
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            color: #F5F2EF;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @keyframes caret-blink { 0%,100% { opacity: 1; } 50% { opacity: 0; } }
        .ai-caret {
            color: var(--accent);
            font-weight: 400;
            margin-left: 1px;
            animation: caret-blink 0.85s step-end infinite;
        }
        .ai-bar-arrow {
            flex-shrink: 0;
            color: var(--accent);
            display: flex; align-items: center;
        }

        /* ── Chips de preguntas frecuentes ── */
        .chips-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-bottom: 22px;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            color: #D6CFC8;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }
        .chip:hover {
            border-color: color-mix(in srgb, var(--accent) 40%, transparent);
            background: color-mix(in srgb, var(--accent) 8%, transparent);
            transform: translateY(-1px);
        }

        /* ── Link discreto al menú clásico ── */
        .classic-link {
            font-size: 13px;
            color: #8A817A;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 0.2s ease;
        }
        .classic-link:hover { color: #A39A93; }
    </style>
</head>
<body>

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="page">
        <div class="content">

            <!-- Logo + identidad -->
            <div class="identity fu d0">
                <!-- Logo -->
                <div style="margin-bottom:20px;">
                    <div class="logo-wrap">
                        <div class="logo-glow"></div>
                        <div class="logo-ring-2"></div>
                        <div class="logo-ring"></div>
                        @if(!empty($comercio->logo_url))
                            <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                                 alt="{{ $comercio->nombre }}"
                                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                                        border:2px solid color-mix(in srgb, var(--accent) 30%, transparent);
                                        box-shadow:0 0 32px color-mix(in srgb, var(--accent) 18%, transparent);
                                        display:block; position:relative; z-index:1;">
                        @else
                            <div style="width:80px;height:80px;border-radius:50%;
                                        background:linear-gradient(135deg,color-mix(in srgb, var(--accent) 22%, transparent),color-mix(in srgb, var(--accent) 6%, transparent));
                                        border:2px solid color-mix(in srgb, var(--accent) 30%, transparent);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:30px;font-weight:700;color:var(--accent);
                                        box-shadow:0 0 32px color-mix(in srgb, var(--accent) 15%, transparent);
                                        position:relative;z-index:1;">
                                {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pill -->
                <div class="fu d1" style="margin-bottom:12px;">
                    <span style="font-size:10px;letter-spacing:0.14em;font-weight:700;
                                 background:color-mix(in srgb, var(--accent) 10%, transparent);color:var(--accent);
                                 padding:4px 14px;border-radius:999px;
                                 border:1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                        MENÚ DIGITAL
                    </span>
                </div>

                <!-- Nombre -->
                <div class="fu d2">
                    <h1 style="font-size:clamp(28px,8vw,44px);font-weight:700;line-height:1.12;
                               letter-spacing:-0.03em;color:#ffffff;margin-bottom:12px;">
                        {{ $comercio->nombre }}
                    </h1>
                </div>

                <!-- Meta -->
                <div class="fu d3" style="display:flex;align-items:center;justify-content:center;
                                          gap:10px;font-size:12.5px;color:#5C534C;">
                    @if(!empty($comercio->ciudad))
                        <span>{{ $comercio->ciudad }}</span>
                        <span style="color:#3A322C;">·</span>
                    @endif
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span class="dot-open"></span>
                        <span>Abierto ahora</span>
                    </span>
                </div>
            </div>

            <!-- Pregunta -->
            <p class="question-label fu d4">¿Cómo querés explorar el menú?</p>

            <!-- Barra IA + accesos rápidos -->
            <div style="width:100%;" class="fu d5">

                <!-- Barra tipo buscador/chat: lleva al chat con IA -->
                <a href="{{ route('menu.chat', $comercio) }}" class="ai-bar" data-track-choice="ia" data-track-desde="cta">
                    <span class="ai-bar-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="rgba(26,15,0,0.9)">
                            <path d="M12 2l1.8 5.4L19 9l-5.2 1.6L12 16l-1.8-5.4L5 9l5.2-1.6L12 2z"/>
                            <path d="M19 13.5l.85 2.35L22 16.7l-2.15.7L19 19.7l-.85-2.3L16 16.7l2.15-.85L19 13.5z"/>
                        </svg>
                    </span>
                    <span class="ai-bar-text"><span id="aiTypewriter"></span><span class="ai-caret">|</span></span>
                    <span class="ai-bar-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>

                {{-- Chips generados a partir del menú real del comercio (pre-cargan la pregunta
                     en el chat). data-track-choice: sin esto, quien entraba al chat por un chip
                     no contaba como que había elegido, y el embudo del panel daba ~1% de
                     elección. Eso disparaba siempre la sugerencia "muchos entran pero pocos
                     eligen", apuntando a arreglar una pantalla que en realidad funcionaba. --}}
                @if(!empty($chips))
                    <div class="chips-row fu d6">
                        @foreach($chips as $chip)
                            <a class="chip" data-track-choice="ia" data-track-desde="chip" data-track-label="{{ $chip['label'] }}" href="{{ route('menu.chat', ['comercio' => $comercio, 'q' => $chip['q']]) }}">{{ $chip['emoji'] }} {{ $chip['label'] }}</a>
                        @endforeach
                    </div>
                @endif

                <!-- Link discreto al menú tradicional -->
                <div class="fu d6" style="text-align:center;margin-top:2px;">
                    <a href="{{ route('menu.classic', $comercio) }}" class="classic-link" data-track-choice="classic" data-track-desde="completo">Prefiero ver el menú completo</a>
                </div>

            </div>

            <!-- Footer -->
            <div class="footer fu d6">
                <span>{{ $comercio->nombre }}</span>
                <span class="footer-dot"></span>
                <span>Powered by ServiME IA</span>
            </div>

        </div>
    </div>

    <script>
        // Efecto máquina de escribir en la barra IA
        (function () {
            var phrases = [
                '¿Qué me recomendás hoy?',
                '¿Tienen opciones sin TACC?',
                '¿Qué tiene el combo de la tarde?',
                'Preguntame lo que quieras...'
            ];
            var el = document.getElementById('aiTypewriter');
            if (!el) return;
            var pi = 0, ci = 0, deleting = false;
            function tick() {
                var full = phrases[pi];
                if (!deleting) {
                    ci++;
                    el.textContent = full.slice(0, ci);
                    if (ci === full.length) { deleting = true; return setTimeout(tick, 1600); }
                    return setTimeout(tick, 55 + Math.random() * 45);
                }
                ci--;
                el.textContent = full.slice(0, ci);
                if (ci === 0) { deleting = false; pi = (pi + 1) % phrases.length; return setTimeout(tick, 350); }
                return setTimeout(tick, 28);
            }
            setTimeout(tick, 500);
        })();
    </script>

    @include('menu.partials.tracking', ['trackView' => 'choose'])
</body>
</html>
