<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary">Crear Usuario</h1>
                    <p class="text-text-secondary mt-1">Nuevo usuario en el sistema</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">← Usuarios</a>
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

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
                @csrf

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
                            <x-text-input id="name" class="input-field w-full" type="text" name="name" value="{{ old('name') }}" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-text-secondary mb-2">Email</label>
                            <x-text-input id="email" class="input-field w-full" type="email" name="email" value="{{ old('email') }}" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div>
                            <label for="username" class="block text-sm font-medium text-text-secondary mb-2">
                                Nombre de usuario <span class="text-text-secondary font-normal">(opcional)</span>
                            </label>
                            <x-text-input id="username" class="input-field w-full" type="text" name="username"
                                          value="{{ old('username') }}" autocapitalize="none"
                                          placeholder="se genera solo a partir del email" />
                            <p class="text-xs text-text-secondary mt-1">
                                Si lo dejás vacío se arma con la parte de adelante del email. Sirve para entrar sin escribir el email completo.
                            </p>
                            <x-input-error :messages="$errors->get('username')" class="mt-1" />
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-text-secondary mb-2">WhatsApp</label>
                            <x-text-input id="whatsapp" class="input-field w-full" type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="+54 9 11 1234-5678" />
                            <x-input-error :messages="$errors->get('whatsapp')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-accent-green/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span>🔒</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-primary">Contraseña</h3>
                    </div>
                    {{-- El valor sale de la constante del modelo para que este texto no se
                         desincronice del código si algún día se cambia la contraseña. --}}
                    <div class="bg-amber-900/15 border border-amber-500/30 rounded-xl p-4 text-sm">
                        <p class="text-amber-200">
                            La cuenta se crea con la contraseña
                            <code class="font-mono bg-black/30 px-1.5 py-0.5 rounded select-all">{{ \App\Models\User::passwordInicial() }}</code>
                        </p>
                        <p class="text-amber-200/80 mt-1.5">
                            Es la misma para todas las cuentas nuevas, así que conviene que la cambie apenas ingrese. Va a poder entrar con su usuario o con su email.
                        </p>
                        <p class="text-amber-200/70 text-xs mt-2">
                            También le llega un mail con un link para elegir su propia contraseña, que es la forma recomendada. Ese link <span class="font-medium">vence a la hora</span>: si tarda en abrirlo, tiene que pedir uno nuevo desde &ldquo;Olvidé mi contraseña&rdquo;.
                        </p>
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
                                <option value="">Seleccionar rol...</option>
                                <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Usuario Común</option>
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Administrador</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>

                        <div class="bg-primary-dark border border-accent-gray/50 rounded-xl p-4 space-y-2 text-sm text-text-secondary">
                            <p><span class="text-text-primary font-medium">Usuario Común:</span> puede crear un comercio y gestionar sus menús.</p>
                            <p><span class="text-text-primary font-medium">Super Administrador:</span> acceso total al sistema — gestiona usuarios, comercios y menús.</p>
                        </div>
                    </div>
                </div>


                <!-- Acciones -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">Crear Usuario</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>

