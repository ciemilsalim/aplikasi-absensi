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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

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
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>
<body class="antialiased font-sans h-full">
    {{-- Layout ini sekarang cukup fleksibel untuk menangani semua halaman tamu --}}
    @if (isset($slot))
        {{-- Untuk halaman login/register yang menggunakan <slot> dan memiliki layout dua kolom --}}
        {{ $slot }}
    @else
    {{-- Untuk halaman welcome/scanner yang menggunakan @yield dan memiliki layout standar --}}
    <div class="flex flex-col min-h-screen bg-slate-50 dark:bg-slate-900">
        {{-- Header dengan Sticky Navigation BARU --}}
        <header x-data="{ atTop: true }" @scroll.window="atTop = (window.pageYOffset < 50)" 
                :class="{ 'bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-md': !atTop }" 
                class="sticky top-0 z-50 transition-all duration-300">
            @include('layouts.navigation')
        </header>

        <main class="flex-grow">
            @yield('content')
        </main>

        <footer class="w-full bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm shadow-inner mt-auto">
            <div class="container mx-auto px-6 py-4 text-center text-slate-500 dark:text-slate-400 text-sm">
                &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}.
            </div>
        </footer>
    </div>
    @endif

    @stack('scripts')
</body>
</html>
