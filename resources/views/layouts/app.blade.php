<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ServiME') }} - Panel de Administración</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:300,400,500,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --primary-dark:   #111318;
            --secondary-dark: #1c1f26;
            --accent-blue:    #f59e0b;
            --accent-green:   #10b981;
            --accent-gray:    #374151;
            --text-primary:   #FFFFFF;
            --text-secondary: #94a3b8;
            --background:     #0b0c10;
        }
        body {
            font-family: Roboto, Inter, system-ui, sans-serif;
            background: var(--background);
            color: var(--text-primary);
        }
        /* Focus rings use amber */
        input:focus, textarea:focus, select:focus {
            --tw-ring-color: var(--accent-blue);
            border-color: var(--accent-blue);
        }

        /* SweetAlert2 — reemplazar azules por paleta dark amber */
        .swal2-icon.swal2-question {
            border-color: rgba(245, 158, 11, 0.3) !important;
            color: #f59e0b !important;
        }
        .swal2-icon.swal2-question .swal2-icon-content {
            color: #f59e0b !important;
        }
        .swal2-icon.swal2-info {
            border-color: rgba(245, 158, 11, 0.3) !important;
            color: #f59e0b !important;
        }
        .swal2-icon.swal2-info .swal2-icon-content {
            color: #f59e0b !important;
        }
        /* Popup title */
        .swal2-title {
            color: #ffffff !important;
        }
        /* Popup background */
        .swal2-popup {
            background: #1c1f26 !important;
        }
        /* Focus ring en botones — quitar el azul por defecto */
        .swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.4) !important;
        }
        .swal2-styled.swal2-cancel:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.4) !important;
        }
    </style>
    
    <!-- SweetAlert2 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
<body class="text-text-primary min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav class="bg-background border-b border-accent-gray sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-3">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <a href="{{ route('dashboard') }}" class="hover:opacity-80 transition-opacity duration-200">
                                <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" style="height:140px;width:auto;">
                            </a>
                        </div>
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-4">
                                <a href="{{ route('dashboard') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Dashboard
                                </a>
                                
                                <!-- Navegación común para todos los usuarios -->
                                <a href="{{ route('comercios.index') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('comercios.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Comercios
                                </a>
                                @if(Auth::user()->isSuperAdmin())
                                    <a href="{{ route('admin.menus.index') }}" 
                                       class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.menus.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                        Menús
                                    </a>
                                @else
                                    <a href="{{ route('menus.all') }}" 
                                       class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('menus.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                        Menús
                                    </a>
                                @endif
                                
                                <!-- Analytics oculto temporalmente -->
                                {{-- <a href="{{ route('analytics.dashboard') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('analytics.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Analytics
                                </a> --}}
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="relative group">
                            <button class="flex items-center space-x-3 hover:bg-secondary-dark px-3 py-2 rounded-lg transition-all duration-300">
                                <div class="w-8 h-8 bg-gradient-to-r from-accent-blue to-accent-green rounded-full flex items-center justify-center neon-glow">
                                    <span class="text-sm font-semibold text-text-primary">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                </div>
                                <span class="text-sm font-medium text-text-secondary">
                                    {{ auth()->user()->name }}
                                </span>
                                <svg class="w-4 h-4 text-text-secondary group-hover:text-accent-blue transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-64 bg-secondary-dark border border-accent-gray rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 neon-glow">
                                <div class="py-2">
                                    <!-- User Info Header -->
                                    <div class="px-4 py-3 border-b border-accent-gray">
                                        <p class="text-sm font-medium text-text-primary">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-text-secondary">{{ auth()->user()->email }}</p>
                                    </div>
                                    
                                    <!-- Menu Items -->
                                    <div class="py-2">
                                        <!-- Perfil -->
                                        <a href="{{ route('profile.edit') }}" 
                                           class="flex items-center px-4 py-3 text-sm text-text-secondary hover:text-accent-blue hover:bg-primary-dark transition-all duration-300">
                                            <span class="text-lg mr-3">👤</span>
                                            Perfil
                                        </a>
                                        
                                        <!-- Ayuda -->
                                        <a href="{{ url('/?chat=1') }}"
                                           class="flex items-center px-4 py-3 text-sm text-text-secondary hover:text-accent-blue hover:bg-primary-dark transition-all duration-300">
                                            <span class="text-lg mr-3">❓</span>
                                            Ayuda
                                        </a>
                                        
                                        <!-- Divider -->
                                        <div class="border-t border-accent-gray my-2"></div>
                                        
                                        <!-- Cerrar Sesión -->
                                        <form method="POST" action="{{ route('logout') }}" class="block">
                                            @csrf
                                            <button type="submit" 
                                                    class="flex items-center w-full px-4 py-3 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all duration-300">
                                                <span class="text-lg mr-3">🚪</span>
                                                Cerrar sesión
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

    <!-- Dark Mode Script -->
    <script src="{{ asset('js/dark-mode.js') }}"></script>
    
    <!-- Scripts -->
    <script>
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Add hover effects to cards
        document.querySelectorAll('.metric-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('neon-glow');
            });
            
            card.addEventListener('mouseleave', function() {
                this.classList.remove('neon-glow');
            });
        });
    </script>
    
    <!-- SweetAlert2 Helper Functions -->
    <script>
        // Configuración global de SweetAlert2 para tema dark
        const swalConfig = {
            background: '#1c1f26',
            color: '#ffffff',
            customClass: {
                popup: 'dark-modal',
            }
        };

        // Reemplazar alerts nativos con SweetAlert2
        window.originalAlert = window.alert;
        window.alert = function(message) {
            Swal.fire({
                ...swalConfig,
                title: 'Información',
                text: message,
                icon: 'info',
                iconColor: '#f59e0b',
                confirmButtonText: 'OK',
                confirmButtonColor: '#f59e0b'
            });
        };

        // Función para confirms con SweetAlert2 (asíncrona)
        function showConfirm(message, callback) {
            Swal.fire({
                ...swalConfig,
                title: 'Confirmar',
                text: message,
                icon: 'question',
                iconColor: '#f59e0b',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#EF4444',
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                callback(result.isConfirmed);
            });
        }

        // Función para confirms con indicador de carga
        function showConfirmWithLoader(message, callback, loadingMessage = 'Procesando...') {
            Swal.fire({
                ...swalConfig,
                title: 'Confirmar',
                text: message,
                icon: 'question',
                iconColor: '#f59e0b',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#EF4444',
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loader global
                    LoadingManager.showGlobalLoader(loadingMessage);
                    callback(true);
                } else {
                    callback(false);
                }
            });
        }

        // Mantener confirm nativo para formularios (síncrono)
        window.originalConfirm = window.confirm;

        // Helper functions para usar SweetAlert2 fácilmente
        function showSuccess(message, title = '¡Éxito!') {
            Swal.fire({
                ...swalConfig,
                title: title,
                text: message,
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10B981'
            });
        }

        function showError(message, title = 'Error') {
            Swal.fire({
                ...swalConfig,
                title: title,
                text: message,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#EF4444'
            });
        }

        function showWarning(message, title = 'Advertencia') {
            Swal.fire({
                ...swalConfig,
                title: title,
                text: message,
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#F59E0B'
            });
        }

        function showInfo(message, title = 'Información') {
            Swal.fire({
                ...swalConfig,
                title: title,
                text: message,
                icon: 'info',
                iconColor: '#f59e0b',
                confirmButtonText: 'OK',
                confirmButtonColor: '#f59e0b'
            });
        }

        // Sistema de indicadores de carga
        const LoadingManager = {
            // Indicadores de carga globales
            showGlobalLoader: function(message = 'Cargando...') {
                Swal.fire({
                    ...swalConfig,
                    title: message,
                    html: `
                        <div class="flex flex-col items-center space-y-4">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-blue" style="border-color: #f59e0b;"></div>
                            <p class="text-gray-300">Por favor espera...</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },

            hideGlobalLoader: function() {
                Swal.close();
            },

            // Indicadores de carga para botones
            showButtonLoader: function(button, text = 'Cargando...') {
                if (!button) return;
                
                // Guardar el contenido original
                button.setAttribute('data-original-text', button.innerHTML);
                button.setAttribute('data-original-disabled', button.disabled);
                
                // Mostrar loader
                button.innerHTML = `
                    <div class="flex items-center justify-center space-x-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        <span>${text}</span>
                    </div>
                `;
                button.disabled = true;
                button.classList.add('opacity-75', 'cursor-not-allowed');
            },

            hideButtonLoader: function(button) {
                if (!button) return;
                
                // Restaurar contenido original
                const originalText = button.getAttribute('data-original-text');
                const originalDisabled = button.getAttribute('data-original-disabled');
                
                if (originalText) {
                    button.innerHTML = originalText;
                    button.removeAttribute('data-original-text');
                }
                if (originalDisabled !== null) {
                    button.disabled = originalDisabled === 'true';
                    button.removeAttribute('data-original-disabled');
                }
                
                button.classList.remove('opacity-75', 'cursor-not-allowed');
            },

            // Indicador de carga para formularios
            showFormLoader: function(form, message = 'Enviando...') {
                if (!form) return;
                
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    this.showButtonLoader(submitButton, message);
                }
                
                // Deshabilitar todos los inputs del formulario
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(input => {
                    if (!input.disabled) {
                        input.disabled = true;
                        input.classList.add('opacity-75');
                    }
                });
            },

            hideFormLoader: function(form) {
                if (!form) return;
                
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    this.hideButtonLoader(submitButton);
                }
                
                // Habilitar todos los inputs del formulario
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(input => {
                    if (!input.disabled || input.getAttribute('data-original-disabled') === 'false') {
                        input.disabled = false;
                        input.classList.remove('opacity-75');
                    }
                });
            }
        };

        // Hacer disponible globalmente
        window.LoadingManager = LoadingManager;
    </script>
    </body>
</html>
