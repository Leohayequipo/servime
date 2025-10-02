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
                    @endif
                    <div>
                        <h1 class="text-5xl font-bold text-text-primary mb-2">
                            📋 Menús de {{ $comercio->nombre }}
                        </h1>
                        <p class="text-xl text-text-secondary">Gestiona los menús digitales de tu negocio</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('comercios.index') }}" 
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        ← Volver
                    </a>
                    <a href="{{ route('comercios.edit', $comercio) }}" 
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        ✏️ Editar
                    </a>
                </div>
            </div>

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
                <button onclick="generateQR()" 
                        class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-text-primary font-semibold py-3 px-6 rounded-xl transition-all duration-300 shadow-lg inline-flex items-center">
                    <span class="mr-2">📱</span>
                    Generar QR
                </button>
            </div>

            @if($menus->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($menus as $menu)
                        <div class="metric-card group">
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
                                    <div class="w-full h-full bg-gradient-to-br from-accent-blue to-accent-green flex items-center justify-center">
                                        <span class="text-6xl">📋</span>
                                    </div>
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
                                    <h3 class="text-xl font-bold text-text-primary mb-2 group-hover:text-accent-blue transition-colors duration-300">
                                        {{ $menu->nombre ?? 'Menú sin nombre' }}
                                    </h3>
                                    
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
                                        <span>Creado:</span>
                                        <span>{{ $menu->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="space-y-3">
                                    <a href="{{ route('menus.edit', [$comercio, $menu]) }}" 
                                       class="w-full btn-primary text-center block">
                                        <span class="mr-2">✏️</span>
                                        Editar Menú
                                        <span class="ml-2 group-hover:translate-x-1 transition-transform duration-300">→</span>
                                    </a>
                                    
                                    <form method="POST" action="{{ route('menus.destroy', [$comercio, $menu]) }}" 
                                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar este menú?')"
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
    <div id="qrModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-8 max-w-md mx-4">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-text-primary mb-4">Código QR Generado</h3>
                <div id="qrCode" class="mb-6"></div>
                <p class="text-text-secondary mb-6">Escanea este código para acceder al menú digital</p>
                <div class="flex space-x-3">
                    <button onclick="downloadQR()" 
                            class="btn-primary">
                        📥 Descargar
                    </button>
                    <button onclick="closeQR()" 
                            class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de QR -->
    <div id="qrModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="glass-effect rounded-2xl p-8 max-w-sm w-full text-center">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold gradient-text">📱 Código QR</h3>
                <button onclick="closeQR()" class="text-secondary hover:text-primary transition-colors">
                    <span class="text-2xl">✕</span>
                </button>
            </div>
            
            <div class="mb-6">
                <div id="qrCode" class="flex justify-center mb-4"></div>
                <p class="text-sm text-secondary mb-2">{{ $comercio->nombre }}</p>
                <p class="text-xs text-secondary">Escanea para acceder al chatbot</p>
            </div>
            
            <div class="flex space-x-3">
                <button onclick="downloadQR()" class="flex-1 btn-primary py-2 px-4 rounded-lg">
                    📥 Descargar
                </button>
                <button onclick="closeQR()" class="flex-1 bg-secondary-dark hover:bg-accent-gray text-text-primary py-2 px-4 rounded-lg transition-all duration-300">
                    Cerrar
                </button>
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
    </script>
</x-app-layout>