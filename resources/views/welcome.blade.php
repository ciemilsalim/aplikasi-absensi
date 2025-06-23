<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - Sistem Absensi Modern</title>
    
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
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                        'fade-in': 'fadeIn 1s ease-out forwards',
                        'slide-in-right': 'slideInRight 1s ease-out forwards',
                        'gentle-float': 'gentleFloat 6s ease-in-out infinite', // Animasi baru untuk logo
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideInRight: {
                             '0%': { opacity: '0', transform: 'translateX(50px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        gentleFloat: { // Definisi keyframe untuk animasi mengambang
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
        .animated { animation-fill-mode: both; animation-duration: 1s; }
        .animate-on-scroll { opacity: 0; transition: opacity 0.8s ease-out, transform 0.8s ease-out; transform: translateY(30px); }
        .is-visible { opacity: 1; transform: none; }
    </style>
</head>
<body class="antialiased">
    <div class="flex flex-col min-h-screen">
        <header class="absolute inset-x-0 top-0 z-50">
            @include('layouts.navigation')
        </header>

        <main>
            <!-- Hero Section -->
            <section class="relative isolate pt-14">
                {{-- Latar Belakang Baru --}}
                <div class="absolute inset-0 -z-10 h-full w-full">
                    <img src="https://images.unsplash.com/photo-1580582932707-520aed937b7b?q=80&w=2832&auto=format&fit=crop" 
                         alt="Latar belakang lingkungan sekolah yang asri" 
                         class="h-full w-full object-cover"
                         onerror="this.style.display='none'">
                    {{-- Overlay yang disesuaikan untuk keterbacaan teks --}}
                    <div class="absolute inset-0 bg-slate-900/50 dark:bg-slate-900/70"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:flex lg:items-center lg:gap-x-10 lg:py-40">
                    <div class="mx-auto max-w-2xl lg:mx-0 lg:flex-auto">
                        <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight animated" style="animation-name: fadeInUp; animation-delay: 0.2s;">
                            Sistem Absensi Modern Berbasis <span class="text-sky-300">QR Code</span>
                        </h1>
                        <p class="mt-6 text-lg text-gray-200 animated" style="animation-name: fadeInUp; animation-delay: 0.4s;">
                            Memudahkan pencatatan kehadiran siswa secara cepat, akurat, dan real-time. Ucapkan selamat tinggal pada absensi manual.
                        </p>
                        <div class="mt-10 flex items-center gap-x-6 animated" style="animation-name: fadeInUp; animation-delay: 0.6s;">
                            <a href="{{ route('login') }}" class="rounded-md bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                                Mulai Absen
                            </a>
                            {{-- <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-white">Login <span aria-hidden="true">→</span></a> --}}
                        </div>
                    </div>
                    <div class="mt-16 sm:mt-24 lg:mt-0 lg:flex-shrink-0 lg:flex-grow">
                        <div class="relative w-72 h-72 lg:w-96 lg:h-96 mx-auto animated" style="animation-name: fadeIn; animation-delay: 0.5s;">
                            <div class="absolute inset-0 bg-sky-200/50 dark:bg-sky-900/50 rounded-full blur-3xl"></div>
                            <x-application-logo class="relative w-full h-full text-white/30 dark:text-white/30 p-8 animate-gentle-float" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fitur Unggulan -->
            <section class="bg-white dark:bg-slate-800/50 py-20 sm:py-32">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Fitur Unggulan Kami</h2>
                        <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-400">Semua yang Anda butuhkan dalam satu platform.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-10">
                        {{-- KONTEN FITUR 1 --}}
                        <div class="feature-card animate-on-scroll text-center">
                             <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Absensi QR Cepat</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Siswa cukup memindai QR code untuk mencatat kehadiran masuk dan pulang.</p>
                        </div>
                        {{-- KONTEN FITUR 2 --}}
                        <div class="feature-card animate-on-scroll text-center" style="transition-delay: 150ms">
                            <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Dasbor Statistik</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Pantau persentase kehadiran, keterlambatan, dan rekapitulasi per kelas secara real-time.</p>
                        </div>
                        {{-- KONTEN FITUR 3 --}}
                        <div class="feature-card animate-on-scroll text-center" style="transition-delay: 300ms">
                             <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Akses Multi-Peran</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Sistem login terpisah untuk Admin dan Orang Tua Wali dengan dasbor masing-masing.</p>
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
        // JavaScript untuk animasi saat scroll
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
