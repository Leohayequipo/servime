<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-accent-blue/10 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">
                        📋
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-text-primary">{{ $menu->nombre ?: 'Menú sin nombre' }}</h1>
                        <p class="text-text-secondary mt-0.5">{{ $menu->comercio->nombre }} · {{ $menu->comercio->user->name }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn-primary">Editar</a>
                    <a href="{{ route('admin.menus.index') }}" class="btn-secondary">← Menús</a>
                </div>
            </div>

            <!-- Flash -->
            <x-flash />

            <!-- Info del menú -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                <p class="text-text-secondary text-xs font-medium uppercase tracking-wide mb-5">Información</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Nombre</p>
                            <p class="text-text-primary font-medium">{{ $menu->nombre ?: 'Sin nombre' }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Comercio</p>
                            <p class="text-text-primary font-medium">{{ $menu->comercio->nombre }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Propietario</p>
                            <p class="text-text-primary font-medium">{{ $menu->comercio->user->name }}
                                <span class="text-text-secondary font-normal text-sm">· {{ $menu->comercio->user->email }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Formato</p>
                            <span class="px-2.5 py-1 text-xs rounded-full font-medium
                                {{ $menu->formato === 'pdf' ? 'bg-danger/20 text-danger border border-danger/30' : 'bg-accent-blue/10 text-accent-blue border border-accent-blue/20' }}">
                                {{ strtoupper($menu->formato) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Procesado</p>
                            <span class="px-2.5 py-1 text-xs rounded-full font-medium
                                {{ $menu->procesado ? 'bg-accent-green/20 text-accent-green border border-accent-green/30' : 'bg-warning/20 text-warning border border-warning/30' }}">
                                {{ $menu->procesado ? 'Sí' : 'Pendiente' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Activo</p>
                            <span class="px-2.5 py-1 text-xs rounded-full font-medium
                                {{ $menu->activo ? 'bg-accent-green/20 text-accent-green border border-accent-green/30' : 'bg-accent-gray/50 text-text-secondary border border-accent-gray' }}">
                                {{ $menu->activo ? 'Sí' : 'No' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Creado</p>
                            <p class="text-text-primary font-medium">{{ $menu->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $menu->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Última actualización</p>
                            <p class="text-text-primary font-medium">{{ $menu->updated_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $menu->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archivo -->
            @if($menu->archivo_original)
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                            <span>📄</span>
                        </div>
                        <h3 class="font-bold text-text-primary">Archivo del Menú</h3>
                    </div>

                    <div class="flex items-center justify-between bg-primary-dark border border-accent-gray/40 rounded-xl px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-accent-blue/10 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                                {{ $menu->formato === 'pdf' ? '📄' : '🍽️' }}
                            </div>
                            <div>
                                <p class="font-medium text-text-primary text-sm">{{ basename($menu->archivo_original) }}</p>
                                <p class="text-text-secondary text-xs">{{ strtoupper($menu->formato) }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <a href="{{ asset('storage/' . $menu->archivo_original) }}"
                               target="_blank"
                               class="px-3 py-1.5 bg-accent-blue/10 hover:bg-accent-blue/20 text-accent-blue border border-accent-blue/20 rounded-lg text-xs font-medium transition-colors">
                                {{ $menu->formato === 'pdf' ? 'Ver PDF' : 'Ver menú' }}
                            </a>
                            <a href="{{ asset('storage/' . $menu->archivo_original) }}"
                               download
                               class="px-3 py-1.5 bg-accent-gray/30 hover:bg-accent-gray/50 text-text-primary border border-accent-gray rounded-lg text-xs font-medium transition-colors">
                                Descargar
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if($menu->contexto_gpt)
                @php
                    $jsonContent = $menu->contexto_gpt;
                    $clean = trim($jsonContent);
                    if (strpos($clean, '```json') !== false) {
                        $clean = preg_replace('/```json\s*/', '', $clean);
                        $clean = preg_replace('/```\s*$/', '', $clean);
                    }
                    $contenido = json_decode(trim($clean), true);

                    // Separar promociones del resto
                    $promocionesCategoria = null;
                    $categorias = [];
                    if ($contenido && isset($contenido['categorias']) && is_array($contenido['categorias'])) {
                        foreach ($contenido['categorias'] as $cat) {
                            if (strtolower(trim($cat['nombre'] ?? '')) === 'promociones del día') {
                                $promocionesCategoria = $cat;
                            } else {
                                $categorias[] = $cat;
                            }
                        }
                    }
                @endphp

                <!-- Restaurante info -->
                @if(isset($contenido['restaurante']['nombre']) && $contenido['restaurante']['nombre'] !== 'No especificado')
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                        <h3 class="text-lg font-semibold text-text-primary mb-1">🏪 {{ $contenido['restaurante']['nombre'] }}</h3>
                        @if(isset($contenido['restaurante']['descripcion']) && !empty($contenido['restaurante']['descripcion']))
                            <p class="text-text-secondary text-sm">{{ $contenido['restaurante']['descripcion'] }}</p>
                        @endif
                    </div>
                @endif

                <!-- Promociones del Día -->
                @if($promocionesCategoria && isset($promocionesCategoria['platos']) && count($promocionesCategoria['platos']) > 0)
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 bg-accent-blue/20 border border-accent-blue/30 rounded-lg flex items-center justify-center">
                                <span>🔥</span>
                            </div>
                            <h3 class="font-bold text-text-primary">Promociones del Día</h3>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary border border-accent-gray font-medium">
                                {{ count($promocionesCategoria['platos']) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($promocionesCategoria['platos'] as $promo)
                                <div class="bg-primary-dark border border-accent-blue/20 rounded-xl p-4">
                                    <div class="flex items-start gap-3">
                                        @if(isset($promo['imagen']) && !empty($promo['imagen']))
                                            <img src="{{ asset($promo['imagen']) }}"
                                                 alt="{{ $promo['nombre'] ?? 'Promoción' }}"
                                                 class="w-16 h-16 object-cover rounded-lg border border-accent-gray flex-shrink-0">
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-2 mb-1">
                                                <h5 class="font-semibold text-text-primary text-sm leading-tight">{{ $promo['nombre'] ?? 'Sin nombre' }}</h5>
                                                @if(!empty($promo['precio']))
                                                    <span class="text-accent-blue font-bold text-sm flex-shrink-0">${{ $promo['precio'] }}</span>
                                                @endif
                                            </div>
                                            @if(!empty($promo['descripcion']))
                                                <p class="text-text-secondary text-xs leading-relaxed">{{ $promo['descripcion'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Categorías y Platos -->
                @if(count($categorias) > 0)
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                                <span>📋</span>
                            </div>
                            <h3 class="font-bold text-text-primary">Contenido Extraído</h3>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-accent-gray/50 text-text-secondary border border-accent-gray font-medium">
                                {{ count($categorias) }} categoría(s)
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($categorias as $categoria)
                                <div class="bg-primary-dark border border-accent-gray/50 rounded-xl p-5">
                                    <h4 class="text-base font-semibold text-text-primary mb-4 pb-3 border-b border-accent-gray/50">
                                        {{ $categoria['nombre'] ?? 'Sin nombre' }}
                                    </h4>
                                    <div class="space-y-4">
                                        @if(isset($categoria['platos']) && is_array($categoria['platos']))
                                            @foreach($categoria['platos'] as $plato)
                                                @php
                                                    $nombrePlato = $plato['nombre'] ?? 'Sin nombre';
                                                    $nombrePlato = str_replace(['-', '_'], ' ', $nombrePlato);
                                                    $nombrePlato = mb_convert_case($nombrePlato, MB_CASE_TITLE, 'UTF-8');
                                                    $nombrePlato = preg_replace('/\s+/', ' ', trim($nombrePlato));
                                                @endphp
                                                <div class="border-l-2 border-accent-blue/40 pl-3">
                                                    <div class="flex items-start gap-3 mb-1.5">
                                                        @if(isset($plato['imagen']) && !empty($plato['imagen']))
                                                            <img src="{{ asset($plato['imagen']) }}"
                                                                 alt="{{ $nombrePlato }}"
                                                                 class="w-12 h-12 object-cover rounded-lg border border-accent-gray flex-shrink-0">
                                                        @endif
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between gap-2">
                                                                <h5 class="font-semibold text-text-primary text-sm leading-tight capitalize">{{ $nombrePlato }}</h5>
                                                                @if(!empty($plato['precio']))
                                                                    <span class="text-accent-blue font-bold text-sm flex-shrink-0">${{ preg_replace('/[^0-9]/', '', $plato['precio']) }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if(!empty($plato['descripcion']))
                                                        <p class="text-text-secondary text-xs leading-relaxed mb-1.5">{{ $plato['descripcion'] }}</p>
                                                    @endif
                                                    @if(isset($plato['tags']) && is_array($plato['tags']) && count($plato['tags']) > 0)
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($plato['tags'] as $tag)
                                                                @if(!empty($tag))
                                                                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-accent-blue/15 text-accent-blue border border-accent-blue/30">{{ $tag }}</span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(isset($contenido['informacion_adicional']) && !empty($contenido['informacion_adicional']))
                            <div class="mt-6 bg-background border border-accent-gray/40 rounded-xl p-4">
                                <p class="text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Información Adicional</p>
                                <p class="text-text-secondary text-sm">{{ $contenido['informacion_adicional'] }}</p>
                            </div>
                        @endif
                    </div>
                @elseif(!$contenido)
                    <!-- JSON no parseable — fallback -->
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                                <span>🤖</span>
                            </div>
                            <h3 class="font-bold text-text-primary">Contenido Procesado por IA</h3>
                        </div>
                        <div class="bg-primary-dark border border-accent-gray/40 rounded-xl p-5">
                            <pre class="text-text-secondary text-sm whitespace-pre-wrap leading-relaxed font-sans">{{ $menu->contexto_gpt }}</pre>
                        </div>
                    </div>
                @endif

            @elseif($menu->procesado)
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center">
                            <span>📋</span>
                        </div>
                        <h3 class="font-bold text-text-primary">Contenido Extraído</h3>
                    </div>
                    <p class="text-text-secondary text-sm">El menú fue procesado pero no se extrajo contenido estructurado.</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

