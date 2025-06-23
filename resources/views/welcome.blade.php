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
                        'gentle-float': 'gentleFloat 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        gentleFloat: {
                            '0%, 100%': { transform: 'translateY(0) rotate(-3deg)' },
                            '50%': { transform: 'translateY(-20px) rotate(3deg)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        body { @apply bg-white dark:bg-slate-900 font-sans text-slate-700 dark:text-slate-300; }
        .animate-on-scroll { opacity: 0; transition: opacity 0.8s ease-out, transform 0.8s ease-out; transform: translateY(30px); }
        .is-visible { opacity: 1; transform: none; }
        
        /* Gaya untuk Parallax Background */
        .parallax-bg {
            background-image: url('https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=3024&auto=format&fit=crop');
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
    </style>
</head>
<body class="antialiased">
    <div class="flex flex-col min-h-screen">
        {{-- Header dengan Sticky Navigation --}}
        <header x-data="{ atTop: true }" @scroll.window="atTop = (window.pageYOffset < 50)" 
                :class="{ 'bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-md': !atTop }" 
                class="sticky top-0 z-50 transition-all duration-300">
            @include('layouts.navigation')
        </header>

        <main>
            <!-- Hero Section dengan Parallax -->
            <section class="relative isolate pt-14 parallax-bg">
                <div class="absolute inset-0 bg-slate-900/50 dark:bg-slate-900/70"></div>
                
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 sm:py-48 lg:py-56 text-center">
                    <div class="max-w-3xl mx-auto">
                        <div class="flex justify-center mb-8 animate-[fade-in-up_1s_ease-out_forwards]">
                            <div class="relative w-32 h-32">
                                <div class="absolute inset-0 bg-sky-200/50 dark:bg-sky-900/50 rounded-full blur-2xl"></div>
                                <x-application-logo class="relative w-full h-full text-white/40 p-4 animate-gentle-float" />
                            </div>
                        </div>

                        <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.2s;">
                            Sistem Absensi <span class="text-sky-300">Cerdas</span> untuk Sekolah Modern
                        </h1>
                        <p class="mt-6 text-lg text-gray-200 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.4s;">
                            Transformasi cara sekolah Anda mengelola kehadiran. Cepat, akurat, dan terintegrasi untuk semua—Admin, Guru, dan Orang Tua.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-x-6 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.6s;">
                            <a href="{{ route('login') }}" class="rounded-md bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                                Login ke Sistem
                            </a>
                            <a href="#fitur" class="text-sm font-semibold leading-6 text-white">Lihat Fitur <span aria-hidden="true">→</span></a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fitur Unggulan -->
            <section id="fitur" class="bg-white dark:bg-slate-800/50 py-20 sm:py-32">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Semua yang Anda Butuhkan</h2>
                        <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-400">Platform terpadu untuk efisiensi manajemen sekolah.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-10">
                        <div class="feature-card animate-on-scroll text-center">
                             <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Absensi QR Cepat</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Siswa cukup memindai QR code untuk mencatat kehadiran masuk dan pulang.</p>
                        </div>
                        <div class="feature-card animate-on-scroll text-center" style="transition-delay: 150ms">
                            <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Dasbor Statistik</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Pantau persentase kehadiran, keterlambatan, dan rekapitulasi per kelas secara real-time.</p>
                        </div>
                        <div class="feature-card animate-on-scroll text-center" style="transition-delay: 300ms">
                             <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 dark:bg-sky-500/20 mx-auto">
                                <svg class="h-8 w-8 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-medium text-slate-900 dark:text-white">Akses Multi-Peran</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Sistem login terpisah untuk Admin, Guru, dan Orang Tua Wali dengan dasbor masing-masing.</p>
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
