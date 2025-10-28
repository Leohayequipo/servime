<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ServiME</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            color: #ffffff;
            line-height: 1.6;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(26, 26, 26, 0.9);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #00d4ff 0%, #00ff88 100%);
            padding: 50px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #000;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            margin-bottom: 10px;
            text-align: center;
            mso-text-align: center;
            text-align: center !important;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #000;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 60px 50px;
        }

        .greeting {
            font-size: 1.5rem;
            color: #00d4ff;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .message {
            font-size: 1.1rem;
            color: #b0b0b0;
            margin-bottom: 35px;
            line-height: 1.8;
            padding: 0 20px;
        }

        .button-container {
            text-align: center;
            margin: 50px 0;
        }

        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #00d4ff 0%, #00ff88 100%);
            color: #000;
            text-decoration: none;
            padding: 18px 45px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.4);
        }

        .reset-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .reset-button:hover::before {
            left: 100%;
        }

        .info-box {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 10px;
            padding: 30px 25px;
            margin: 40px 0;
            text-align: center;
        }

        .info-box .icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .info-box .text {
            color: #00d4ff;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 10px;
            padding: 30px 25px;
            margin: 40px 0;
            text-align: center;
        }

        .warning .icon {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .warning .text {
            color: #ffc107;
            font-weight: 500;
        }

        .footer {
            background: rgba(42, 42, 42, 0.8);
            padding: 40px 30px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer .logo {
            font-size: 1.5rem;
            color: #00d4ff;
            margin-bottom: 15px;
        }

        .footer .text {
            color: #b0b0b0;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .footer .social {
            margin-top: 25px;
        }

        .footer .social a {
            color: #00d4ff;
            text-decoration: none;
            margin: 0 15px;
            font-size: 1.2rem;
        }

        .footer .social a:hover {
            color: #00ff88;
        }

        .alternative-link {
            background: rgba(42, 42, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 30px 25px;
            margin: 40px 0;
            text-align: center;
        }

        .alternative-link .text {
            color: #b0b0b0;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .alternative-link .url {
            color: #00d4ff;
            font-size: 0.8rem;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .reset-button {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo" align="center">🚀 ServiME</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">¡Hola! 👋</div>
            
            <div class="message">
                Recibiste este email porque solicitaste recuperar tu contraseña en <strong>ServiME</strong>.
                <br><br>
                Si fuiste tú quien hizo esta solicitud, haz clic en el botón de abajo para crear una nueva contraseña.
            </div>

            <!-- Reset Button -->
            <div class="button-container">
                <a href="{{ $url }}" class="reset-button">
                    🔄 Recuperar Contraseña
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <div class="icon">⏰</div>
                <div class="text">Este enlace expirará en 60 minutos</div>
            </div>

            <!-- Warning -->
            <div class="warning">
                <div class="icon">⚠️</div>
                <div class="text">
                    Si no solicitaste este cambio, puedes ignorar este email de forma segura.
                </div>
            </div>

            <!-- Alternative Link -->
            <div class="alternative-link">
                <div class="text">Si tienes problemas con el botón, copia y pega esta URL en tu navegador:</div>
                <div class="url">{{ $url }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="logo">ServiME</div>
            <div class="text">
                © {{ date('Y') }} ServiME. Todos los derechos reservados.<br>
                Este email fue enviado automáticamente, por favor no respondas.
            </div>
            <div class="social">
                <a href="https://servime.net">🌐 Sitio Web</a>
                <a href="mailto:soporte@servime.net">📧 Soporte</a>
            </div>
        </div>
    </div>
</body>
</html>
