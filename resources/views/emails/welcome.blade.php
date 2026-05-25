<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido a ServiME!</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .logo {
            position: relative;
            z-index: 1;
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 10px;
        }
        
        .header-title {
            position: relative;
            z-index: 1;
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
        }
        
        .header-subtitle {
            position: relative;
            z-index: 1;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .welcome-message {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.7;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
        }
        
        .feature-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .feature-description {
            font-size: 14px;
            color: #718096;
            line-height: 1.5;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        
        .cta-title {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
        }
        
        .cta-description {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 24px;
        }
        
        .cta-button {
            display: inline-block;
            background: #ffffff;
            color: #f5576c;
            padding: 16px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .stats-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
        }
        
        .stats-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            text-align: center;
            margin-bottom: 24px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }
        
        .footer {
            background: #2d3748;
            padding: 30px;
            text-align: center;
        }
        
        .footer-logo {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 16px;
        }
        
        .footer-text {
            font-size: 14px;
            color: #a0aec0;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .social-links {
            margin: 20px 0;
        }
        
        .social-link {
            display: inline-block;
            margin: 0 10px;
            color: #a0aec0;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .social-link:hover {
            color: #667eea;
        }
        
        .footer-bottom {
            border-top: 1px solid #4a5568;
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .footer-bottom-text {
            font-size: 12px;
            color: #718096;
        }
        
        @media (max-width: 600px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🤖 ServiME</div>
            <h1 class="header-title">¡Bienvenido a la Revolución!</h1>
            <p class="header-subtitle">Tu asistente IA para menús digitales</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <h2 class="welcome-title">¡Hola {{ $user->name }}! 👋</h2>
            <p class="welcome-message">
                ¡Estamos emocionados de tenerte en ServiME! Has dado el primer paso hacia la transformación digital de tu negocio con inteligencia artificial.
            </p>
            
            <!-- Información de Acceso -->
            <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 16px; padding: 24px; margin: 30px 0;">
                <h3 style="color: #0369a1; margin-bottom: 16px; font-size: 18px; font-weight: 600; display: flex; align-items: center;">
                    <span style="margin-right: 8px;">🔐</span>
                    Tus Datos de Acceso
                </h3>
                <div style="background: #ffffff; border-radius: 12px; padding: 20px; border: 1px solid #bae6fd;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; color: #0c4a6e; font-weight: 600; margin-bottom: 4px; font-size: 14px;">📧 Email:</label>
                        <span style="color: #0369a1; font-weight: 500; font-size: 16px;">{{ $user->email }}</span>
                    </div>
                    @if(!empty($resetUrl))
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; color: #0c4a6e; font-weight: 600; margin-bottom: 8px; font-size: 14px;">🔑 Contraseña:</label>
                        <a href="{{ $resetUrl }}"
                           style="display: inline-block; background: #0ea5e9; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                            Establecer mi contraseña
                        </a>
                        <p style="color: #64748b; font-size: 12px; margin-top: 8px;">
                            Este link expira en 60 minutos. Si vence, usá "Olvidé mi contraseña" en el login.
                        </p>
                    </div>
                    @endif
                    <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 8px; margin-top: 16px;">
                        <p style="color: #92400e; font-size: 13px; margin: 0; font-weight: 500;">
                            💡 <strong>Importante:</strong> Podés cambiar tu contraseña en cualquier momento desde tu perfil.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Features Grid -->
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">📱</span>
                    <h3 class="feature-title">Chatbot Inteligente</h3>
                    <p class="feature-description">Asistente IA que responde preguntas sobre tu menú las 24/7</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">🎨</span>
                    <h3 class="feature-title">Personalización Total</h3>
                    <p class="feature-description">Colores, estilos y branding únicos para tu negocio</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">⚡</span>
                    <h3 class="feature-title">Configuración Rápida</h3>
                    <p class="feature-description">Sube tu menú y en minutos tienes todo funcionando</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">📊</span>
                    <h3 class="feature-title">Analytics Avanzados</h3>
                    <p class="feature-description">Métricas detalladas de interacciones con tus clientes</p>
                </div>
            </div>
            
            <!-- CTA Section -->
            <div class="cta-section">
                <h3 class="cta-title">¡Comencemos tu aventura digital!</h3>
                <p class="cta-description">
                    Crea tu primer comercio y sube tu menú para comenzar a recibir clientes inteligentes
                </p>
                <a href="{{ config('app.url') }}/dashboard" class="cta-button">
                    🚀 Ir al Dashboard
                </a>
            </div>
            
            <!-- Stats Section -->
            <div class="stats-section">
                <h3 class="stats-title">¿Por qué elegir ServiME?</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">+95%</div>
                        <div class="stat-label">Satisfacción de Clientes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Disponibilidad</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">5 min</div>
                        <div class="stat-label">Configuración</div>
                    </div>
                </div>
            </div>
            
            <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 30px 0;">
                <h4 style="color: #0369a1; margin-bottom: 10px; font-size: 16px;">💡 Tip del Día</h4>
                <p style="color: #0c4a6e; font-size: 14px; margin: 0;">
                    <strong>Personaliza tu chatbot:</strong> Cambia el nombre y mensaje de bienvenida para que refleje la personalidad de tu negocio. Esto hará que tus clientes se sientan más conectados contigo.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">🤖 ServiME</div>
            <p class="footer-text">
                Gracias por unirte a nuestra comunidad de emprendedores digitales. 
                Si tienes alguna pregunta, no dudes en contactarnos.
            </p>
            
            <div class="social-links">
                <a href="mailto:{{ env('SUPPORT_EMAIL', 'info.servime@gmail.com') }}" class="social-link">📧 Email</a>
                <a href="https://wa.me/{{ env('SUPPORT_WHATSAPP', '5492257526706') }}" class="social-link">💬 WhatsApp</a>
                <a href="{{ env('SUPPORT_WEB', config('app.url')) }}" class="social-link">🌐 Web</a>
            </div>
            
            <div class="footer-bottom">
                <p class="footer-bottom-text">
                    Este email fue enviado a {{ $user->email }} porque te registraste en ServiME.<br>
                    Si no deseas recibir estos emails, puedes <a href="#" style="color: #667eea;">cancelar la suscripción</a>.
                </p>
                <p class="footer-bottom-text" style="margin-top: 10px;">
                    © {{ date('Y') }} ServiME. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

