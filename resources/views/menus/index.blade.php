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
                    {{-- Misma lista para todos: `/comercios` muestra los del usuario, y al
                         super admin le muestra todos. Antes había una lista aparte en
                         `/admin/comercios` que repetía lo mismo con menos acciones. --}}
                    <a href="{{ route('comercios.index') }}"
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                        ← Volver
                    </a>
                </div>
            </div>

            <x-flash />

            {{-- Lo que falta configurar. Se esconde solo cuando está todo. --}}
            <x-comercios.pendientes :comercio="$comercio" />

            <!-- Action Buttons -->
            {{-- Una sola acción con fondo lleno y el resto en secundario.
                 Antes eran cuatro bloques de color en fila —verde, terracota, terracota,
                 blanco— y no se entendía cuál era la acción principal: todos gritaban lo
                 mismo. Crear el menú es lo que se viene a hacer acá; ver la página y sacar
                 el QR son consultas.

                 El verde de `btn-success` además no era de marca: es el semántico de
                 éxito, y usarlo como color de acción le saca el significado (ver la skill
                 `identidad`). --}}
            <div class="flex flex-wrap gap-4 mb-8">
                <a href="{{ route('menus.create', $comercio) }}"
                   class="btn-primary inline-flex items-center">
                    <span class="mr-2">➕</span>
                    Crear Menú
                </a>
                <a href="{{ route('menu.public', $comercio) }}"
                   target="_blank"
                   class="btn-secondary inline-flex items-center">
                    <span class="mr-2">👁️</span>
                    Ver Página Pública
                </a>
                {{-- QR Dropdown. Era el único escrito a mano, y le había quedado tinta
                     sobre terracota: 2,41:1, la mitad del mínimo legible. --}}
                <div class="relative group">
                    <button class="btn-secondary">
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

                {{-- El QR de arriba es el del local (vidriera, flyer, redes) y se queda como
                     está. Éste lleva a los QR por mesa, que son otra cosa. --}}
                @if($comercio->tieneFeature('mesas') || auth()->user()->isSuperAdmin())
                    <a href="{{ route('mesas.index', $comercio) }}"
                       class="btn-secondary">
                        <span class="mr-2">🪑</span>
                        Mesas y QR
                    </a>
                @endif
            </div>

            @if($menus->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($menus as $menu)
                        {{-- "En uso" = la que ven los clientes (MenuController::index calcula
                             cuál con menuVigente). Antes todas las tarjetas decían "MENU" y la
                             activa sólo tenía una pastillita: no se entendía cuál se usaba. --}}
                        @php $enUso = $menu->id === $vigenteId; @endphp
                        <div class="metric-card group relative overflow-visible {{ $enUso ? 'ring-2 ring-accent-green' : '' }}">
                            <div class="absolute top-3 left-4 z-20">
                                @if($enUso)
                                    <span class="inline-flex items-center gap-1.5 bg-accent-green text-white font-bold text-xs uppercase tracking-[0.15em] px-4 py-1.5 rounded-full shadow-lg">
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                        En uso
                                    </span>
                                @else
                                    <span class="bg-white text-text-secondary font-bold text-xs uppercase tracking-[0.15em] px-4 py-1.5 rounded-full shadow border border-accent-gray">
                                        Sin usar
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Imagen del menú.

                                 Son dos cosas distintas y no se muestran igual: la FOTO de la
                                 carta llena la tarjeta (`object-cover`, recortar está bien), y
                                 el LOGO —que es lo que se ve cuando el menú es un PDF— va
                                 entero y centrado. Con `object-cover` el logo se estiraba al
                                 ancho de la tarjeta y quedaba cortado por la mitad. --}}
                            @php
                                $esFotoDelMenu = $menu->archivo_original && ! str_contains($menu->archivo_original, '.pdf');
                                $imagenCard = $esFotoDelMenu
                                    ? asset('storage/'.$menu->archivo_original)
                                    : ($comercio->logo_url
                                        ? asset('storage/'.$comercio->logo_url)
                                        : asset('images/logo_empresa_default.png'));
                            @endphp
                            <div class="relative h-48 overflow-hidden rounded-t-xl bg-secondary-dark {{ $enUso ? '' : 'opacity-60' }}">
                                @if($esFotoDelMenu)
                                    <img src="{{ $imagenCard }}"
                                         alt="Imagen del menú"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    {{-- Fondo: la misma imagen ampliada y desenfocada. Resuelve que
                                         "el logo" del comercio a veces sea un logo cuadrado y a veces
                                         una foto del local: en los dos casos la de adelante se ve
                                         entera y el hueco lo rellena ella misma, sin franjas blancas. --}}
                                    <img src="{{ $imagenCard }}" alt="" aria-hidden="true"
                                         class="absolute inset-0 w-full h-full object-cover scale-125 blur-xl opacity-50">
                                    <img src="{{ $imagenCard }}"
                                         alt="Logo de {{ $comercio->nombre }}"
                                         class="relative mx-auto h-full w-auto max-w-full object-contain py-3 group-hover:scale-105 transition-transform duration-500">
                                @endif

                                <!-- Estado del procesamiento. Con la carta lista no se muestra
                                     nada: el ✓ verde se leía como "esta es la elegida", y lo
                                     tenían todas. "Listo" sigue abajo, en Estado. -->
                                <div class="absolute top-4 right-4">
                                    @if($menu->estado === \App\Models\Menu::ESTADO_ERROR)
                                        <div class="w-8 h-8 bg-danger/20 backdrop-blur-sm rounded-full flex items-center justify-center" title="Error al procesar">
                                            <span class="text-danger text-sm">⚠️</span>
                                        </div>
                                    @elseif($menu->estado !== \App\Models\Menu::ESTADO_LISTO)
                                        <div class="w-8 h-8 bg-warning/20 backdrop-blur-sm rounded-full flex items-center justify-center" title="Procesando">
                                            <span class="text-warning text-sm">⏳</span>
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
                                    </div>

                                    @if($enUso)
                                        <p class="mb-3 px-3 py-2 rounded-lg bg-accent-green/10 text-accent-green text-sm font-medium">
                                            ✓ Es la carta que ven tus clientes al escanear el QR.
                                            @unless($menu->activo)
                                                {{-- menuVigente: sin ninguna activa, se muestra la última procesada --}}
                                                <span class="block mt-1 font-normal text-text-secondary">No hay ninguna activada, así que se muestra la última que cargaste.</span>
                                            @endunless
                                        </p>
                                    @else
                                        <p class="mb-3 text-sm text-text-secondary">Tus clientes no la ven.</p>
                                    @endif
                                    
                                    @if($menu->descripcion)
                                        <p class="text-text-secondary text-sm line-clamp-2">{{ $menu->descripcion }}</p>
                                    @endif
                                </div>
                                
                                <!-- Información del menú -->
                                <div class="space-y-2 text-sm text-text-secondary mb-6">
                                    <div class="flex items-center justify-between">
                                        <span>Estado:</span>
                                        @if($menu->estado === \App\Models\Menu::ESTADO_LISTO)
                                            <span class="px-2 py-1 rounded-full text-xs bg-accent-green/20 text-accent-green">Listo</span>
                                        @elseif($menu->estado === \App\Models\Menu::ESTADO_ERROR)
                                            {{-- Ya no hay que volver a subir nada: el archivo quedó
                                                 guardado y el editor tiene el botón de reintentar. --}}
                                            <span class="px-2 py-1 rounded-full text-xs bg-danger/20 text-danger">Error — abrilo para reintentar</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs bg-warning/20 text-warning">Procesando…</span>
                                        @endif
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
                                           class="w-full bg-accent-green hover:brightness-95 text-white font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm shadow-lg">
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
                                                        class="w-full bg-accent-green hover:brightness-95 text-white font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm">
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
                                                    class="w-full bg-secondary-dark hover:bg-danger/20 hover:text-danger text-text-primary font-medium py-2 px-3 rounded-lg transition-all duration-300 flex items-center justify-center text-sm border border-accent-gray">
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
                    <div class="w-32 h-32 mx-auto mb-8 bg-accent-blue hover:brightness-95 rounded-full flex items-center justify-center neon-glow">
                        <span class="text-6xl">📋</span>
                    </div>
                    <h3 class="text-3xl font-bold text-text-primary mb-4">No tienes menús aún</h3>
                    <p class="text-xl text-text-secondary mb-8">Crea tu primer menú digital para comenzar a ofrecer una experiencia interactiva a tus clientes</p>
                    {{-- Misma acción que el botón de arriba, así que el mismo color: en
                         verde quedaba distinta de sí misma según hubiera menús o no. --}}
                    <a href="{{ route('menus.create', $comercio) }}"
                       class="btn-primary inline-flex items-center text-lg px-8 py-4 rounded-xl">
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
                qrCodeDiv.innerHTML = '<div class="text-danger text-center p-4"><p>Error generando QR</p><p class="text-sm">Verifica tu conexión a internet</p></div>';
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
