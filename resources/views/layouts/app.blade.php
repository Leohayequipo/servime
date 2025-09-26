<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ServiME') }} - Panel de Administración</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/ServiME_IA.ico') }}">

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
        
        /* Light mode styles */
        :root:not(.dark) {
            --background: #ffffff;
            --primary-dark: #f8fafc;
            --secondary-dark: #e2e8f0;
            --accent-gray: #cbd5e1;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }
        
        /* Light mode text colors - darker */
        :root:not(.dark) h1,
        :root:not(.dark) h2,
        :root:not(.dark) h3,
        :root:not(.dark) h4,
        :root:not(.dark) h5,
        :root:not(.dark) h6 {
            color: #0f172a !important;
        }
        
        :root:not(.dark) p,
        :root:not(.dark) span,
        :root:not(.dark) div {
            color: #0f172a !important;
        }
        
        /* Force darker text for better contrast in light mode */
        :root:not(.dark) .text-gray-300,
        :root:not(.dark) .text-gray-400,
        :root:not(.dark) .text-gray-500,
        :root:not(.dark) .text-gray-600 {
            color: #0f172a !important;
        }
        
        /* Ensure images and icons are not affected */
        :root:not(.dark) img {
            opacity: 1 !important;
            filter: none !important;
            display: block !important;
            max-width: 100% !important;
            height: auto !important;
        }
        
        /* Protect all image elements */
        :root:not(.dark) .logo,
        :root:not(.dark) .business-logo,
        :root:not(.dark) .menu-image,
        :root:not(.dark) .card-image {
            opacity: 1 !important;
            filter: none !important;
            display: block !important;
        }
        
        /* Override any color changes for images */
        :root:not(.dark) img,
        :root:not(.dark) .logo,
        :root:not(.dark) .business-logo,
        :root:not(.dark) .menu-image,
        :root:not(.dark) .card-image {
            color: inherit !important;
            background: transparent !important;
        }
        
        /* Protect image containers */
        :root:not(.dark) .relative,
        :root:not(.dark) .overflow-hidden,
        :root:not(.dark) .rounded-t-xl {
            background: transparent !important;
        }
        
        /* Force image visibility - ULTRA PROTECTION */
        :root:not(.dark) img {
            display: block !important;
            opacity: 1 !important;
            filter: none !important;
            visibility: visible !important;
            width: auto !important;
            height: auto !important;
            max-width: 100% !important;
            position: relative !important;
            z-index: 1 !important;
        }
        
        :root:not(.dark) img[src*="storage"] {
            display: block !important;
            opacity: 1 !important;
            filter: none !important;
            visibility: visible !important;
            width: auto !important;
            height: auto !important;
            object-fit: cover !important;
        }
        
        /* NUCLEAR OPTION - Force ALL images to be visible but respect size classes */
        :root:not(.dark) * img {
            display: block !important;
            opacity: 1 !important;
            filter: none !important;
            visibility: visible !important;
            background: none !important;
            color: inherit !important;
            width: auto !important;
            height: auto !important;
        }
        
        /* Override any hiding */
        :root:not(.dark) .hidden img,
        :root:not(.dark) .invisible img,
        :root:not(.dark) .opacity-0 img {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        /* Respect Tailwind size classes for images */
        :root:not(.dark) .h-4 img,
        :root:not(.dark) .w-4 img {
            height: 1rem !important;
            width: 1rem !important;
        }
        
        :root:not(.dark) .h-6 img,
        :root:not(.dark) .w-6 img {
            height: 1.5rem !important;
            width: 1.5rem !important;
        }
        
        :root:not(.dark) .h-8 img,
        :root:not(.dark) .w-8 img {
            height: 2rem !important;
            width: 2rem !important;
        }
        
        /* Force small size for analytics table logos */
        :root:not(.dark) table img {
            height: 1rem !important;
            width: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
        }
        
        /* Override any large image rules for table */
        :root:not(.dark) tbody img,
        :root:not(.dark) .whitespace-nowrap img {
            height: 1rem !important;
            width: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
        }
        
        /* Light mode button text */
        :root:not(.dark) .btn-sm,
        :root:not(.dark) .btn-xs {
            color: #1e293b !important;
        }
        
        /* Light mode specific text elements - darker */
        :root:not(.dark) .text-white {
            color: #0f172a !important;
        }
        
        :root:not(.dark) .text-gray-300 {
            color: #64748b !important;
        }
        
        :root:not(.dark) .text-gray-400 {
            color: #64748b !important;
        }
        
        :root:not(.dark) .text-gray-500 {
            color: #64748b !important;
        }
        
        /* Force specific text elements to be darker in light mode */
        :root:not(.dark) h1,
        :root:not(.dark) h2,
        :root:not(.dark) h3,
        :root:not(.dark) h4,
        :root:not(.dark) h5,
        :root:not(.dark) h6 {
            color: #0f172a !important;
        }
        
        :root:not(.dark) p {
            color: #0f172a !important;
        }
        
        :root:not(.dark) .text-gray-300,
        :root:not(.dark) .text-gray-400,
        :root:not(.dark) .text-gray-500 {
            color: #0f172a !important;
        }
        
        /* Elegant light mode buttons */
        :root:not(.dark) button {
            background: #f8fafc !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            font-weight: 500 !important;
        }
        
        :root:not(.dark) .btn-primary {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border: 1px solid #2563eb !important;
            box-shadow: 0 1px 3px rgba(59,130,246,0.3) !important;
        }
        
        :root:not(.dark) .btn-secondary {
            background: #64748b !important;
            color: #ffffff !important;
            border: 1px solid #475569 !important;
            box-shadow: 0 1px 3px rgba(100,116,139,0.3) !important;
        }
        
        :root:not(.dark) .btn-danger {
            background: #ef4444 !important;
            color: #ffffff !important;
            border: 1px solid #dc2626 !important;
            box-shadow: 0 1px 3px rgba(239,68,68,0.3) !important;
        }
        
        /* Elegant text links for light mode - no underlines */
        :root:not(.dark) a {
            color: #3b82f6 !important;
            text-decoration: none !important;
            font-weight: 500 !important;
        }
        
        :root:not(.dark) a:hover {
            color: #1d4ed8 !important;
            text-decoration: none !important;
        }
        
        /* Action links as subtle buttons */
        :root:not(.dark) a[href*="ver"],
        :root:not(.dark) a[href*="edit"],
        :root:not(.dark) a[href*="show"] {
            background: #f1f5f9 !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1 !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            display: inline-block !important;
            font-size: 14px !important;
        }
        
        :root:not(.dark) a[href*="delete"],
        :root:not(.dark) a[href*="destroy"] {
            background: #fef2f2 !important;
            color: #dc2626 !important;
            border: 1px solid #fecaca !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            display: inline-block !important;
            font-size: 14px !important;
        }
        
        :root:not(.dark) .text-blue-500,
        :root:not(.dark) .text-green-500,
        :root:not(.dark) .text-red-500 {
            color: #1e293b !important;
        }
        
        /* Light mode body background */
        :root:not(.dark) body {
            background: #ffffff !important;
        }
        
        /* Light mode main content */
        :root:not(.dark) .min-h-screen {
            background: #ffffff !important;
        }
        
        /* Light mode container */
        :root:not(.dark) .container {
            background: #ffffff !important;
        }
        
        /* Force white background for all main elements */
        :root:not(.dark) .bg-background {
            background: #ffffff !important;
        }
        
        :root:not(.dark) .bg-black {
            background: #ffffff !important;
        }
        
        :root:not(.dark) .bg-gray-900 {
            background: #ffffff !important;
        }
        
        /* Force light mode for all dark elements */
        :root:not(.dark) .bg-gray-800,
        :root:not(.dark) .bg-gray-700,
        :root:not(.dark) .bg-gray-600 {
            background: #f8fafc !important;
        }
        
        /* Force light mode for cards and containers */
        :root:not(.dark) [class*="bg-"] {
            background: #f8fafc !important;
        }
        
        :root:not(.dark) .bg-background {
            background: #ffffff !important;
        }
        
        /* Light mode button text */
        :root:not(.dark) .btn-primary {
            color: #ffffff !important;
            background: #0ea5e9 !important;
        }
        
        :root:not(.dark) .btn-secondary {
            color: #1e293b !important;
            background: #e2e8f0 !important;
        }
        
        /* Light mode navigation text */
        :root:not(.dark) .nav-link {
            color: #64748b !important;
        }
        
        :root:not(.dark) .nav-link:hover {
            color: #0ea5e9 !important;
        }
        
        :root:not(.dark) .nav-link.active {
            color: #0ea5e9 !important;
            background: #e0f2fe !important;
        }
        
        /* Dark mode styles */
        .dark {
            --background: #000000;
            --primary-dark: #1A2A44;
            --secondary-dark: #2A3A55;
            --accent-gray: #333333;
            --text-primary: #FFFFFF;
            --text-secondary: #CCCCCC;
        }
        
        /* Dark mode - RESPECT ORIGINAL AESTHETIC */
        .dark body {
            background: var(--background) !important;
            color: var(--text-primary) !important;
        }
        
        .dark .min-h-screen {
            background: var(--background) !important;
        }
        
        .dark .container {
            background: var(--background) !important;
        }
        
        /* Dark mode cards - original aesthetic */
        .dark .metric-card {
            background: var(--primary-dark) !important;
            border: 1px solid var(--accent-gray) !important;
        }
        
        .dark .glass-effect {
            background: rgba(42, 58, 85, 0.8) !important;
            border: 1px solid rgba(0, 196, 255, 0.2) !important;
        }
        
        /* Dark mode text - original aesthetic */
        .dark h1,
        .dark h2,
        .dark h3,
        .dark h4,
        .dark h5,
        .dark h6 {
            color: var(--text-primary) !important;
        }
        
        .dark p,
        .dark span,
        .dark div {
            color: var(--text-primary) !important;
        }
        
        /* Dark mode buttons - original aesthetic */
        .dark button {
            background: var(--secondary-dark) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--accent-gray) !important;
        }
        
        .dark .btn-primary {
            background: #0ea5e9 !important;
            color: #ffffff !important;
            border: 1px solid #0284c7 !important;
        }
        
        .dark .btn-secondary {
            background: #64748b !important;
            color: #ffffff !important;
            border: 1px solid #475569 !important;
        }
        
        .dark .btn-danger {
            background: #ef4444 !important;
            color: #ffffff !important;
            border: 1px solid #dc2626 !important;
        }
        
        /* Dark mode profile dropdown */
        .dark .group:hover .group-hover\:block {
            background: var(--primary-dark) !important;
            border: 1px solid var(--accent-gray) !important;
        }
        
        .dark .absolute {
            background: var(--primary-dark) !important;
            border: 1px solid var(--accent-gray) !important;
        }
        
        .dark .py-2 {
            color: var(--text-primary) !important;
        }
        
        .dark .px-4 {
            color: var(--text-primary) !important;
        }
        
        /* Dark mode user dropdown menu */
        .dark .user-dropdown {
            background: var(--primary-dark) !important;
            border: 1px solid var(--accent-gray) !important;
        }
        
        .dark .user-dropdown a {
            color: var(--text-primary) !important;
        }
        
        .dark .user-dropdown a:hover {
            background: var(--secondary-dark) !important;
            color: var(--text-primary) !important;
        }
        
        /* Dark mode navigation and user menu */
        .dark .bg-background {
            background: var(--background) !important;
        }
        
        .dark .border-accent-gray {
            border-color: var(--accent-gray) !important;
        }
        
        .dark .nav-link {
            color: var(--text-primary) !important;
        }
        
        .dark .nav-link:hover {
            color: #0ea5e9 !important;
        }
        
        .dark .nav-link.active {
            color: #0ea5e9 !important;
            background: rgba(14, 165, 233, 0.1) !important;
        }
        
        /* Dark mode user profile - FORCE STYLES */
        .dark .user-profile {
            color: var(--text-primary) !important;
        }
        
        .dark .user-profile:hover {
            background: var(--secondary-dark) !important;
        }
        
        /* Dark mode - FORCE ALL ELEMENTS */
        .dark * {
            color: var(--text-primary) !important;
        }
        
        .dark .bg-white {
            background: var(--primary-dark) !important;
        }
        
        .dark .text-gray-900 {
            color: var(--text-primary) !important;
        }
        
        .dark .text-gray-700 {
            color: var(--text-secondary) !important;
        }
        
        .dark .border-gray-300 {
            border-color: var(--accent-gray) !important;
        }
        
        .dark input {
            background: var(--secondary-dark) !important;
            color: var(--text-primary) !important;
            border-color: var(--accent-gray) !important;
        }
        
        .dark button {
            background: var(--secondary-dark) !important;
            color: var(--text-primary) !important;
            border-color: var(--accent-gray) !important;
        }
        
        .glass-effect {
            background: rgba(42, 58, 85, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 196, 255, 0.2);
        }
        
        /* Light mode glass effect */
        :root:not(.dark) .glass-effect {
            background: rgba(248, 250, 252, 0.8);
            border: 1px solid rgba(0, 196, 255, 0.3);
        }
        
        /* Light mode cards */
        :root:not(.dark) .metric-card {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        /* Light mode glass effect cards */
        :root:not(.dark) .glass-effect {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        /* Light mode all cards */
        :root:not(.dark) .bg-primary-dark,
        :root:not(.dark) .bg-secondary-dark {
            background: #f8fafc !important;
        }
        
        /* Light mode navigation */
        :root:not(.dark) .nav-link {
            color: #64748b;
        }
        
        :root:not(.dark) .nav-link:hover {
            color: #0ea5e9;
        }
        
        :root:not(.dark) .nav-link.active {
            color: #0ea5e9;
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
                                {{ config('app.name', 'ServiME') }}
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
                                        
                                        <!-- Modo Oscuro -->
                                        <button onclick="toggleDarkMode()" 
                                                class="flex items-center w-full px-4 py-3 text-sm text-text-secondary hover:text-accent-blue hover:bg-primary-dark transition-all duration-300">
                                            <span class="text-lg mr-3">🌙</span>
                                            Modo oscuro
                                        </button>
                                        
                                        <!-- Ayuda -->
                                        <a href="{{ route('help.index') }}" 
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
    </body>
</html>