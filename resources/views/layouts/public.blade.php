<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Aplikasi Absensi')</title>

    <!-- Favicon Dinamis (BARU) -->
        @if (isset($appLogoPath) && $appLogoPath)
            <link rel="icon" type="image/png" href="{{ asset('storage/' . $appLogoPath) }}">
        @endif
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        // **PERBAIKAN ADA DI SINI**
        // Skrip ini harus ada di <head> untuk mencegah layar berkedip.
        // Ia akan menerapkan tema gelap berdasarkan pengaturan dari admin.
        const serverDarkModeEnabled = @json($darkModeEnabled ?? false);

        // Prioritas: Pilihan pengguna di browser (localStorage) > Pengaturan default dari admin
        if (localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && serverDarkModeEnabled)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script>
        // Konfigurasi custom untuk Tailwind CSS agar sesuai tema
        tailwind.config = {
            darkMode: 'class', // Penting untuk mengaktifkan dark mode via class
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        sky: {
                           50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd',
                           300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9',
                           600: '#0284c7', 700: '#0369a1', 800: '#075985',
                           900: '#0c4a6e', 950: '#082f49',
                        }
                    }
                }
            }
        }
    </script>
    
    <style type="text/tailwindcss">
        body {
            @apply bg-sky-50 font-sans text-slate-700  dark:bg-slate-900;
        }
    </style>

    <style>
        #page-loader { transition: opacity 0.3s ease-in-out; }
        #page-content { opacity: 0; transition: opacity 0.4s ease-in-out; }
        #page-content.loaded { opacity: 1; }
    </style>
</head>
<body class="antialiased font-sans h-full">
    <!-- Page Loader -->
    <div id="page-loader" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white dark:bg-slate-900">
        <div class="w-16 h-16 border-4 border-dashed rounded-full animate-spin border-sky-600"></div>
    </div>

    <div class="flex flex-col min-h-screen">
        <header>
            @include('layouts.navigation')
        </header>

        <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

        <footer class="w-full bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm shadow-inner mt-auto">
            <div class="container mx-auto px-6 py-4 text-center text-slate-500 dark:text-slate-400 text-sm">
                &copy; {{ date('Y') }} Aplikasi Absensi Siswa.
            </div>
        </footer>
    </div>

    @stack('scripts')

    <script>
        // JavaScript untuk Loader dan Transisi Halaman
        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('page-loader');
            const content = document.getElementById('page-content');

            window.addEventListener('load', () => {
                if(loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => { loader.style.display = 'none'; }, 300);
                }
                if(content) { content.classList.add('loaded'); }
            });

            document.body.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link) return;
                const href = link.getAttribute('href');
                const target = link.getAttribute('target');

                if (target === '_blank' || (href && href.startsWith('#'))) return;
                
                if(link.closest('form') && link.closest('form').getAttribute('action').includes('logout')) {
                     if (loader) { loader.style.display = 'flex'; setTimeout(() => { loader.style.opacity = '1'; }, 10); }
                     return;
                }

                if(href && href !== window.location.href && !href.startsWith('javascript:')) {
                    e.preventDefault();
                    if(loader) {
                        loader.style.display = 'flex';
                        setTimeout(() => {
                            loader.style.opacity = '1';
                            window.location.href = href;
                        }, 50);
                    } else {
                        window.location.href = href;
                    }
                }
            });
        });
    </script>
</body>
</html>
