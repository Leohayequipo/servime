<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Editar Usuario</h1>
                    <p class="text-text-secondary mt-1">{{ $user->name }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                        ← Usuarios
                    </a>
                </div>
            </div>

            <!-- Flash -->
            @if(session('success'))
                <div class="mb-8">
                    <div class="bg-green-900/20 border border-green-500/30 rounded-2xl p-5 flex items-center gap-4">
                        <div class="w-9 h-9 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">✓</div>
                        <p class="text-green-200 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- Credenciales iniciales, solo al crear el usuario.
                 Se muestran UNA vez: la contraseña se guarda hasheada, así que si se
                 cierra esta pantalla no hay forma de volver a verla, solo de generar otra.
                 Va acá y no en users/show porque showUser() redirige a esta vista. --}}
            @if(session('credenciales'))
                @php $cred = session('credenciales'); @endphp
                <div class="mb-8 bg-amber-900/20 border border-amber-500/40 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <x-icono name="alert" class="w-5 h-5 flex-none mt-0.5 text-amber-400" />
                        <div class="min-w-0 flex-1">
                            <h2 class="text-amber-200 font-semibold">Credenciales de acceso</h2>
                            <p class="text-amber-200/80 text-sm mt-0.5">
                                Anotalas y pasáselas ahora: es la única vez que se muestra la contraseña.
                            </p>

                            <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                                @foreach(['Usuario' => $cred['usuario'], 'Email' => $cred['email'], 'Contraseña' => $cred['password']] as $etiqueta => $valor)
                                    <div class="bg-black/25 border border-amber-500/25 rounded-xl px-3 py-2">
                                        <dt class="text-[11px] uppercase tracking-wide text-amber-200/60">{{ $etiqueta }}</dt>
                                        <dd class="text-sm font-mono text-amber-100 break-all select-all">{{ $valor }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <p class="text-amber-200/70 text-xs mt-3">
                                Puede entrar con el usuario o con el email. También le llegó un mail para elegir su propia contraseña.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Información Personal -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span>👤</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-primary">Información Personal</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-text-secondary mb-2">Nombre Completo</label>
                            <x-text-input id="name" class="input-field w-full" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-text-secondary mb-2">Email</label>
                            <x-text-input id="email" class="input-field w-full" type="email" name="email" value="{{ old('email', $user->email) }}" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-text-secondary mb-2">WhatsApp</label>
                            <x-text-input id="whatsapp" class="input-field w-full" type="text" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" placeholder="+54 9 11 1234-5678" />
                            <x-input-error :messages="$errors->get('whatsapp')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Rol -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 bg-accent-blue/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span>🛡️</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-primary">Rol y Permisos</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="role" class="block text-sm font-medium text-text-secondary mb-2">Rol del Usuario</label>
                            <select id="role" name="role" class="input-field w-full" required>
                                <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>Usuario Común</option>
                                <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Administrador</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>

                        <div class="bg-primary-dark border border-accent-gray/50 rounded-xl p-4 space-y-2 text-sm text-text-secondary">
                            <p><span class="text-text-primary font-medium">Usuario Común:</span> puede crear un comercio y gestionar sus menús.</p>
                            <p><span class="text-text-primary font-medium">Super Administrador:</span> acceso total al sistema — gestiona usuarios, comercios y menús.</p>
                        </div>
                    </div>
                </div>

                <!-- Info de cuenta (solo lectura) -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 bg-accent-gray/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span>ℹ️</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-primary">Información de la Cuenta</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Registrado</p>
                            <p class="text-text-primary font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Última actualización</p>
                            <p class="text-text-primary font-medium">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                            <p class="text-text-secondary text-xs">{{ $user->updated_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Email verificado</p>
                            @if($user->email_verified_at)
                                <p class="text-accent-green font-medium">✓ Verificado <span class="text-text-secondary text-xs font-normal">· {{ $user->email_verified_at->format('d/m/Y H:i') }}</span></p>
                            @else
                                <p class="text-red-400 font-medium">✗ No verificado</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-text-secondary text-xs mb-1">Comercios / Menús</p>
                            <p class="text-text-primary font-medium">
                                {{ $user->comercios->count() }} comercio(s) ·
                                {{ $user->comercios->sum(fn($c) => $c->menus->count()) }} menú(s)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>

