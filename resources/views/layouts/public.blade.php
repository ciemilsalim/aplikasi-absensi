<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    
    @if (isset($appLogoPath) && $appLogoPath)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $appLogoPath) }}">
    @endif
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        const serverDarkModeEnabled = @json($darkModeEnabled ?? false);
        if (localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && serverDarkModeEnabled)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { 
            darkMode: 'class', 
            theme: { 
                extend: { 
                    fontFamily: { 
                        sans: ['Poppins', 'sans-serif'] 
                    },
                    colors: {
                        sky: { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e',950:'#082f49' }
                    }
                } 
            } 
        }
    </script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { 
                extend: { 
                    fontFamily: { 
                        sans: ['Poppins', 'sans-serif'] 
                    },
                    colors: {
                        sky: { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e',950:'#082f49' }
                    }
                } 
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
    
    <style type="text/tailwindcss">
        body { @apply font-sans; }
        .loader-container {
            @apply fixed inset-0 z-[9999] flex items-center justify-center bg-slate-50 dark:bg-slate-900;
            transition: opacity 0.5s ease-in-out, visibility 0.5s;
        }
        .loader-hidden {
            @apply opacity-0 invisible;
        }
        .content-wrapper {
            @apply opacity-0;
            transition: opacity 0.5s ease-in-out;
        }
        .content-visible {
            @apply opacity-100;
        }
    </style>
</head>
<body class="antialiased font-sans h-full">
    <div id="page-loader" class="loader-container">
        <svg class="w-16 h-16 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
    <div class="flex flex-col min-h-screen bg-slate-50 dark:bg-slate-900">
        <header x-data="{ atTop: true }" @scroll.window="atTop = (window.pageYOffset < 50)" 
                :class="{ 'bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm shadow-lg': !atTop }" 
                class="sticky top-0 z-50 transition-all duration-300">
            @include('layouts.navigation')
        </header>
        <main class="flex-grow">
            @yield('content')
        </main>
        <footer class="w-full bg-slate-100 dark:bg-slate-800 mt-4">
            {{-- PERBAIKAN: Menambahkan versi dan nama pengembang --}}
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                <p>&copy; {{ date('Y') }} {{ $appName }} - {{ config('app.name') }} v1.0.0.</p>
                <p class="mt-1">Dikembangkan oleh <a href="https://github.com/ciemilsalim" target="_blank" class="font-semibold text-sky-600 hover:underline">zahradev</a>.
                 - <a href="{{ route('about') }}" class="hover:underline">Tentang Aplikasi</a></p>
            </div>
        </footer>
    </div>
    
    <div x-data="{ show: false }" 
         @scroll.window="show = (window.pageYOffset > 300)"
         class="fixed bottom-5 right-5 z-50">
        <button x-show="show" 
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                x-transition
                class="p-3 rounded-full bg-sky-600 text-white shadow-lg hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500"
                aria-label="Kembali ke atas"
                style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
            </svg>
        </button>
    </div>

    @stack('scripts')
    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('page-loader');
            const content = document.getElementById('page-content');
            if (loader) {
                loader.classList.add('loader-hidden');
            }
            if (content) {
                content.classList.add('content-visible');
            }
        });
    </script>
</body>
</html>
