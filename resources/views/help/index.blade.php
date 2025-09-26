<x-app-layout>
<!-- Main Content -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-accent-blue to-accent-green rounded-full mb-6 neon-glow">
            <span class="text-3xl">❓</span>
        </div>
        <h1 class="text-4xl font-bold gradient-text mb-4">Centro de Ayuda</h1>
        <p class="text-xl text-text-secondary">Todo lo que necesitas saber sobre ServiME</p>
    </div>

    <!-- System Info Cards -->
    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <!-- What is ServiME -->
        <div class="glass-effect p-8 rounded-2xl hover-lift">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center mr-4">
                    <span class="text-2xl">🤖</span>
                </div>
                <h2 class="text-2xl font-bold text-text-primary">¿Qué es ServiME?</h2>
            </div>
            <p class="text-text-secondary leading-relaxed mb-4">
                ServiME es una plataforma inteligente que revoluciona la experiencia gastronómica 
                mediante tecnología de inteligencia artificial. Permite a bares y restaurantes 
                crear menús digitales interactivos con chatbot integrado.
            </p>
            <ul class="space-y-2 text-text-secondary">
                <li class="flex items-center">
                    <span class="text-accent-green mr-2">✓</span>
                    Menús digitales con QR codes
                </li>
                <li class="flex items-center">
                    <span class="text-accent-green mr-2">✓</span>
                    Chatbot inteligente con IA
                </li>
                <li class="flex items-center">
                    <span class="text-accent-green mr-2">✓</span>
                    Personalización completa
                </li>
                <li class="flex items-center">
                    <span class="text-accent-green mr-2">✓</span>
                    Analytics y métricas
                </li>
            </ul>
        </div>

        <!-- Main Features -->
        <div class="glass-effect p-8 rounded-2xl hover-lift">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-500 rounded-lg flex items-center justify-center mr-4">
                    <span class="text-2xl">⚡</span>
                </div>
                <h2 class="text-2xl font-bold text-text-primary">Funcionalidades Principales</h2>
            </div>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3 mt-1">
                        <span class="text-sm">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-text-primary">Gestión de Comercios</h3>
                        <p class="text-sm text-text-secondary">Crea y administra múltiples establecimientos</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3 mt-1">
                        <span class="text-sm">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-text-primary">Procesamiento de Menús</h3>
                        <p class="text-sm text-text-secondary">Sube imágenes y obtén menús estructurados automáticamente</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3 mt-1">
                        <span class="text-sm">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-text-primary">Chatbot Inteligente</h3>
                        <p class="text-sm text-text-secondary">IA que responde preguntas sobre tu menú 24/7</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3 mt-1">
                        <span class="text-sm">4</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-text-primary">Personalización Total</h3>
                        <p class="text-sm text-text-secondary">Colores, mensajes y branding personalizado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How it Works -->
    <div class="glass-effect p-8 rounded-2xl mb-12">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold gradient-text mb-4">¿Cómo Funciona?</h2>
            <p class="text-text-secondary">Proceso simple en 4 pasos</p>
        </div>
        <div class="grid md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-4 neon-glow">
                    <span class="text-2xl">📝</span>
                </div>
                <h3 class="font-semibold text-text-primary mb-2">1. Crea tu Comercio</h3>
                <p class="text-sm text-text-secondary">Registra tu establecimiento con logo y datos básicos</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mx-auto mb-4 neon-glow">
                    <span class="text-2xl">📸</span>
                </div>
                <h3 class="font-semibold text-text-primary mb-2">2. Sube tu Menú</h3>
                <p class="text-sm text-text-secondary">Toma una foto de tu menú y la IA lo procesa automáticamente</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4 neon-glow">
                    <span class="text-2xl">🎨</span>
                </div>
                <h3 class="font-semibold text-text-primary mb-2">3. Personaliza</h3>
                <p class="text-sm text-text-secondary">Ajusta colores, mensajes y estilo de tu página</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center mx-auto mb-4 neon-glow">
                    <span class="text-2xl">📱</span>
                </div>
                <h3 class="font-semibold text-text-primary mb-2">4. Comparte QR</h3>
                <p class="text-sm text-text-secondary">Genera el código QR y compártelo con tus clientes</p>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="glass-effect p-8 rounded-2xl mb-12">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold gradient-text mb-4">Contacto y Soporte</h2>
            <p class="text-text-secondary">¿Necesitas ayuda? Estamos aquí para ti</p>
        </div>
        <div class="grid md:grid-cols-2 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-r from-accent-blue to-accent-green rounded-full flex items-center justify-center mx-auto mb-6 neon-glow">
                    <span class="text-3xl">👨‍💻</span>
                </div>
                <h3 class="text-xl font-bold text-text-primary mb-4">Desarrollador</h3>
                <p class="text-text-secondary mb-2"><strong>Leonardo Mannanice</strong></p>
                <p class="text-text-secondary mb-4">Desarrollador Full Stack</p>
                <div class="space-y-2">
                    <p class="flex items-center justify-center text-text-secondary">
                        <span class="mr-2">📞</span>
                        <a href="tel:+542257526706" class="hover:text-accent-blue transition-colors">+54 2257 526706</a>
                    </p>
                    <p class="flex items-center justify-center text-text-secondary">
                        <span class="mr-2">📧</span>
                        <a href="mailto:{{ config('mail.from.address', 'leonardo@servime.com') }}" class="hover:text-accent-blue transition-colors">
                            {{ config('mail.from.address', 'leonardo@servime.com') }}
                        </a>
                    </p>
                </div>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-6 neon-glow">
                    <span class="text-3xl">💬</span>
                </div>
                <h3 class="text-xl font-bold text-text-primary mb-4">Soporte Técnico</h3>
                <p class="text-text-secondary mb-4">Respuesta en menos de 24 horas</p>
                <div class="space-y-2">
                    <p class="text-text-secondary">Horario: Lunes a Viernes 9:00 - 18:00</p>
                    <p class="text-text-secondary">Sábados: 9:00 - 13:00</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Thank You Message -->
    <div class="glass-effect p-8 rounded-2xl text-center">
        <div class="w-20 h-20 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-6 neon-glow">
            <span class="text-3xl">🙏</span>
        </div>
        <h2 class="text-3xl font-bold gradient-text mb-4">¡Gracias por Elegir ServiME!</h2>
        <p class="text-lg text-text-secondary mb-6">
            Tu confianza en nuestra plataforma nos motiva a seguir mejorando 
            y ofrecerte la mejor experiencia posible.
        </p>
        <p class="text-text-secondary">
            <strong>Versión:</strong> 1.0.0 | 
            <strong>Última actualización:</strong> {{ date('d/m/Y') }}
        </p>
    </div>
</div>
</x-app-layout>