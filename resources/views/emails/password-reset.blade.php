<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ServiME</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0b0c10;
            color: #ffffff;
            line-height: 1.6;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #111318;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .header {
            background: linear-gradient(135deg, #1c1f26 0%, #111318 100%);
            border-bottom: 2px solid rgba(245, 158, 11, 0.4);
            padding: 40px 60px;
            text-align: center;
        }

        .header img {
            height: 60px;
            width: auto;
        }

        .content {
            padding: 32px 40px;
        }

        .greeting {
            font-size: 1.4rem;
            color: #f59e0b;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message {
            font-size: 1rem;
            color: #94a3b8;
            margin-bottom: 35px;
            line-height: 1.8;
        }

        .button-container {
            text-align: center;
            margin: 40px 0;
        }

        .reset-button {
            display: inline-block;
            background: #f59e0b;
            color: #000000;
            text-decoration: none;
            padding: 16px 42px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.35);
        }

        .info-box {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 10px;
            padding: 16px 24px;
            margin: 30px 0;
            text-align: center;
        }

        .info-box .icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .info-box .text {
            color: #f59e0b;
            font-weight: 600;
            font-size: 1rem;
        }

        .warning {
            background: rgba(55, 65, 81, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 16px 24px;
            margin: 30px 0;
            text-align: center;
        }

        .warning .icon {
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .warning .text {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .alternative-link {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 10px;
            padding: 16px 24px;
            margin: 30px 0;
            text-align: center;
        }

        .alternative-link .text {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .alternative-link .url {
            color: #f59e0b;
            font-size: 0.78rem;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.4);
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
        }

        .footer {
            background: #1c1f26;
            padding: 35px 30px;
            text-align: center;
            border-top: 1px solid rgba(245, 158, 11, 0.15);
        }

        .footer .brand {
            color: #f59e0b;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .footer .text {
            color: #94a3b8;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .footer .links {
            margin-top: 20px;
        }

        .footer .links a {
            color: #f59e0b;
            text-decoration: none;
            margin: 0 12px;
            font-size: 0.9rem;
        }

        @media (max-width: 600px) {
            .content { padding: 24px 20px; }
            .header  { padding: 24px 20px; }
            .header img { height: 44px; }
            .reset-button { padding: 14px 28px; font-size: 0.95rem; }
            .info-box, .warning, .alternative-link { padding: 14px 20px; }
        }
    </style>
</head>
<body style="margin:0;padding:20px;background-color:#0b0c10;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;color:#ffffff;line-height:1.6;">
    <div class="email-container" style="max-width:600px;margin:0 auto;background:#111318;border-radius:20px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.5);border:1px solid rgba(245,158,11,0.2);">

        <!-- Header -->
        <div class="header" style="background:linear-gradient(135deg,#1c1f26 0%,#111318 100%);border-bottom:2px solid rgba(245,158,11,0.4);padding:40px 60px;text-align:center;">
            <img src="https://servime.net/images/logo_servime.png" alt="ServiME" style="height:200px;width:auto;display:block;margin:0 auto;">
        </div>

        <!-- Content -->
        <div class="content" style="padding:32px 40px;">

            <div class="greeting" style="font-size:1.4rem;color:#f59e0b;margin-bottom:20px;font-weight:600;">
                ¡Hola, {{ $user->name }}! 👋
            </div>

            <div class="message" style="font-size:1rem;color:#94a3b8;margin-bottom:35px;line-height:1.8;">
                Recibiste este email porque solicitaste recuperar tu contraseña en <strong>ServiME</strong>.
                <br><br>
                Si fuiste vos quien hizo esta solicitud, hacé clic en el botón de abajo para crear una nueva contraseña.
            </div>

            <!-- CTA -->
            <div class="button-container" style="text-align:center;margin:40px 0;">
                <a href="{{ $url }}" class="reset-button" style="display:inline-block;background:#f59e0b;color:#000000;text-decoration:none;padding:16px 42px;border-radius:8px;font-weight:700;font-size:1rem;text-transform:uppercase;letter-spacing:1px;box-shadow:0 8px 24px rgba(245,158,11,0.35);">
                    Recuperar Contraseña
                </a>
            </div>

            <!-- Expiry -->
            <div class="info-box" style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:10px;padding:16px 24px;margin:30px 0;text-align:center;">
                <div style="font-size:1.8rem;margin-bottom:10px;">⏰</div>
                <div style="color:#f59e0b;font-weight:600;font-size:1rem;">Este enlace expirará en 60 minutos</div>
            </div>

            <!-- Warning -->
            <div class="warning" style="background:rgba(55,65,81,0.5);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:16px 24px;margin:30px 0;text-align:center;">
                <div style="font-size:1.4rem;margin-bottom:10px;">⚠️</div>
                <div style="color:#94a3b8;font-size:0.95rem;">
                    Si no solicitaste este cambio, podés ignorar este email de forma segura. Tu contraseña no será modificada.
                </div>
            </div>

            <!-- Fallback URL -->
            <div class="alternative-link" style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:16px 24px;margin:30px 0;text-align:center;">
                <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:12px;">Si el botón no funciona, copiá y pegá esta URL en tu navegador:</div>
                <div style="color:#f59e0b;font-size:0.78rem;word-break:break-all;background:rgba(0,0,0,0.4);padding:12px;border-radius:6px;font-family:'Courier New',monospace;">{{ $url }}</div>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer" style="background:#1c1f26;padding:32px 40px;text-align:center;border-top:1px solid rgba(245,158,11,0.15);">
            <div style="color:#f59e0b;font-size:1.2rem;font-weight:700;letter-spacing:1px;margin-bottom:16px;">ServiME</div>
            <div style="color:#94a3b8;font-size:0.85rem;line-height:1.8;margin-bottom:8px;">
                © {{ date('Y') }} ServiME. Todos los derechos reservados.<br>
                Este email fue enviado automáticamente, por favor no respondas.
            </div>
            <div style="color:#94a3b8;font-size:0.85rem;margin-bottom:20px;">
                ServiME — Buenos Aires, Argentina
            </div>
            <div style="margin-top:4px;">
                <a href="https://servime.net" style="color:#f59e0b;text-decoration:none;margin:0 12px;font-size:0.9rem;">🌐 Sitio Web</a>
                <a href="mailto:info.servime@gmail.com" style="color:#f59e0b;text-decoration:none;margin:0 12px;font-size:0.9rem;">📧 Soporte</a>
            </div>
        </div>

    </div>
</body>
</html>
