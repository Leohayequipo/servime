<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-12">
                <div class="flex items-center">
                    @if($comercio->logo_url)
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                             alt="{{ $comercio->nombre }}" 
                             class="w-12 h-12 rounded-full object-cover mr-4">
                    @else
                        <img src="{{ asset('images/logo_empresa_default.png') }}" 
                             alt="Logo por defecto de {{ $comercio->nombre }}" 
                             class="w-12 h-12 rounded-full object-cover mr-4">
                    @endif
                    <div>
                        <h1 class="text-5xl font-bold text-text-primary mb-2">
                            📋 Menús de {{ $comercio->nombre }}
                        </h1>
                        <p class="text-xl text-text-secondary">Gestiona los menús digitales de tu negocio</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.comercios.index') }}" 
                           class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                            ← Volver
                        </a>
                    @else
                        <a href="{{ route('comercios.index') }}" 
                           class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                            ← Volver
                        </a>
                    @endif
                </div>
            </div>

            <!-- Success Messages -->
            @if(session('success'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-green-900/30 to-emerald-900/30 border border-green-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
                        <!-- Animated background -->
                        <div class="absolute inset-0 bg-gradient-to-r from-green-500/10 to-emerald-500/10 animate-pulse"></div>
                        
                        <div class="relative flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                                    <span class="text-white text-xl">✨</span>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-bold text-green-100 mb-1">¡Éxito!</h3>
                                <p class="text-green-200 font-medium">{{ session('success') }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Error Messages -->
            @if(session('error'))
                <div class="mb-8 animate-fade-in-down">
                    <div class="relative overflow-hidden bg-gradient-to-r from-red-900/30 to-rose-900/30 border border-red-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-sm">
                        <!-- Animated background -->
                        <div class="absolute inset-0 bg-gradient-to-r from-red-500/10 to-rose-500/10 animate-pulse"></div>
                        
                        <div class="relative flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center shadow-lg animate-shake">
                                    <span class="text-white text-xl">⚠️</span>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-bold text-red-100 mb-1">Error</h3>
                                <p class="text-red-200 font-medium">{{ session('error') }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-red-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-red-500 to-rose-500 rounded-full animate-progress-bar"></div>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-4 mb-8">
                <a href="{{ route('menus.create', $comercio) }}" 
                   class="btn-success inline-flex items-center">
                    <span class="mr-2">➕</span>
                    Crear Menú
                </a>
                <a href="{{ route('menu.public', $comercio) }}" 
                   target="_blank"
                   class="btn-primary inline-flex items-center">
                    <span class="mr-2">👁️</span>
                    Ver Página Pública
                </a>
                <!-- QR Dropdown -->
                <div class="relative group">
                    <button class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-text-primary font-semibold py-3 px-6 rounded-xl transition-all duration-300 shadow-lg inline-flex items-center">
                        <span class="mr-2">📱</span>
                        Generar QR
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <!-- Dropdown Menu -->
                    <div class="absolute left-0 mt-2 w-48 bg-secondary-dark border border-accent-gray rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="py-2">
                            <button onclick="generateQR()" 
                                    class="w-full text-left px-4 py-3 text-sm text-text-primary hover:bg-primary-dark transition-colors duration-200 flex items-center">
                                <span class="mr-3">📱</span>
                                QR Simple
                            </button>
                            <button onclick="generateQRWithLogo()" 
                                    class="w-full text-left px-4 py-3 text-sm text-text-primary hover:bg-primary-dark transition-colors duration-200 flex items-center">
                                <span class="mr-3">🎨</span>
                                QR con Logo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if($menus->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($menus as $menu)
                        <div class="metric-card group relative overflow-visible">
                            <!-- Label MENU arriba -->
                            <div class="absolute top-3 left-4 z-20">
                                <span class="bg-gradient-to-r from-accent-blue to-blue-600 text-white font-bold text-xs uppercase tracking-[0.15em] px-4 py-1.5 rounded-full shadow-lg border-2 border-white/20">
                                    MENU
                                </span>
                            </div>
                            
                            <!-- Imagen del menú -->
                            <div class="relative h-48 overflow-hidden rounded-t-xl">
                                @if($menu->archivo_original && !str_contains($menu->archivo_original, '.pdf'))
                                    <img src="{{ asset('storage/' . $menu->archivo_original) }}" 
                                         alt="Imagen del menú" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @elseif($comercio->logo_url)
                                    <img src="{{ asset('storage/' . $comercio->logo_url) }}" 
                                         alt="Logo de {{ $comercio->nombre }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <img src="{{ asset('images/logo_empresa_default.png') }}" 
                                         alt="Logo por defecto de {{ $comercio->nombre }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @endif
                                
                                <!-- Estado del procesamiento -->
                                <div class="absolute top-4 right-4">
                                    @if($menu->procesado)
                                        <div class="w-8 h-8 bg-accent-green/20 backdrop-blur-sm rounded-full flex items-center justify-center neon-glow-green">
                                            <span class="text-accent-green text-sm">✓</span>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 bg-yellow-500/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                            <span class="text-yellow-400 text-sm">⏳</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Contenido de la tarjeta -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-xl font-bold text-text-primary group-hover:text-accent-blue transition-colors duration-300">
                                            {{ $menu->nombre ?? 'Menú sin nombre' }}
                                        </h3>
                                        @if($menu->activo)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span>
                                                Activo
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($menu->descripcion)
                                        <p class="text-text-secondary text-sm line-clamp-2">{{ $menu->descripcion }}</p>
                                    @endif
                                </div>
                                
                                <!-- Información del menú -->
                                <div class="space-y-2 text-sm text-text-secondary mb-6">
                                    <div class="flex items-center justify-between">
                                        <span>Estado:</span>
                                        <span class="px-2 py-1 rounded-full text-xs {{ $menu->procesado ? 'bg-accent-green/20 text-accent-green' : 'bg-yellow-500/20 text-yellow-400' }}">
                                            {{ $menu->procesado ? 'Procesado' : 'Pendiente' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Actualizado:</span>
                                        <span class="text-accent-green/90 font-medium">{{ $menu->updated_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="space-y-3">
                                    @if(auth()->user()->isSuperAdmin())
                                        <a href="{{ route('admin.menus.edit', $menu) }}" 
                                           class="w-full btn-primary text-center block">
                                            <span class="mr-2">✏️</span>
                                            Editar Menú
                                            <span class="ml-2 group-hover:translate-x-1 transition-transform duration-300">→</span>
                                        </a>
                                    @else
                                        <a href="{{ route('menus.edit', [$comercio, $menu]) }}" 
                                           class="w-full btn-primary text-center block">
                                            <span class="mr-2">✏️</span>
                                            Editar Menú
                                            <span class="ml-2 group-hover:translate-x-1 transition-transform duration-300">→</span>
                                        </a>
                                    @endif
                                    
                                    @if($menu->procesado)
                                        <a href="{{ route('menus.export', [$comercio, $menu]) }}" 
                                           class="w-full bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm shadow-lg">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 3v18"/>
                                            </svg>
                                            Exportar a Excel
                                        </a>
                                    @endif
                                    
                                    <div class="space-y-2">
                                        @if(!$menu->activo)
                                            <form method="POST" action="{{ route('menus.activar', [$comercio, $menu]) }}" class="w-full">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm">
                                                    <span class="mr-1">⭐</span>
                                                    Activar Menú
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form method="POST" action="{{ route('menus.destroy', [$comercio, $menu]) }}" 
                                              onsubmit="event.preventDefault(); const form = this; showConfirmWithLoader('¿Estás seguro de que quieres eliminar este menú?', function(result) { if(result) { form.submit(); } }, 'Eliminando menú...');"
                                              class="w-full">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="w-full bg-secondary-dark hover:bg-red-500/20 hover:text-red-400 text-text-primary font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm border border-accent-gray">
                                                <span class="mr-1">🗑️</span>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-r from-accent-blue to-accent-green rounded-full flex items-center justify-center neon-glow">
                        <span class="text-6xl">📋</span>
                    </div>
                    <h3 class="text-3xl font-bold text-text-primary mb-4">No tienes menús aún</h3>
                    <p class="text-xl text-text-secondary mb-8">Crea tu primer menú digital para comenzar a ofrecer una experiencia interactiva a tus clientes</p>
                    <a href="{{ route('menus.create', $comercio) }}" 
                       class="btn-success inline-flex items-center text-lg px-8 py-4 rounded-xl">
                        <span class="mr-2">➕</span>
                        Crear mi primer menú
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal para QR -->
    <div id="qrModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-8 max-w-lg mx-4">
            <div class="text-center">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-text-primary">📱 Código QR</h3>
                    <button onclick="closeQR()" class="text-text-secondary hover:text-text-primary transition-colors">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>
                <div id="qrCode" class="mb-6"></div>
                <p class="text-text-secondary mb-6">Escanea este código para acceder al menú digital</p>
                <div class="flex space-x-3">
                    <button onclick="downloadQR()" 
                            class="flex-1 btn-primary">
                        📥 Descargar
                    </button>
                    <button onclick="closeQR()" 
                            class="flex-1 bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateQR() {
            const url = '{{ route("menu.public", $comercio) }}';
            const qrCodeDiv = document.getElementById('qrCode');
            const modal = document.getElementById('qrModal');
            
            // Limpiar contenido previo
            qrCodeDiv.innerHTML = '<div class="text-center p-4"><p>Generando QR...</p></div>';
            
            // Mostrar modal inmediatamente
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Generar QR usando API online con mejor configuración
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}&color=000000&bgcolor=ffffff&margin=20&ecc=H&format=png`;
            
            const img = document.createElement('img');
            img.src = qrUrl;
            img.alt = 'Código QR';
            img.className = 'mx-auto';
            img.style.maxWidth = '300px';
            img.style.height = 'auto';
            
            img.onload = function() {
                qrCodeDiv.innerHTML = '';
                qrCodeDiv.appendChild(img);
            };
            
            img.onerror = function() {
                qrCodeDiv.innerHTML = '<div class="text-red-500 text-center p-4"><p>Error generando QR</p><p class="text-sm">Verifica tu conexión a internet</p></div>';
            };
        }
        
        function closeQR() {
            const modal = document.getElementById('qrModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        function downloadQR() {
            const qrCodeDiv = document.getElementById('qrCode');
            const img = qrCodeDiv.querySelector('img');
            
            if (img) {
                // Descargar directamente la imagen desde la URL
                const url = '{{ route("menu.public", $comercio) }}';
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}&color=000000&bgcolor=ffffff&margin=20&ecc=H&format=png`;
                
                const link = document.createElement('a');
                link.download = 'qr-{{ $comercio->slug }}.png';
                link.href = qrUrl;
                link.target = '_blank';
                link.click();
            } else {
                alert('No se pudo generar el QR para descargar');
            }
        }
        
        // Cerrar modal al hacer click fuera
        document.getElementById('qrModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQR();
            }
        });

        // Nueva función para generar QR con Logo
        function generateQRWithLogo() {
            LoadingManager.showGlobalLoader('Generando QR con logo...');
            
            const url = '{{ route("menu.public", $comercio) }}';
            const comercioNombre = '{{ $comercio->nombre }}';
            const logoUrl = '{{ $comercio->logo_url ? asset("storage/" . $comercio->logo_url) : asset("images/logo_empresa_default.png") }}';
            
            // Configuración fija: Tarjeta 105x148mm a 300 DPI
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Dimensiones: 1240x1748px (105x148mm a 300 DPI)
            canvas.width = 1240;
            canvas.height = 1748;
            
            // Fondo blanco
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Cargar logo
            const logo = new Image();
            logo.crossOrigin = 'anonymous';
            logo.src = logoUrl;
            
            logo.onload = function() {
                // Dibujar logo centrado (80px de alto)
                const logoHeight = 160;
                const logoAspectRatio = logo.width / logo.height;
                const logoWidth = logoHeight * logoAspectRatio;
                const logoX = (canvas.width - logoWidth) / 2;
                const logoY = 100;
                
                ctx.drawImage(logo, logoX, logoY, logoWidth, logoHeight);
                
                // Dibujar nombre del comercio
                ctx.fillStyle = '#000000';
                ctx.font = 'bold 48px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(comercioNombre, canvas.width / 2, logoY + logoHeight + 80);
                
                // Generar QR
                const qrSize = 800;
                const qrY = logoY + logoHeight + 150;
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(url)}&color=000000&bgcolor=ffffff&margin=0&ecc=H&format=png`;
                
                const qrImg = new Image();
                qrImg.crossOrigin = 'anonymous';
                qrImg.src = qrUrl;
                
                qrImg.onload = function() {
                    // Dibujar QR centrado
                    const qrX = (canvas.width - qrSize) / 2;
                    ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);
                    
                    // Texto inferior
                    ctx.font = '32px Arial';
                    ctx.fillText('Escanea para ver nuestro', canvas.width / 2, qrY + qrSize + 80);
                    ctx.font = 'bold 36px Arial';
                    ctx.fillText('menú digital', canvas.width / 2, qrY + qrSize + 130);
                    
                    // Convertir canvas a imagen
                    const finalImage = canvas.toDataURL('image/png');
                    
                    // Mostrar en modal
                    const qrCodeDiv = document.getElementById('qrCode');
                    const modal = document.getElementById('qrModal');
                    
                    qrCodeDiv.innerHTML = `<img src="${finalImage}" alt="QR con Logo" class="mx-auto" style="max-width: 100%; height: auto; border-radius: 8px;">`;
                    
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    
                    LoadingManager.hideGlobalLoader();
                    
                    // Actualizar función de descarga
                    window.currentQRData = finalImage;
                };
                
                qrImg.onerror = function() {
                    LoadingManager.hideGlobalLoader();
                    alert('Error al generar el código QR. Verifica tu conexión a internet.');
                };
            };
            
            logo.onerror = function() {
                LoadingManager.hideGlobalLoader();
                alert('Error al cargar el logo del comercio.');
            };
        }
        
        // Actualizar función de descarga para soportar ambos tipos
        const originalDownloadQR = downloadQR;
        downloadQR = function() {
            if (window.currentQRData) {
                // Descargar QR con logo
                const link = document.createElement('a');
                link.download = 'qr-con-logo-{{ $comercio->slug }}.png';
                link.href = window.currentQRData;
                link.click();
                
                // Limpiar
                window.currentQRData = null;
            } else {
                // Descargar QR simple (función original)
                originalDownloadQR();
            }
        };
    </script>
</x-app-layout>
