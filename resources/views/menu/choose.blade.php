<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comercio->nombre }} · {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #07080c;
            color: #fff;
            overflow-x: hidden;
        }

        /* ── Fondo con orbs animados ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(90px);
            z-index: 0;
        }
        .orb-amber {
            width: 520px; height: 520px;
            background: radial-gradient(circle, rgba(245,158,11,0.18) 0%, transparent 68%);
            top: -160px; left: -160px;
            animation: drift-a 12s ease-in-out infinite;
        }
        .orb-green {
            width: 360px; height: 360px;
            background: radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 68%);
            bottom: -60px; right: -80px;
            animation: drift-b 15s ease-in-out infinite;
        }
        .orb-mid {
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(245,158,11,0.06) 0%, transparent 68%);
            top: 55%; left: 40%;
            animation: drift-c 18s ease-in-out infinite;
        }

        @keyframes drift-a {
            0%,100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(40px,-30px) scale(1.06); }
        }
        @keyframes drift-b {
            0%,100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(-25px,35px) scale(0.94); }
        }
        @keyframes drift-c {
            0%,100% { transform: translate(0,0); }
            50%      { transform: translate(-40px,-20px); }
        }

        /* ── Grain overlay ── */
        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: 0.028;
            pointer-events: none;
            z-index: 1;
        }

        /* ── Entradas ── */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fade-in {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .fu { animation: fade-up 0.5s cubic-bezier(0.22,1,0.36,1) both; }
        .fi { animation: fade-in 0.6s ease both; }
        .d0 { animation-delay: 0.0s; }
        .d1 { animation-delay: 0.08s; }
        .d2 { animation-delay: 0.16s; }
        .d3 { animation-delay: 0.24s; }
        .d4 { animation-delay: 0.32s; }
        .d5 { animation-delay: 0.42s; }
        .d6 { animation-delay: 0.52s; }

        /* ── Logo ring ── */
        @keyframes ring-pulse {
            0%,100% { transform: scale(1);   opacity: 0.35; }
            50%      { transform: scale(1.1); opacity: 0.12; }
        }
        .logo-ring {
            position: absolute; inset: -8px;
            border-radius: 50%;
            border: 1.5px solid rgba(245,158,11,0.4);
            animation: ring-pulse 3s ease-in-out infinite;
        }
        .logo-ring-2 {
            position: absolute; inset: -16px;
            border-radius: 50%;
            border: 1px solid rgba(245,158,11,0.18);
            animation: ring-pulse 3s ease-in-out infinite 0.8s;
        }

        /* ── Card IA (amber sólido) ── */
        .card-ia {
            background: #f59e0b;
            border-radius: 18px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            min-height: 190px;
            position: relative;
            overflow: hidden;
            transition: transform 0.22s cubic-bezier(0.22,1,0.36,1),
                        box-shadow 0.22s ease;
        }
        /* Shimmer en reposo (sutil) */
        .card-ia::before {
            content: '';
            position: absolute;
            top: 0; left: -75%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.12), transparent);
            transform: skewX(-15deg);
            animation: shimmer-idle 4s ease-in-out infinite 1.2s;
        }
        @keyframes shimmer-idle {
            0%   { left: -75%; opacity: 0; }
            10%  { opacity: 1; }
            40%  { left: 130%; opacity: 0; }
            100% { left: 130%; opacity: 0; }
        }
        /* Shimmer fuerte al hover */
        .card-ia::after {
            content: '';
            position: absolute;
            top: 0; left: -75%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.22), transparent);
            transform: skewX(-15deg);
            transition: none;
            opacity: 0;
        }
        .card-ia:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 28px 56px rgba(245,158,11,0.32),
                        0 0 0 1px rgba(245,158,11,0.5);
        }
        .card-ia:hover::after {
            animation: shimmer-hover 0.55s ease-out forwards;
        }
        @keyframes shimmer-hover {
            0%   { left: -75%; opacity: 1; }
            100% { left: 130%; opacity: 0.6; }
        }

        .card-ia .icon-wrap {
            background: rgba(0,0,0,0.14);
            border-radius: 50%;
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .card-ia .card-title { font-size: 16px; font-weight: 700; color: #1a0f00; margin-bottom: 5px; }
        .card-ia .card-desc  { font-size: 12px; line-height: 1.55; color: rgba(26,15,0,0.62); }
        .card-ia .card-arrow { font-size: 20px; color: rgba(26,15,0,0.45); transition: transform 0.2s ease; }
        .card-ia:hover .card-arrow { transform: translate(2px,-2px); }

        /* Etiqueta IA */
        .badge-ia {
            position: absolute;
            bottom: 22px; right: 22px;
            font-size: 9px; font-weight: 700; letter-spacing: 0.1em;
            color: rgba(26,15,0,0.5);
            background: rgba(0,0,0,0.08);
            padding: 3px 8px; border-radius: 999px;
        }

        /* ── Card clásica ── */
        .card-classic {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(8px);
            border-radius: 18px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            min-height: 190px;
            position: relative;
            transition: border-color 0.22s ease,
                        background 0.22s ease,
                        transform 0.22s cubic-bezier(0.22,1,0.36,1),
                        box-shadow 0.22s ease;
        }
        .card-classic:hover {
            border-color: rgba(255,255,255,0.22);
            background: rgba(255,255,255,0.06);
            transform: translateY(-2px);
            box-shadow: 0 20px 48px rgba(0,0,0,0.4);
        }
        .card-classic .icon-wrap {
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s ease;
        }
        .card-classic:hover .icon-wrap { background: rgba(255,255,255,0.11); }
        .card-classic .card-title { font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 5px; }
        .card-classic .card-desc  { font-size: 12px; line-height: 1.55; color: #6b7280; }
        .card-classic .card-arrow { font-size: 20px; color: rgba(255,255,255,0.25); transition: transform 0.2s ease, color 0.2s ease; }
        .card-classic:hover .card-arrow { transform: translate(2px,-2px); color: rgba(255,255,255,0.55); }

        /* ── Dot status ── */
        @keyframes dot-pulse {
            0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
            50%      { opacity: 0.7; box-shadow: 0 0 0 4px rgba(34,197,94,0); }
        }
        .dot-open {
            display: inline-block;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22c55e;
            animation: dot-pulse 2s ease-in-out infinite;
        }

        /* ── Separador ── */
        .divider {
            width: 32px; height: 1px;
            background: linear-gradient(to right, rgba(245,158,11,0.4), transparent);
        }

        /* ── Footer ── */
        .footer-brand {
            display: flex; align-items: center; gap: 10px;
        }
        .footer-logo {
            width: 36px; height: 36px;
            border-radius: 50%; object-fit: cover;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .footer-initials {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(245,158,11,0.05));
            border: 1px solid rgba(245,158,11,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 600; color: #f59e0b;
        }
    </style>
</head>
<body>

    <!-- Orbs de fondo -->
    <div class="orb orb-amber"></div>
    <div class="orb orb-green"></div>
    <div class="orb orb-mid"></div>

    <!-- Contenido -->
    <div style="position:relative; z-index:2; min-height:100vh; display:flex; flex-direction:column;
                padding: 44px 28px 36px; max-width: 480px;">

        <!-- Logo del restaurante -->
        <div class="fu d0" style="margin-bottom:28px;">
            <div style="position:relative; display:inline-block;">
                <div class="logo-ring-2"></div>
                <div class="logo-ring"></div>
                @if(!empty($comercio->logo_url))
                    <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                         alt="{{ $comercio->nombre }}"
                         style="width:64px;height:64px;border-radius:50%;object-fit:cover;
                                display:block; border:2px solid rgba(245,158,11,0.25);
                                box-shadow:0 0 24px rgba(245,158,11,0.15);">
                @else
                    <div style="width:64px;height:64px;border-radius:50%;
                                background:linear-gradient(135deg,rgba(245,158,11,0.25),rgba(245,158,11,0.08));
                                border:2px solid rgba(245,158,11,0.3);
                                display:flex;align-items:center;justify-content:center;
                                font-size:24px;font-weight:700;color:#f59e0b;
                                box-shadow:0 0 24px rgba(245,158,11,0.12);">
                        {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Tag pill -->
        <div class="fu d1" style="margin-bottom:14px;">
            <span style="font-size:10px; letter-spacing:0.14em; font-weight:700;
                         background:rgba(245,158,11,0.1); color:#f59e0b;
                         padding:4px 12px; border-radius:999px;
                         border:1px solid rgba(245,158,11,0.22);">
                MENÚ DIGITAL
            </span>
        </div>

        <!-- Nombre -->
        <div class="fu d2" style="margin-bottom:12px;">
            <h1 style="font-size:clamp(30px,7vw,42px); font-weight:600; line-height:1.15;
                       letter-spacing:-0.025em; color:#ffffff;">
                {{ $comercio->nombre }}
            </h1>
        </div>

        <!-- Metadata -->
        <div class="fu d3" style="display:flex;align-items:center;gap:10px;
                                  font-size:13px;color:#4b5563;margin-bottom:28px;">
            @if(!empty($comercio->ciudad))
                <span>{{ $comercio->ciudad }}</span>
                <span style="color:#2d3748;">·</span>
            @endif
            <span style="display:flex;align-items:center;gap:6px;">
                <span class="dot-open"></span>
                <span style="color:#374151;">Abierto ahora</span>
            </span>
        </div>

        <!-- Divisor + pregunta -->
        <div class="fu d3" style="margin-bottom:20px;">
            <div class="divider" style="margin-bottom:16px;"></div>
            <p style="font-size:12.5px;color:#374151;font-weight:400;letter-spacing:0.01em;">
                ¿Cómo querés explorar el menú?
            </p>
        </div>

        <!-- Cards -->
        <div class="fu d4" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:auto;">

            <!-- Card IA -->
            <a href="{{ route('menu.chat', $comercio) }}" class="card-ia">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div class="icon-wrap">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                             stroke="rgba(26,15,0,0.7)" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <circle cx="9" cy="10" r="1" fill="rgba(26,15,0,0.7)"/>
                            <circle cx="12" cy="10" r="1" fill="rgba(26,15,0,0.7)"/>
                            <circle cx="15" cy="10" r="1" fill="rgba(26,15,0,0.7)"/>
                        </svg>
                    </div>
                    <span class="card-arrow">↗</span>
                </div>
                <div style="margin-top:auto;padding-top:24px;">
                    <div class="card-title">Explorar con IA</div>
                    <div class="card-desc">Preguntá lo que quieras, te respondemos al instante.</div>
                </div>
                <span class="badge-ia">IA</span>
            </a>

            <!-- Card clásica -->
            <a href="{{ route('menu.classic', $comercio) }}" class="card-classic">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div class="icon-wrap">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="rgba(255,255,255,0.65)" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16M4 10h16M4 14h10M4 18h7"/>
                        </svg>
                    </div>
                    <span class="card-arrow">↗</span>
                </div>
                <div style="margin-top:auto;padding-top:24px;">
                    <div class="card-title">Menú clásico</div>
                    <div class="card-desc">Navegá todo el menú a tu ritmo.</div>
                </div>
            </a>

        </div>

        <!-- Footer -->
        <div class="fu d6" style="padding-top:40px;">
            <div class="footer-brand">
                @if(!empty($comercio->logo_url))
                    <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                         alt="{{ $comercio->nombre }}"
                         class="footer-logo">
                @else
                    <div class="footer-initials">
                        {{ strtoupper(substr($comercio->nombre, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div style="font-size:12px;font-weight:500;color:#4b5563;line-height:1.3;">
                        {{ $comercio->nombre }}
                    </div>
                    <div style="font-size:11px;color:#1f2937;line-height:1.3;">
                        Powered by ServiME IA
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
