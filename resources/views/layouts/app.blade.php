<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:300,400,500,700&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1A2A44',
                        'secondary-dark': '#2A3A55',
                        'accent-blue': '#00C4FF',
                        'accent-green': '#00FF00',
                        'accent-gray': '#333333',
                        'text-primary': '#FFFFFF',
                        'text-secondary': '#CCCCCC',
                        'background': '#000000',
                    },
                    fontFamily: {
                        'sans': ['Roboto', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-dark: #1A2A44;
            --secondary-dark: #2A3A55;
            --accent-blue: #00C4FF;
            --accent-green: #00FF00;
            --accent-gray: #333333;
            --text-primary: #FFFFFF;
            --text-secondary: #CCCCCC;
            --background: #000000;
        }
        
        body {
            font-family: 'Roboto', system-ui, sans-serif;
            background: var(--background);
            color: var(--text-primary);
        }
        
        .glass-effect {
            background: rgba(42, 58, 85, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 196, 255, 0.2);
        }
        
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 196, 255, 0.3);
        }
        
        .neon-glow {
            box-shadow: 0 0 20px rgba(0, 196, 255, 0.5);
        }
        
        .neon-glow-green {
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }
        
        /* Navigation hover effects */
        .nav-link {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--accent-blue);
            text-shadow: 0 0 10px var(--accent-blue);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--accent-blue), #0099CC);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 8px 16px;
        }
        
        /* Button styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), #0099CC);
            color: var(--text-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 196, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 196, 255, 0.5);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--accent-green), #00CC00);
            color: var(--text-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 255, 0, 0.5);
        }
        
        /* Card styles */
        .metric-card {
            background: var(--secondary-dark);
            border: 1px solid var(--accent-gray);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 196, 255, 0.2);
            border-color: var(--accent-blue);
        }
        
        /* Input styles */
        input:focus, textarea:focus, select:focus {
            --tw-ring-color: var(--accent-blue) !important;
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 0 3px rgba(0, 196, 255, 0.1) !important;
        }
    </style>
</head>
<body class="bg-background text-text-primary min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav class="bg-background border-b border-accent-gray sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-2xl font-bold text-text-primary">
                                ServiMe
                            </h1>
                        </div>
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-4">
                                <a href="{{ route('dashboard') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Dashboard
                                </a>
                                <a href="{{ route('comercios.index') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('comercios.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Comercios
                                </a>
                                <a href="{{ route('menus.all') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('menus.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Menús
                                </a>
                                <a href="{{ route('analytics.dashboard') }}" 
                                   class="nav-link px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('analytics.*') ? 'active' : 'text-text-secondary hover:text-accent-blue' }}">
                                    Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="relative">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-accent-blue to-accent-green rounded-full flex items-center justify-center neon-glow">
                                    <span class="text-sm font-semibold text-text-primary">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                </div>
                                <span class="text-sm font-medium text-text-secondary">
                                    {{ auth()->user()->name }}
                                </span>
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
</body>
</html>