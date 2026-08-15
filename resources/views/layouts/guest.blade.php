<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Presensi Siswa'))</title>

    @if (isset($appLogoPath) && $appLogoPath)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $appLogoPath) }}">
    @endif

    <!-- Google Fonts & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Round" rel="stylesheet">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <meta name="theme-color" content="#0284c7" />
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    <script>
        var serverDarkModeEnabled = @json($darkModeEnabled ?? false);
        if (localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && serverDarkModeEnabled)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        slate: {
                            750: '#293548',
                            850: '#151e2e',
                            950: '#0b1120',
                        },
                        sky: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e', 950: '#082f49' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')

    <style type="text/tailwindcss">
        body { 
            @apply font-sans text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-950 antialiased selection:bg-sky-500/20 selection:text-sky-600; 
        }
        .loader-container {
            @apply fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 backdrop-blur-md;
            transition: opacity 0.4s ease-in-out, visibility 0.4s;
        }
        .loader-hidden {
            @apply opacity-0 invisible pointer-events-none;
        }
    </style>
</head>

<body class="antialiased font-sans h-full bg-slate-50 dark:bg-slate-950">
    <div id="page-loader" class="loader-container">
        <div class="flex flex-col items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-white/90 dark:bg-slate-850/90 shadow-2xl flex items-center justify-center border border-white/20">
                <svg class="w-6 h-6 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-slate-600 dark:text-slate-300 tracking-wider">Memuat Halaman...</span>
        </div>
    </div>

    @if (isset($slot))
        {{ $slot }}
    @else
        <div class="flex flex-col min-h-screen">
            <header x-data="{ atTop: true }" @scroll.window="atTop = (window.pageYOffset < 50)"
                :class="{ 'bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-sm': !atTop }"
                class="sticky top-0 z-50 transition-all duration-300">
                @include('layouts.navigation')
            </header>

            <main class="flex-grow">
                @yield('content')
            </main>

            <footer class="w-full bg-white/50 dark:bg-slate-900/50 border-t border-slate-200/60 dark:border-slate-800/80 py-6 mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-slate-500 dark:text-slate-400 text-xs space-y-1">
                    <p>&copy; {{ date('Y') }} {{ $appName ?? config('app.name') }} &bull; Sistem Presensi Terpadu</p>
                    <p class="text-[11px] text-slate-400">Dikembangkan dengan standar antarmuka modern & responsif</p>
                </div>
            </footer>
        </div>
    @endif

    <div x-data="{ show: false }" @scroll.window="show = (window.pageYOffset > 300)"
        class="fixed bottom-5 right-5 z-50">
        <button x-show="show" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" x-transition
            class="p-3 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-600/30 transition-all active:scale-95"
            aria-label="Kembali ke atas" style="display: none;">
            <span class="material-icons text-lg">arrow_upward</span>
        </button>
    </div>

    @stack('scripts')
    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.classList.add('loader-hidden');
            }
        });
    </script>
</body>

</html>