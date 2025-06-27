<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
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
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
    
    <style type="text/tailwindcss">
        body { @apply font-sans; }
        /* PERBAIKAN: Logika baru untuk loader dan transisi halaman */
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
<body class="h-full antialiased bg-slate-50 dark:bg-slate-900">
    <!-- Page Loader BARU -->
    <div id="page-loader" class="loader-container">
        <svg class="w-16 h-16 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Wrapper Konten Utama -->
    <div id="page-content">
        <div x-data="{ sidebarOpen: false }" class="relative h-full">
            <!-- Sidebar untuk Mobile (Off-canvas) -->
            <div x-show="sidebarOpen" class="relative z-40 lg:hidden" @click.away="sidebarOpen = false" x-transition>
                <div class="fixed inset-0 bg-gray-600/80"></div>
                <div class="fixed inset-0 flex">
                    <div class="relative mr-16 flex w-full max-w-xs flex-1">
                        <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        @include('layouts.sidebar')
                    </div>
                </div>
            </div>

            <!-- Sidebar Statis untuk Desktop -->
            <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-72 lg:flex-col">
                @include('layouts.sidebar')
            </div>
            
            <!-- Konten Utama -->
            <div class="lg:pl-72">
                <!-- Header Sticky di Atas -->
                {{-- PERBAIKAN: Warna latar header disamakan dengan sidebar dan efek blur dihapus untuk tampilan yang lebih solid --}}
                <div class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                    <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 justify-end">
                        @include('layouts.topbar-profile')
                    </div>
                </div>

                <main class="py-10">
                    <div class="px-4 sm:px-6 lg:px-8">
                        @if (isset($header))
                            <div class="mb-6">
                                {{ $header }}
                            </div>
                        @endif
                        
                        {{ $slot }}
                    </div>
                </main>
            </div>

            <footer class="lg:pl-72">
                <div class="py-4 text-center text-xs text-slate-500 dark:text-slate-400 border-t dark:border-slate-700">
                    &copy; {{ date('Y') }} {{ $appName ?? config('app.name') }}.
                    <a href="{{ route('about') }}" class="hover:underline">Tentang Aplikasi</a>
                </div>
            </footer>
        </div>
    </div>
    
    <div x-data="{ show: false }" @scroll.window="show = (window.pageYOffset > 300)" class="fixed bottom-5 right-5 z-50">
        <button x-show="show" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" x-transition class="p-3 rounded-full bg-sky-600 text-white shadow-lg hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500" aria-label="Kembali ke atas" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
        </button>
    </div>

    @stack('scripts')
    <script>
        // Skrip untuk menyembunyikan loader dan menampilkan konten
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
