<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Comercio') }}
            </h2>
            <a href="{{ route('comercios.index') }}" class="btn-secondary">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('comercios.update', $comercio) }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')
                
                <!-- 🧾 Información del Comercio -->
                <div class="glass-effect rounded-2xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-accent-blue rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white text-lg">🧾</span>
                        </div>
                        <h3 class="text-xl font-bold text-text-primary">Información del Comercio</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre del Comercio -->
                        <div class="md:col-span-2">
                            <x-input-label for="nombre" :value="__('Nombre del Comercio')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="nombre" class="input-field w-full" type="text" name="nombre" :value="old('nombre', $comercio->nombre)" required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <x-input-label for="descripcion" :value="__('Descripción')" class="text-text-primary font-medium mb-2" />
                            <textarea id="descripcion" name="descripcion" rows="3" class="input-field w-full resize-none">{{ old('descripcion', $comercio->descripcion) }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>

                        <!-- Dirección -->
                        <div>
                            <x-input-label for="direccion" :value="__('Dirección')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="direccion" class="input-field w-full" type="text" name="direccion" :value="old('direccion', $comercio->direccion)" />
                            <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                        </div>

                        <!-- Horario -->
                        <div>
                            <x-input-label for="horario" :value="__('Horario de Atención')" class="text-text-primary font-medium mb-2" />
                            <x-text-input id="horario" class="input-field w-full" type="text" name="horario" :value="old('horario', $comercio->horario)" placeholder="Ej: Lunes a Viernes 9:00 - 18:00" />
                            <x-input-error :messages="$errors->get('horario')" class="mt-2" />
                        </div>
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
                    
                    @if($comercio->logo_url)
                        <div class="mb-6">
                            <div class="flex items-center space-x-4 p-4 bg-secondary-dark rounded-xl">
                                <img src="{{ asset('storage/' . $comercio->logo_url) }}" alt="Logo actual" class="w-20 h-20 object-cover rounded-lg border border-accent-gray">
                                <div>
                                    <p class="text-sm text-text-secondary mb-2">Logo actual</p>
                                    <button type="button" onclick="confirmDeleteLogo()" class="btn-danger text-sm">
                                        🗑️ Eliminar Logo
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div>
                        <x-input-label for="logo" :value="__('Subir Nuevo Logo')" class="text-text-primary font-medium mb-2" />
                        <input type="file" id="logo" name="logo" accept="image/*" class="input-field w-full file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-blue file:text-white hover:file:bg-blue-600" />
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        <p class="mt-2 text-sm text-text-secondary">Formatos permitidos: JPEG, PNG, JPG, GIF. Tamaño máximo: 2MB</p>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-center space-x-4">
                    <a href="{{ route('comercios.index') }}" class="btn-secondary">
                        {{ __('Cancelar') }}
                    </a>
                    <x-primary-button>
                        {{ __('Actualizar Comercio') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar logo -->
    <div id="deleteLogoModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 w-96">
            <div class="glass-effect rounded-2xl p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-500/20 mb-4">
                        <span class="text-2xl">🗑️</span>
                    </div>
                    <h3 class="text-xl font-bold text-text-primary mb-2">¿Eliminar logo?</h3>
                    <div class="mb-6">
                        <p class="text-sm text-text-secondary">
                            ¿Estás seguro de que quieres eliminar el logo de este comercio? Esta acción no se puede deshacer.
                        </p>
                    </div>
                    <div class="flex justify-center space-x-4">
                        <button onclick="closeDeleteModal()" class="btn-secondary">
                            Cancelar
                        </button>
                        <button onclick="deleteLogo()" class="btn-danger">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDeleteLogo() {
            document.getElementById('deleteLogoModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteLogoModal').classList.add('hidden');
        }

        function deleteLogo() {
            // Crear un formulario oculto para eliminar el logo
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("comercios.update", $comercio) }}';
            
            // Agregar token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Agregar método PUT
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PUT';
            form.appendChild(methodField);
            
            // Agregar campo para eliminar logo
            const deleteLogoField = document.createElement('input');
            deleteLogoField.type = 'hidden';
            deleteLogoField.name = 'delete_logo';
            deleteLogoField.value = '1';
            form.appendChild(deleteLogoField);
            
            // Agregar todos los campos del formulario original
            const originalForm = document.querySelector('form');
            const inputs = originalForm.querySelectorAll('input:not([type="file"]), textarea');
            inputs.forEach(input => {
                if (input.name !== '_token' && input.name !== '_method') {
                    const newInput = document.createElement('input');
                    newInput.type = 'hidden';
                    newInput.name = input.name;
                    newInput.value = input.value;
                    form.appendChild(newInput);
                }
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-app-layout>