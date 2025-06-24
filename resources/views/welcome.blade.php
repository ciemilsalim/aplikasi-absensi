<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName ?? config('app.name', 'Laravel') }} - Sistem Absensi Modern</title>
    
    @if (isset($appLogoPath) && $appLogoPath)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $appLogoPath) }}">
    @endif
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    animation: { 
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                        'gentle-float': 'gentleFloat 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        gentleFloat: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        body { @apply bg-slate-50 dark:bg-slate-900 font-sans text-slate-700 dark:text-slate-300; }
        .animate-on-scroll { opacity: 0; transition: opacity 0.8s ease-out, transform 0.8s ease-out; transform: translateY(30px); }
        .is-visible { opacity: 1; transform: none; }
    </style>
</head>
<body class="antialiased">
    <div class="flex flex-col min-h-screen">
        <header x-data="{ atTop: true }" @scroll.window="atTop = (window.pageYOffset < 50)" 
                :class="{ 'bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm shadow-lg': !atTop }" 
                class="sticky top-0 z-50 transition-all duration-300">
            @include('layouts.navigation')
        </header>

        <main>
            <!-- Hero Section -->
            <section class="relative bg-white dark:bg-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center py-20 lg:py-32">
                    <div class="text-center lg:text-left">
                        <h1 class="text-4xl md:text-6xl font-extrabold text-slate-900 dark:text-white leading-tight animate-[fade-in-up_0.8s_ease-out_forwards]">
                           Absensi Digital <span class="text-sky-500">{{ $appName }}</span>
                        </h1>
                        {{-- PERBAIKAN: Warna teks disesuaikan untuk dark mode --}}
                        <p class="mt-6 text-lg text-slate-600 dark:text-slate-300 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.2s;">
                            Solusi terpadu untuk memonitor kehadiran secara akurat, memudahkan manajemen kelas bagi guru, dan memberikan ketenangan bagi orang tua.
                        </p>
                        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.4s;">
                            @guest
                                <a href="{{ route('login') }}" class="w-full sm:w-auto rounded-md bg-sky-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                                    Login ke Sistem
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto rounded-md bg-sky-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                                    Masuk ke Dasbor
                                </a>
                            @endauth
                            <a href="#fitur" class="w-full sm:w-auto rounded-md bg-slate-100 dark:bg-slate-800 px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700">
                                Lihat Fitur
                            </a>
                        </div>
                    </div>
                    <div class="relative mt-12 lg:mt-0">
                        <div class="animate-gentle-float">
                             <x-application-logo class="mx-auto w-[20rem] max-w-full animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.3s;" alt="Ilustrasi siswa dan sistem pengenalan untuk absensi"/>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fitur Unggulan -->
            <section id="fitur" class="bg-slate-50 dark:bg-slate-800/50 py-20 sm:py-32">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Dirancang untuk Semua Peran</h2>
                        {{-- PERBAIKAN: Warna teks disesuaikan untuk dark mode --}}
                        <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-400">Setiap peran mendapatkan dasbor dan fungsionalitas yang sesuai.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-10">
                        <div class="feature-card animate-on-scroll text-center p-8 bg-white dark:bg-slate-800 rounded-2xl shadow-lg">
                             <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Admin & Manajemen</h3>
                            {{-- PERBAIKAN: Warna teks disesuaikan untuk dark mode --}}
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Kelola data master, pantau statistik, dan cetak laporan PDF dengan mudah.</p>
                        </div>
                        <div class="feature-card animate-on-scroll text-center p-8 bg-white dark:bg-slate-800 rounded-2xl shadow-lg" style="transition-delay: 150ms">
                            <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0-5.455-1.743l-1.558-.467m-6.323-.878A11.962 11.962 0 0 1 3 12.878V12l8.242-8.242a1 1 0 0 1 1.414 0l8.242 8.242v.878a11.962 11.962 0 0 1-4.212 4.212l-.467-1.558A9.38 9.38 0 0 0 15 19.128Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Guru & Wali Kelas</h3>
                            {{-- PERBAIKAN: Warna teks disesuaikan untuk dark mode --}}
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Akses pemindai QR, kelola kehadiran siswa perwalian, dan setujui pengajuan izin.</p>
                        </div>
                        <div class="feature-card animate-on-scroll text-center p-8 bg-white dark:bg-slate-800 rounded-2xl shadow-lg" style="transition-delay: 300ms">
                             <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283-.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Orang Tua Wali</h3>
                            {{-- PERBAIKAN: Warna teks disesuaikan untuk dark mode --}}
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Pantau riwayat kehadiran anak dan ajukan izin atau sakit secara online.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <footer class="w-full bg-slate-100 dark:bg-slate-800">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Hak Cipta Dilindungi.
            </div>
        </footer>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.animate-on-scroll').forEach(element => {
                observer.observe(element);
            });
        });
    </script>
</body>
</html>
