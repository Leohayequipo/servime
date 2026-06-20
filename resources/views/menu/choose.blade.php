<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comercio->nombre }} · {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/servime_ia.ico') }}">
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

        /* ── Card IA: levitación ── */
        @keyframes float-card {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-8px); }
        }
        .card-ia-float {
            width: 100%;
            margin-bottom: 10px;
            position: relative;
            animation: float-card 3.8s ease-in-out infinite;
        }
        .card-ia-float:hover { animation-play-state: paused; }

        /* ── Card IA: glow que respira detrás ── */
        @keyframes glow-breathe {
            0%, 100% { opacity: 0.28; transform: scale(0.96); }
            50%       { opacity: 0.55; transform: scale(1.04); }
        }
        .card-ia-glow {
            position: absolute;
            inset: 12px 8px;
            border-radius: 22px;
            background: #f59e0b;
            filter: blur(24px);
            z-index: 0;
            animation: glow-breathe 3.8s ease-in-out infinite;
            pointer-events: none;
        }

        /* ── Sparkles ── */
        @keyframes sparkle-float {
            0%, 100% { opacity: 0; transform: translateY(0) scale(0.3); }
            30%, 70% { opacity: 1; }
            50%       { opacity: 0.85; transform: translateY(-16px) scale(1); }
        }
        .spark {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            background: rgba(255,255,255,0.8);
        }
        .spark-1 { width: 3px; height: 3px; right: 28px; bottom: 52px; animation: sparkle-float 2.8s ease-in-out infinite 0.0s; }
        .spark-2 { width: 2px; height: 2px; right: 52px; bottom: 38px; animation: sparkle-float 2.8s ease-in-out infinite 0.9s; }
        .spark-3 { width: 3px; height: 3px; right: 40px; bottom: 72px; animation: sparkle-float 2.8s ease-in-out infinite 1.7s; }

        /* ── Badge IA pulsante ── */
        @keyframes badge-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(26,15,0,0.2); }
            50%       { box-shadow: 0 0 0 5px rgba(26,15,0,0); }
        }
        .ia-badge-anim {
            animation: badge-pulse 2.5s ease-in-out infinite;
        }

        /* ── Separador ── */
        .divider {
            width: 1px;
            height: 28px;
            background: linear-gradient(to bottom, transparent, rgba(245,158,11,0.3), transparent);
            margin: 0 auto 28px;
        }

        /* ── Label pregunta ── */
        .question-label {
            font-size: 11px;
            letter-spacing: 0.12em;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        /* ── Card IA ── */
        .card-ia {
            display: block;
            width: 100%;
            background: #f59e0b;
            border-radius: 20px;
            padding: 24px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            margin-bottom: 10px;
            transition: transform 0.22s cubic-bezier(0.22,1,0.36,1),
                        box-shadow 0.22s ease;
            text-align: left;
        }
        /* shimmer en reposo */
        .card-ia::before {
            content: '';
            position: absolute;
            top: 0; left: -75%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.14), transparent);
            transform: skewX(-15deg);
            animation: shimmer-idle 4.5s ease-in-out infinite 1.5s;
        }
        @keyframes shimmer-idle {
            0%   { left: -75%; opacity: 0; }
            10%  { opacity: 1; }
            40%  { left: 135%; opacity: 0; }
            100% { left: 135%; opacity: 0; }
        }
        /* shimmer al hover */
        .card-ia::after {
            content: '';
            position: absolute;
            top: 0; left: -75%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.26), transparent);
            transform: skewX(-15deg);
            opacity: 0;
        }
        .card-ia:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 24px 52px rgba(245,158,11,0.35),
                        0 0 0 1px rgba(245,158,11,0.55);
        }
        .card-ia:hover::after {
            animation: shimmer-hover 0.55s ease-out forwards;
        }
        @keyframes shimmer-hover {
            0%   { left: -75%; opacity: 1; }
            100% { left: 135%; opacity: 0.5; }
        }

        .card-ia .ia-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-ia .ia-icon {
            width: 44px; height: 44px;
            background: rgba(0,0,0,0.16);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .card-ia .ia-badge {
            font-size: 9px; font-weight: 800; letter-spacing: 0.12em;
            color: rgba(26,15,0,0.45);
            background: rgba(0,0,0,0.1);
            padding: 4px 10px; border-radius: 999px;
        }
        .card-ia .ia-title {
            font-size: 20px; font-weight: 700;
            color: #1a0f00; margin-bottom: 6px;
            line-height: 1.2;
        }
        .card-ia .ia-desc {
            font-size: 13px; line-height: 1.55;
            color: rgba(26,15,0,0.58);
        }
        .card-ia .ia-cta {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 18px;
            font-size: 12px; font-weight: 700;
            color: rgba(26,15,0,0.55);
            letter-spacing: 0.04em;
        }

        /* ── Card clásica ── */
        .card-classic {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 18px 20px;
            text-decoration: none;
            transition: border-color 0.22s ease,
                        background 0.22s ease,
                        transform 0.22s cubic-bezier(0.22,1,0.36,1);
            text-align: left;
        }
        .card-classic:hover {
            border-color: rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.06);
            transform: translateY(-2px);
        }
        .card-classic .classic-left {
            display: flex; align-items: center; gap: 14px;
        }
        .card-classic .classic-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.06);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s ease;
        }
        .card-classic:hover .classic-icon { background: rgba(255,255,255,0.1); }
        .card-classic .classic-title {
            font-size: 15px; font-weight: 600; color: #fff;
            margin-bottom: 2px;
        }
        .card-classic .classic-desc {
            font-size: 11.5px; color: #4b5563;
        }
        .card-classic .classic-arrow {
            font-size: 18px; color: rgba(255,255,255,0.2);
            transition: transform 0.2s ease, color 0.2s ease;
            flex-shrink: 0;
        }
        .card-classic:hover .classic-arrow {
            transform: translate(2px,-2px);
            color: rgba(255,255,255,0.5);
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

            <!-- Divisor -->
            <div class="divider fu d4"></div>

            <!-- Pregunta -->
            <p class="question-label fu d4">¿Cómo querés explorar el menú?</p>

            <!-- Cards -->
            <div style="width:100%;" class="fu d5">

                <!-- Card IA -->
                <div class="card-ia-float">
                    <div class="card-ia-glow"></div>
                    <a href="{{ route('menu.chat', $comercio) }}" class="card-ia" style="position:relative;z-index:1;" data-track-choice="ia">
                        <!-- Sparkles -->
                        <span class="spark spark-1"></span>
                        <span class="spark spark-2"></span>
                        <span class="spark spark-3"></span>

                        <div class="ia-header">
                            <div class="ia-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                     stroke="rgba(26,15,0,0.75)" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    <circle cx="9" cy="10" r="1" fill="rgba(26,15,0,0.75)"/>
                                    <circle cx="12" cy="10" r="1" fill="rgba(26,15,0,0.75)"/>
                                    <circle cx="15" cy="10" r="1" fill="rgba(26,15,0,0.75)"/>
                                </svg>
                            </div>
                            <span class="ia-badge ia-badge-anim">IA</span>
                        </div>
                        <div class="ia-title">Explorar con IA</div>
                        <div class="ia-desc">Preguntá lo que quieras — qué tiene, qué te recomiendo, si hay opciones sin TACC — te respondemos al instante.</div>
                        <div class="ia-cta">
                            Empezar ahora
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                </div>

                <!-- Card clásica -->
                <a href="{{ route('menu.classic', $comercio) }}" class="card-classic" data-track-choice="classic">
                    <div class="classic-left">
                        <div class="classic-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                 stroke="rgba(255,255,255,0.55)" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 6h16M4 10h16M4 14h10M4 18h7"/>
                            </svg>
                        </div>
                        <div>
                            <div class="classic-title">Ver menú completo</div>
                            <div class="classic-desc">Navegá todas las secciones a tu ritmo</div>
                        </div>
                    </div>
                    <span class="classic-arrow">↗</span>
                </a>

            </div>

            <!-- Footer -->
            <div class="footer fu d6">
                <span>{{ $comercio->nombre }}</span>
                <span class="footer-dot"></span>
                <span>Powered by ServiME IA</span>
            </div>

        </div>
    </div>

    @include('menu.partials.tracking', ['trackView' => 'choose'])
</body>
</html>
