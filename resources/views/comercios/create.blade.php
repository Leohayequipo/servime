<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-text-primary leading-tight">
                    {{ __('Crear Comercio') }}
                </h2>
                <p class="text-sm text-text-secondary mt-1">Crear un nuevo comercio para gestionar menús digitales</p>
            </div>
            {{-- El cancelar está al pie, junto a «Crear comercio». Acá había otro, con un
                 🔄 que no significa cancelar y un hover del mismo color que el fondo. --}}
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <form method="POST" action="{{ route('comercios.store') }}" enctype="multipart/form-data" class="space-y-8" id="comercio-form">
        @csrf
                
                <!-- 🧾 Información del Comercio -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🧾</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información del Comercio</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if(auth()->user()->isSuperAdmin() && isset($users))
                            <!-- Selección de Usuario -->
                            <div class="md:col-span-2">
                                <x-input-label for="user_id" :value="__('Asignar a Usuario')" class="text-text-primary font-medium mb-2" />
                                <select id="user_id" name="user_id" class="input-field w-full" required>
                                    <option value="">Seleccionar usuario...</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ (old('user_id', $forUserId ?? '') == $u->id) ? 'selected' : '' }}>
                                            {{ $u->name }} ({{ $u->email }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                            </div>

                            <!-- Selección de Plan -->
                            <div class="md:col-span-2">
                                <x-input-label for="plan_id" :value="__('Plan')" class="text-text-primary font-medium mb-2" />
                                <select id="plan_id" name="plan_id" class="input-field w-full">
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ (old('plan_id') == $plan->id || $plan->key === 'pro') ? 'selected' : '' }}>
                                            {{ $plan->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('plan_id')" class="mt-2" />
                            </div>
                        @endif

                        {{-- Los mismos campos que en editar: un solo archivo. --}}
                        <x-comercios.datos-basicos />
                    </div>
                </div>

                <!-- 🖼️ Logo -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-green rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🖼️</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Logo del Comercio</h3>
                    </div>
                    
                    <x-comercios.logo />
                </div>

                {{-- Al pie del formulario y no flotante: el alta entra en una pantalla, y
                     un botón fijo abajo a la derecha tapaba los últimos campos en vez de
                     ayudar. Estilo por `.btn-primary`, no escrito a mano.

                     Y dice «Crear comercio»: acá todavía no hay ningún cambio que guardar
                     —el texto venía copiado de la pantalla de edición—. --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('comercios.index') }}" class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        Crear comercio
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
