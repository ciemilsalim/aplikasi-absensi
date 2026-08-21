@php
    $teacherScheduleId = null;
    if (auth()->check() && auth()->user()->role === 'teacher') {
        $teacher = auth()->user()->teacher;
        if ($teacher) {
            $dayOfWeekNumber = now()->dayOfWeek;
            $firstSchedule = \App\Models\Schedule::where('day_of_week', $dayOfWeekNumber)
                ->whereHas('teachingAssignment', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->orderBy('start_time', 'asc')
                ->first();
            if ($firstSchedule) {
                $teacherScheduleId = $firstSchedule->id;
            }
        }
    }
@endphp
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

    <!-- Fonts: Plus Jakarta Sans & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

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
                        heading: ['"Plus Jakarta Sans"', 'sans-serif']
                    },
                    colors: {
                        sky: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 
                            400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 
                            800: '#075985', 900: '#0c4a6e', 950: '#082f49'
                        },
                        slate: {
                            850: '#0f172a',
                            900: '#0b1120',
                            950: '#060a12'
                        }
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'card': '0 0 50px -10px rgba(0, 0, 0, 0.08)',
                        'floating': '0 20px 40px -15px rgba(0, 0, 0, 0.15)',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')

    <style>
        body {
            font-family: 'Plus Jakarta Sans', Inter, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .loader-container {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(248, 250, 252, 0.9);
            backdrop-filter: blur(8px);
            transition: opacity 0.4s ease-out, visibility 0.4s;
        }
        .dark .loader-container {
            background-color: rgba(11, 17, 32, 0.9);
        }
        .loader-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
    </style>
</head>

<body class="antialiased font-sans h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col selection:bg-sky-500 selection:text-white">
    <!-- Page Preloader -->
    <div id="page-loader" class="loader-container">
        <div class="flex flex-col items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-sky-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/30 animate-pulse">
                <span class="material-icons text-2xl">qr_code_scanner</span>
            </div>
            <div class="w-24 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-sky-500 rounded-full animate-[ping_1.5s_cubic-bezier(0,0,0.2,1)_infinite]"></div>
            </div>
        </div>
    </div>

    <div x-data="{ mobileMenuOpen: false }" class="flex flex-col min-h-screen">
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 transition-all duration-300">
            @include('layouts.navigation')
        </header>

        <main class="flex-grow pb-24 lg:pb-8">
            @yield('content')
        </main>

        <footer class="w-full bg-white dark:bg-slate-900 border-t border-slate-200/80 dark:border-slate-800 py-6 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-slate-500 dark:text-slate-400 text-xs">
                <p>&copy; {{ date('Y') }} {{ $appName }} - {{ config('app.name') }} v2.0.</p>
                <p class="mt-1">
                    Dikembangkan oleh <a href="https://www.zahradev.online" target="_blank" class="font-bold text-sky-600 dark:text-sky-400 hover:underline">ZahraDev</a>
                    • <a href="{{ route('guide') }}" class="hover:underline font-semibold text-slate-600 dark:text-slate-300">Panduan Pengguna</a>
                    • <a href="{{ route('about') }}" class="hover:underline">Tentang Aplikasi</a>
                </p>
            </div>
        </footer>

        {{-- Bottom Floating Dock for Mobile --}}
        @auth
            <div class="fixed bottom-3 inset-x-3 z-40 lg:hidden">
                <nav class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 shadow-2xl rounded-2xl px-2 py-1 max-w-md mx-auto">
                    <div class="flex justify-around items-center h-14">
                        @if(auth()->user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum']))
                            <a href="{{ route('principal.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('principal.dashboard') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">account_balance</span>
                                <span class="text-[10px] mt-0.5">Eksekutif</span>
                            </a>
                            <a href="{{ route('admin.teaching_journals.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('admin.teaching_journals.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">verified_user</span>
                                <span class="text-[10px] mt-0.5">Supervisi</span>
                            </a>
                            
                            <!-- Middle Floating Action Button -->
                            <div class="relative -mt-5 flex flex-col items-center justify-center">
                                <a href="{{ route('admin.reports.charts') }}"
                                    class="flex items-center justify-center h-12 w-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-lg shadow-indigo-600/30 border-2 border-white dark:border-slate-900 transition-transform active:scale-95">
                                    <span class="material-icons text-2xl">insights</span>
                                </a>
                                <span class="text-[9px] font-bold mt-1 text-slate-500 dark:text-slate-400">Monitoring</span>
                            </div>

                            <a href="{{ route('admin.reports.create') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs(['admin.reports.create', 'admin.reports.teacher.*']) ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">assessment</span>
                                <span class="text-[10px] mt-0.5">Laporan</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors text-slate-500 dark:text-slate-400">
                                <span class="material-icons text-xl">grid_view</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @elseif(in_array(auth()->user()->role, ['admin', 'operator']) || auth()->user()->hasAnyRole(['admin', 'operator', 'satpam']))
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">grid_view</span>
                                <span class="text-[10px] mt-0.5">Dasbor</span>
                            </a>
                            <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors text-slate-500 dark:text-slate-400">
                                <span class="material-icons text-xl">swap_horiz</span>
                                <span class="text-[10px] mt-0.5">SIPADA</span>
                            </a>
                            
                            <!-- Middle Floating Action Button -->
                            <div class="relative -mt-5 flex flex-col items-center justify-center">
                                <a href="{{ route('scanner') }}"
                                    class="flex items-center justify-center h-12 w-12 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-600/30 border-2 border-white dark:border-slate-900 transition-transform active:scale-95">
                                    <span class="material-icons text-2xl">qr_code_scanner</span>
                                </a>
                                <span class="text-[9px] font-bold mt-1 text-slate-500 dark:text-slate-400">Presensi</span>
                            </div>

                            <a href="{{ route('admin.chat.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('admin.chat.*') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">chat</span>
                                <span class="text-[10px] mt-0.5">Pesan</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors text-slate-500 dark:text-slate-400">
                                <span class="material-icons text-xl">more_horiz</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @elseif(auth()->user()->role === 'teacher' || auth()->user()->hasRole('teacher'))
                            <a href="{{ route('teacher.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('teacher.dashboard') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">grid_view</span>
                                <span class="text-[10px] mt-0.5">Dasbor</span>
                            </a>
                            <a href="{{ route('teacher.attendance.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('teacher.attendance.dashboard') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">person_pin</span>
                                <span class="text-[10px] mt-0.5">Absen Guru</span>
                            </a>

                            <!-- Middle Floating Action Button -->
                            <div class="relative -mt-5 flex flex-col items-center justify-center">
                                @if(auth()->user()->teacher?->homeroomClass)
                                    <a href="{{ route('scanner') }}"
                                        class="flex items-center justify-center h-12 w-12 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-600/30 border-2 border-white dark:border-slate-900 transition-transform active:scale-95">
                                        <span class="material-icons text-2xl">qr_code_scanner</span>
                                    </a>
                                @else
                                    @if($teacherScheduleId ?? null)
                                        <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $teacherScheduleId]) }}"
                                            class="flex items-center justify-center h-12 w-12 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-600/30 border-2 border-white dark:border-slate-900 transition-transform active:scale-95">
                                            <span class="material-icons text-2xl">qr_code_scanner</span>
                                        </a>
                                    @else
                                        <a href="{{ route('scanner') }}"
                                            class="flex items-center justify-center h-12 w-12 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-600/30 border-2 border-white dark:border-slate-900 transition-transform active:scale-95">
                                            <span class="material-icons text-2xl">qr_code_scanner</span>
                                        </a>
                                    @endif
                                @endif
                                <span class="text-[9px] font-bold mt-1 text-slate-500 dark:text-slate-400">Presensi</span>
                            </div>

                            <a href="{{ route('teacher.subject.attendance.report') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('teacher.subject.attendance.report') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">assessment</span>
                                <span class="text-[10px] mt-0.5">Rekap</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors text-slate-500 dark:text-slate-400">
                                <span class="material-icons text-xl">more_horiz</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @elseif(auth()->user()->role === 'parent' || auth()->user()->hasRole('parent'))
                            <a href="{{ route('parent.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('parent.dashboard') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">grid_view</span>
                                <span class="text-[10px] mt-0.5">Dasbor</span>
                            </a>
                            <a href="{{ route('chat.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('chat.*') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">chat</span>
                                <span class="text-[10px] mt-0.5">Obrolan</span>
                            </a>
                            <a href="{{ route('parent.leave-requests.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors {{ request()->routeIs('parent.leave-requests.*') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                                <span class="material-icons text-xl">assignment_turned_in</span>
                                <span class="text-[10px] mt-0.5">Izin/Sakit</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-1 w-full transition-colors text-slate-500 dark:text-slate-400">
                                <span class="material-icons text-xl">more_horiz</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @endif
                    </div>
                </nav>
            </div>

            <!-- Mobile Drawer Bottom Sheet -->
            <div x-show="mobileMenuOpen" class="relative z-50 lg:hidden" style="display: none;" x-transition>
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="mobileMenuOpen = false"></div>

                <div class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85vh] flex-col rounded-t-3xl bg-white dark:bg-slate-900 p-6 shadow-2xl transition-transform duration-300"
                     x-show="mobileMenuOpen"
                     x-transition:enter="transform transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transform transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full">
                     
                    <!-- Drag Handle (Standard 36dp x 4dp) -->
                    <div class="mx-auto h-1 w-9 rounded-full bg-slate-300 dark:bg-slate-700 mb-4"></div>

                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-sky-500">grid_view</span>
                            Menu Pintas
                        </h3>
                        <button @click="mobileMenuOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-icons">close</span>
                        </button>
                    </div>

                    <div class="overflow-y-auto pb-8 space-y-5">
                        @if(auth()->user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum']))
                            <div class="text-[11px] font-bold uppercase text-indigo-400 tracking-wider">Modul Eksekutif & Supervisi</div>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('principal.dashboard') }}" class="flex flex-col items-center justify-center p-3.5 bg-indigo-50/70 dark:bg-indigo-950/40 hover:bg-indigo-100 rounded-2xl transition border border-indigo-200/60 dark:border-indigo-900/40 col-span-2">
                                    <span class="material-icons text-indigo-600 dark:text-indigo-400 text-2xl mb-1">account_balance</span>
                                    <span class="text-xs font-bold text-indigo-950 dark:text-indigo-200">Dasbor Eksekutif</span>
                                </a>
                                <a href="{{ route('admin.teaching_journals.index') }}" class="flex flex-col items-center justify-center p-3.5 bg-amber-50/70 dark:bg-amber-950/30 hover:bg-amber-100 rounded-2xl transition border border-amber-200/60 dark:border-amber-900/40 col-span-2">
                                    <span class="material-icons text-amber-500 text-2xl mb-1">verified_user</span>
                                    <span class="text-xs font-bold text-amber-900 dark:text-amber-200">Supervisi Jurnal Guru</span>
                                </a>
                                <a href="{{ route('admin.reports.charts') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 rounded-2xl transition border border-slate-100 dark:border-slate-750">
                                    <span class="material-icons text-indigo-500 text-2xl mb-1">insights</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Grafik Siswa</span>
                                </a>
                                <a href="{{ route('admin.reports.create') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 rounded-2xl transition border border-slate-100 dark:border-slate-750">
                                    <span class="material-icons text-emerald-500 text-2xl mb-1">bar_chart</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Rekap Siswa</span>
                                </a>
                            </div>
                        @elseif(in_array(auth()->user()->role, ['admin', 'operator']) || auth()->user()->hasAnyRole(['admin', 'operator', 'satpam']))
                            <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Pemindai & Layanan</div>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('admin.leave_requests.index') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition border border-slate-100 dark:border-slate-750 col-span-2">
                                    <span class="material-icons text-amber-500 text-2xl mb-1">assignment_turned_in</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Persetujuan Izin Siswa</span>
                                </a>
                                <a href="{{ route('scanner') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition border border-slate-100 dark:border-slate-750">
                                    <span class="material-icons text-sky-500 text-2xl mb-1">qr_code_scanner</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Pemindai Hadir</span>
                                </a>
                                <a href="{{ route('permit.scanner') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition border border-slate-100 dark:border-slate-750">
                                    <span class="material-icons text-indigo-500 text-2xl mb-1">assignment</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Pemindai Izin</span>
                                </a>
                            </div>
                        @elseif(auth()->user()->role === 'teacher' || auth()->user()->hasRole('teacher'))
                            <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Layanan Guru</div>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('teacher.subject.attendance.history') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition border border-slate-100 dark:border-slate-750 col-span-2">
                                    <span class="material-icons text-indigo-500 text-2xl mb-1">history_edu</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Riwayat Presensi Mapel</span>
                                </a>
                                <a href="{{ route('scanner') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition border border-slate-100 dark:border-slate-750">
                                    <span class="material-icons text-sky-500 text-2xl mb-1">qr_code_scanner</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Pemindai Hadir</span>
                                </a>
                                <a href="{{ route('permit.scanner') }}" class="flex flex-col items-center justify-center p-3.5 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition border border-slate-100 dark:border-slate-750">
                                    <span class="material-icons text-indigo-500 text-2xl mb-1">assignment</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Pemindai Izin</span>
                                </a>
                            </div>
                        @endif

                        <!-- Akun & Logout -->
                        <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider pt-2">Pengaturan Akun</div>
                        <div class="space-y-2">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <span class="material-icons text-slate-500 dark:text-slate-400">manage_accounts</span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Edit Profil Saya</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-950/40 transition">
                                    <span class="material-icons">logout</span>
                                    <span class="text-xs font-bold">Keluar / Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endauth
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