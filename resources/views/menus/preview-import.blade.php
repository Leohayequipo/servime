<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-text-primary mb-2">
                        📊 Vista Previa de Importación
                    </h1>
                    <p class="text-xl text-text-secondary">{{ $comercio->nombre }}</p>
                </div>
                <a href="{{ route('menus.index', $comercio) }}" 
                   class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                    ← Cancelar
                </a>
            </div>

            <!-- Resumen -->
            <div class="metric-card mb-6">
                <h2 class="text-2xl font-bold text-text-primary mb-4">Resumen de la Importación</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-accent-blue/10 border border-accent-blue/30 rounded-lg p-4">
                        <div class="text-3xl font-bold text-accent-blue">{{ count($data['categorias'] ?? []) }}</div>
                        <div class="text-sm text-text-secondary mt-1">Categorías</div>
                    </div>
                    <div class="bg-accent-green/10 border border-accent-green/30 rounded-lg p-4">
                        @php
                            $totalPlatos = 0;
                            foreach ($data['categorias'] ?? [] as $categoria) {
                                $totalPlatos += count($categoria['platos'] ?? []);
                            }
                        @endphp
                        <div class="text-3xl font-bold text-accent-green">{{ $totalPlatos }}</div>
                        <div class="text-sm text-text-secondary mt-1">Platos</div>
                    </div>
                    <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4">
                        <div class="text-3xl font-bold text-purple-500">
                            @php
                                $conImagen = 0;
                                foreach ($data['categorias'] ?? [] as $categoria) {
                                    foreach ($categoria['platos'] ?? [] as $plato) {
                                        if (!empty($plato['imagen'])) $conImagen++;
                                    }
                                }
                            @endphp
                            {{ $conImagen }}
                        </div>
                        <div class="text-sm text-text-secondary mt-1">Con Imagen</div>
                    </div>
                </div>
            </div>

            <!-- Vista Previa de Datos -->
            <div class="metric-card mb-6">
                <h2 class="text-2xl font-bold text-text-primary mb-4">Vista Previa del Menú</h2>
                
                @if(empty($data['categorias']))
                    <div class="text-center py-8 text-text-secondary">
                        <p>No se encontraron datos en el archivo Excel.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($data['categorias'] as $categoriaIndex => $categoria)
                            <div class="bg-secondary-dark rounded-lg p-6">
                                <h4 class="text-lg font-semibold text-text-primary mb-4 border-b border-accent-gray pb-3">
                                    {{ $categoria['nombre'] ?? 'Sin nombre' }}
                                    <span class="text-sm font-normal text-text-secondary ml-2">
                                        ({{ count($categoria['platos'] ?? []) }} platos)
                                    </span>
                                </h4>
                                
                                <div class="space-y-3">
                                    @foreach($categoria['platos'] ?? [] as $platoIndex => $plato)
                                        @php
                                            $nombrePlato = $plato['nombre'] ?? 'Sin nombre';
                                            $nombrePlato = str_replace(['-', '_'], ' ', $nombrePlato);
                                            $nombrePlato = mb_convert_case($nombrePlato, MB_CASE_TITLE, 'UTF-8');
                                            $nombrePlato = preg_replace('/\s+/', ' ', trim($nombrePlato));
                                        @endphp
                                        <div class="group plato-item bg-primary-dark border-l-4 border-l-accent-blue border-r border-t border-b border-accent-gray/50 rounded-r-lg p-4 mb-3 hover:bg-primary-dark/90 hover:shadow-xl hover:border-accent-blue/60 transition-all duration-300 shadow-lg">
                                            <!-- Nombre del producto -->
                                            <div class="flex items-center gap-2 mb-3">
                                                <h5 class="text-white font-bold text-lg leading-tight capitalize whitespace-normal flex-1">
                                                    {{ $nombrePlato }}
                                                </h5>
                                            </div>
                                            
                                            <!-- Foto y Precio (misma línea) -->
                                            <div class="flex flex-row items-center gap-4 mb-4 pb-3 border-b border-accent-gray/40">
                                                <!-- Imagen -->
                                                <div class="flex-shrink-0">
                                                    @if(!empty($plato['imagen']))
                                                        <div class="relative group/image">
                                                            <div class="w-20 h-20 rounded-lg overflow-hidden border-2 border-accent-blue/30 shadow-md bg-secondary-dark">
                                                                <img src="{{ $plato['imagen'] }}" 
                                                                     alt="{{ $nombrePlato }}"
                                                                     class="w-full h-full object-cover"
                                                                     onerror="this.style.display='none'">
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="w-20 h-20 rounded-lg border-2 border-dashed border-accent-blue/40 bg-secondary-dark/80 flex flex-col items-center justify-center text-text-secondary">
                                                            <svg class="w-5 h-5 mb-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            <span class="text-[9px] font-semibold text-accent-blue">Sin imagen</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <!-- Precio (alineado a la derecha) -->
                                                <div class="flex items-center gap-2 flex-shrink-0 ml-auto">
                                                    @if(!empty($plato['precio']))
                                                        <div class="flex items-center bg-secondary-dark border border-accent-blue/50 rounded-lg overflow-hidden shadow-md">
                                                            <span class="text-accent-blue font-bold text-sm px-2.5 py-1.5 border-r border-accent-blue/40 bg-accent-blue/10">$</span>
                                                            <span class="text-accent-blue font-bold text-base px-2.5 py-1.5">
                                                                {{ preg_replace('/[^0-9]/', '', $plato['precio']) }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-accent-gray text-sm">Sin precio</span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Descripción y Tags -->
                                            <div class="mt-4 space-y-3">
                                                @if(!empty($plato['descripcion']))
                                                    <p class="text-text-secondary text-sm leading-relaxed w-full whitespace-normal">
                                                        {{ $plato['descripcion'] }}
                                                    </p>
                                                @endif
                                                
                                                @if(!empty($plato['tags']) && is_array($plato['tags']))
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach($plato['tags'] as $tag)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-orange-500/20 text-orange-300 border border-orange-500/40 hover:bg-orange-500/30 transition-colors">
                                                                {{ trim($tag) }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Formulario oculto para el submit -->
            <form id="importForm" method="POST" action="{{ route('menus.import', $comercio) }}" style="display: none;">
                @csrf
                <input type="hidden" name="session_key" value="{{ $sessionKey }}">
                @if($menuEditando)
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="menu_id" value="{{ $menuEditando->id }}">
                @else
                    <input type="hidden" name="accion" value="{{ $menusExistentes->isNotEmpty() ? 'actualizar' : 'crear' }}" id="accionInput">
                    <input type="hidden" name="menu_id" value="" id="menuIdInput">
                    <input type="hidden" name="nombre" value="Menú Importado {{ date('d/m/Y') }}" id="nombreInput">
                @endif
            </form>

            <!-- Botón flotante a la derecha -->
            <button onclick="showConfirmModal()" 
                    class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-medium py-4 px-8 rounded-full transition-all duration-300 shadow-2xl hover:shadow-green-500/50 hover:scale-110 flex items-center gap-3">
                <span class="text-2xl">📥</span>
                <span class="text-lg">Importar</span>
            </button>

            <!-- Modal de confirmación -->
            <div id="confirmModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-background rounded-2xl shadow-2xl max-w-md w-full border border-accent-gray">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-text-primary mb-4">⚠️ Confirmar Importación</h3>
                        
                        @if($menuEditando)
                            <div class="mb-6 p-4 bg-accent-blue/10 border border-accent-blue/30 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <span class="text-accent-blue text-xl mr-2">ℹ️</span>
                                    <div class="font-semibold text-text-primary">Actualizando menú: "{{ $menuEditando->nombre }}"</div>
                                </div>
                                <p class="text-sm text-text-secondary mt-2">
                                    <strong class="text-red-400">El contenido de este menú será REEMPLAZADO COMPLETAMENTE</strong> con los datos del Excel.
                                </p>
                            </div>
                        @else
                            <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <span class="text-yellow-500 text-xl mr-2">⚠️</span>
                                    <div class="font-semibold text-text-primary">Atención</div>
                                </div>
                                <p class="text-sm text-text-secondary mt-2">
                                    @if($menusExistentes->isNotEmpty())
                                        <strong class="text-red-400">Se actualizará un menú existente y su contenido será REEMPLAZADO COMPLETAMENTE.</strong>
                                    @else
                                        Se creará un nuevo menú con el contenido importado.
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                            <p class="text-sm text-text-primary">
                                <strong class="text-red-400">⚠️ Todos los datos actuales del menú serán eliminados y reemplazados por los datos del Excel.</strong>
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <button onclick="closeConfirmModal()" 
                                    class="flex-1 bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-accent-gray">
                                Cancelar
                            </button>
                            <button onclick="confirmImport()" 
                                    class="flex-1 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-medium py-3 px-6 rounded-xl transition-all duration-300 shadow-lg flex items-center justify-center gap-2">
                                <span class="text-xl">📥</span>
                                <span>Importar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showConfirmModal() {
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function confirmImport() {
            @if(!$menuEditando && $menusExistentes->isNotEmpty())
                // Si hay menús existentes, usar el primero por defecto
                const accionInput = document.getElementById('accionInput');
                const menuIdInput = document.getElementById('menuIdInput');
                
                if (accionInput.value === 'actualizar') {
                    // Obtener el primer menú disponible
                    const firstMenu = @json($menusExistentes->first());
                    if (firstMenu) {
                        menuIdInput.value = firstMenu.id;
                    } else {
                        accionInput.value = 'crear';
                    }
                }
            @endif
            
            // Enviar el formulario
            document.getElementById('importForm').submit();
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeConfirmModal();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeConfirmModal();
            }
        });
    </script>
</x-app-layout>

