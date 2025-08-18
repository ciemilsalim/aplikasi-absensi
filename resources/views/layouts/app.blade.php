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

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <meta name="theme-color" content="#0284c7"/>
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">
    
    <script>
        const serverDarkModeEnabled = @json($darkModeEnabled ?? false);
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
                        sans: ['Helvetica', 'Arial', 'sans-serif'] 
                    },
                    colors: {
                        sky: { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e',950:'#082f49' }
                    }
                } 
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

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
{{-- PERBAIKAN: Menambahkan padding bawah untuk mengakomodasi bottom nav di mobile --}}
<body class="h-full antialiased bg-slate-50 dark:bg-slate-900 pb-16 lg:pb-0">
    <div id="page-loader" class="loader-container">
        <svg class="w-16 h-16 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <div id="page-content">
        <div x-data="{ sidebarOpen: false }" class="relative h-full">
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

            <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-72 lg:flex-col">
                @include('layouts.sidebar')
            </div>
            
            <div class="lg:pl-72">
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
                    &copy; {{ date('Y') }} {{ config('app.name') }} v1.0.0.
                    Dikembangkan oleh <a href="https://github.com/ciemilsalim," target="_blank" class="font-semibold text-sky-600 hover:underline">zahradev</a>.
                    <a href="{{ route('about') }}" class="hover:underline ml-2">Tentang Aplikasi</a>
                </div>
            </footer>
        </div>
    </div>
    
    <div x-data="{ show: false }" @scroll.window="show = (window.pageYOffset > 300)" class="fixed bottom-5 right-5 z-50">
        <button x-show="show" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" x-transition class="p-3 rounded-full bg-sky-600 text-white shadow-lg hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500" aria-label="Kembali ke atas" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
        </button>
    </div>

    {{-- PERBAIKAN: Bottom Navigation Bar dipindahkan ke sini --}}
    @auth
        @if(auth()->user()->role === 'teacher')
            <nav class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 z-50 px-2 lg:hidden">
                <div class="flex justify-around max-w-7xl mx-auto">
                    <a href="{{ route('teacher.dashboard') }}" class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                        <span class="material-icons">home</span>
                        <span class="text-xs mt-1">Beranda</span>
                    </a>
                    <a href="{{ route('teacher.subject.attendance.history') }}" class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                        <span class="material-icons">history_edu</span>
                        <span class="text-xs mt-1">Riwayat Mapel</span>
                    </a>
                    <a href="{{ route('teacher.subject.attendance.report') }}" class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                        <span class="material-icons">assessment</span>
                        <span class="text-xs mt-1">Rekap Mapel</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                        <span class="material-icons">account_circle</span>
                        <span class="text-xs mt-1">Profil</span>
                    </a>
                </div>
            </nav>
        @endif
    @endauth

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

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('Service Worker registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('Service Worker registration failed: ', registrationError);
                    });
            });
        }
    </script>

    {{-- PERBAIKAN: Skrip untuk navigasi aktif dipindahkan ke sini --}}
    @auth
        @if(auth()->user()->role === 'teacher')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const navItems = document.querySelectorAll('.nav-item');
                    const currentPath = window.location.pathname;
                    navItems.forEach(item => {
                        const itemPath = new URL(item.getAttribute('href')).pathname;
                        if (currentPath.startsWith(itemPath)) {
                            item.classList.add('text-sky-500', 'dark:text-sky-400');
                            item.classList.remove('text-gray-500', 'dark:text-gray-400');
                        } else {
                            item.classList.add('text-gray-500', 'dark:text-gray-400');
                            item.classList.remove('text-sky-500', 'dark:text-sky-400');
                        }
                    });
                });
            </script>
        @endif
    @endauth
</body>
</html>
