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

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #07080c;
            color: #fff;
            overflow-x: hidden;
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
            background: radial-gradient(circle, rgba(245,158,11,0.15) 0%, transparent 65%);
            top: -200px; left: -200px;
            animation: drift-a 14s ease-in-out infinite;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(16,185,129,0.08) 0%, transparent 65%);
            bottom: -80px; right: -80px;
            animation: drift-b 17s ease-in-out infinite;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(245,158,11,0.05) 0%, transparent 65%);
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
            border: 1.5px solid rgba(245,158,11,0.35);
            animation: ring-pulse 3s ease-in-out infinite;
        }
        .logo-ring-2 {
            position: absolute; inset: -20px;
            border-radius: 50%;
            border: 1px solid rgba(245,158,11,0.15);
            animation: ring-pulse 3s ease-in-out infinite 0.9s;
        }
        .logo-glow {
            position: absolute; inset: -30px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245,158,11,0.12) 0%, transparent 70%);
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
            color: #374151;
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
            background: #22c55e;
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
            color: #1f2937;
        }
        .footer-dot {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: #1f2937;
        }

        /* ── Barra IA (buscador/chat) ── */
        @keyframes bar-glow {
            0%, 100% { box-shadow: 0 0 0 1.5px rgba(245,158,11,0.5), 0 0 16px rgba(245,158,11,0.10); }
            50%      { box-shadow: 0 0 0 1.5px rgba(245,158,11,0.95), 0 0 34px rgba(245,158,11,0.42); }
        }
        .ai-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding: 11px 13px;
            border-radius: 20px;
            background: rgba(255,255,255,0.035);
            border: 1.5px solid rgba(245,158,11,0.6);
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
            background: #f59e0b;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 16px rgba(245,158,11,0.35);
        }
        .ai-bar-text {
            flex: 1;
            min-width: 0;
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            color: #e5e7eb;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @keyframes caret-blink { 0%,100% { opacity: 1; } 50% { opacity: 0; } }
        .ai-caret {
            color: #f59e0b;
            font-weight: 400;
            margin-left: 1px;
            animation: caret-blink 0.85s step-end infinite;
        }
        .ai-bar-arrow {
            flex-shrink: 0;
            color: #f59e0b;
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
            color: #d1d5db;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }
        .chip:hover {
            border-color: rgba(245,158,11,0.4);
            background: rgba(245,158,11,0.08);
            transform: translateY(-1px);
        }

        /* ── Link discreto al menú clásico ── */
        .classic-link {
            font-size: 13px;
            color: #6b7280;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 0.2s ease;
        }
        .classic-link:hover { color: #9ca3af; }
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
                                        border:2px solid rgba(245,158,11,0.3);
                                        box-shadow:0 0 32px rgba(245,158,11,0.18);
                                        display:block; position:relative; z-index:1;">
                        @else
                            <div style="width:80px;height:80px;border-radius:50%;
                                        background:linear-gradient(135deg,rgba(245,158,11,0.22),rgba(245,158,11,0.06));
                                        border:2px solid rgba(245,158,11,0.3);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:30px;font-weight:700;color:#f59e0b;
                                        box-shadow:0 0 32px rgba(245,158,11,0.15);
                                        position:relative;z-index:1;">
                                {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pill -->
                <div class="fu d1" style="margin-bottom:12px;">
                    <span style="font-size:10px;letter-spacing:0.14em;font-weight:700;
                                 background:rgba(245,158,11,0.1);color:#f59e0b;
                                 padding:4px 14px;border-radius:999px;
                                 border:1px solid rgba(245,158,11,0.2);">
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
                                          gap:10px;font-size:12.5px;color:#374151;">
                    @if(!empty($comercio->ciudad))
                        <span>{{ $comercio->ciudad }}</span>
                        <span style="color:#1f2937;">·</span>
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
                <a href="{{ route('menu.chat', $comercio) }}" class="ai-bar" data-track-choice="ia">
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

                <!-- Chips generados a partir del menú real del comercio (pre-cargan la pregunta en el chat) -->
                @if(!empty($chips))
                    <div class="chips-row fu d6">
                        @foreach($chips as $chip)
                            <a class="chip" href="{{ route('menu.chat', ['comercio' => $comercio, 'q' => $chip['q']]) }}">{{ $chip['emoji'] }} {{ $chip['label'] }}</a>
                        @endforeach
                    </div>
                @endif

                <!-- Link discreto al menú tradicional -->
                <div class="fu d6" style="text-align:center;margin-top:2px;">
                    <a href="{{ route('menu.classic', $comercio) }}" class="classic-link" data-track-choice="classic">Prefiero ver el menú completo</a>
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
