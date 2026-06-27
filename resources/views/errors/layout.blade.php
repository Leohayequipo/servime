<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'ServiME') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, system-ui, sans-serif;
            background: #07080c; color: #fff; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px; overflow: hidden; text-align: center;
        }
        .orb { position: fixed; border-radius: 50%; filter: blur(100px); pointer-events: none; z-index: 0; }
        .orb-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(245,158,11,.15) 0%, transparent 65%); top: -220px; left: -180px; }
        .orb-2 { width: 420px; height: 420px; background: radial-gradient(circle, rgba(16,185,129,.08) 0%, transparent 65%); bottom: -100px; right: -90px; }

        .card { position: relative; z-index: 1; max-width: 480px; animation: fade-up .5s cubic-bezier(.22,1,.36,1) both; }
        @keyframes fade-up { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }

        .emoji { font-size: 60px; line-height: 1; margin-bottom: 4px; }
        .code {
            font-size: clamp(80px, 18vw, 140px); font-weight: 800; line-height: 1; letter-spacing: -.04em;
            background: linear-gradient(135deg, #f59e0b, #fbbf24); -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .title { font-size: clamp(20px, 5vw, 28px); font-weight: 700; margin-top: 6px; }
        .msg { color: #94a3b8; margin-top: 12px; font-size: 15px; line-height: 1.6; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 28px;
            background: #f59e0b; color: #1a0f00; font-weight: 700; font-size: 14px;
            padding: 12px 24px; border-radius: 14px; text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(245,158,11,.35); }
        .footer { margin-top: 30px; font-size: 11px; color: #374151; letter-spacing: .04em; }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="card">
        <div class="emoji">@yield('emoji', '🍽️')</div>
        <div class="code">@yield('code')</div>
        <h1 class="title">@yield('title')</h1>
        <p class="msg">@yield('message')</p>
        <a href="{{ url('/') }}" class="btn">← Volver al inicio</a>
        <p class="footer">Powered by ServiME IA</p>
    </div>
</body>
</html>
