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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @if (isset($appLogoPath) && $appLogoPath)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $appLogoPath) }}">
    @endif

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <meta name="theme-color" content="#0284c7" />
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    <script>
        const serverDarkModeEnabled = @json($darkModeEnabled ?? false);
        if (localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && serverDarkModeEnabled)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        sky: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e', 950: '#082f49' }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
        <svg class="w-16 h-16 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
    </div>

    <div id="page-content">
        <div x-data="{ sidebarOpen: false, mobileMenuOpen: false }" class="relative h-full">

            <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-72 lg:flex-col">
                @include('layouts.sidebar')
            </div>

            <div class="lg:pl-72">
                <div
                    class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-2 lg:hidden">
                        <x-application-logo class="h-8 w-auto text-sky-600 dark:text-sky-500" />
                        <span class="font-bold text-lg text-slate-800 dark:text-white tracking-tight leading-tight">
                            {{ config('app.name', 'Presensi') }}
                        </span>
                    </div>
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
                    &copy; {{ date('Y') }} {{ config('app.name') }} v2.0.<br>
                    Dikembangkan oleh <a href="https://www.zahradev.online" target="_blank"
                        class="font-semibold text-sky-600 hover:underline">ZahraDev</a>.
                    <a href="{{ route('about') }}" class="hover:underline ml-2">Tentang Aplikasi</a><br>
                    Kontak: <a href="mailto:emilsalimramadhan@gmail.com"
                        class="font-semibold text-sky-600 hover:underline">emilsalimramadhan@gmail.com</a>
                </div>
            </footer>

            {{-- PERBAIKAN: Bottom Navigation Bar dipindahkan ke sini --}}
            @auth
                <nav class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 z-50 px-2 lg:hidden">
                    <div class="flex justify-around items-center max-w-7xl mx-auto h-16 pb-1">
                        @if(in_array(auth()->user()->role, ['admin', 'operator']))
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">home</span>
                                <span class="text-[10px] mt-0.5">Beranda</span>
                            </a>
                            <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">swap_horiz</span>
                                <span class="text-[10px] mt-0.5">SIPADA</span>
                            </a>
                            
                            <!-- Tombol Scan QR Tengah (Diperbesar) -->
                            <div class="relative -mt-6 flex flex-col items-center justify-center w-full">
                                <a href="{{ route('scanner') }}"
                                    class="flex items-center justify-center h-14 w-14 rounded-full bg-sky-600 hover:bg-sky-700 text-white shadow-lg border-4 border-white dark:border-slate-800 transition transform hover:scale-105 active:scale-95">
                                    <span class="material-icons text-2xl">qr_code_scanner</span>
                                </a>
                                <span class="text-[9px] font-semibold mt-1 text-slate-500 dark:text-gray-400">Scan QR</span>
                            </div>

                            <a href="{{ route('admin.chat.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">chat</span>
                                <span class="text-[10px] mt-0.5">Pesan</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200 text-gray-500 dark:text-gray-400">
                                <span class="material-icons">menu</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @elseif(auth()->user()->role === 'teacher')
                            <a href="{{ route('teacher.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">home</span>
                                <span class="text-[10px] mt-0.5">Beranda</span>
                            </a>
                            <a href="{{ route('teacher.attendance.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">person_pin</span>
                                <span class="text-[10px] mt-0.5">Absen Saya</span>
                            </a>

                            <!-- Tombol Scan QR Tengah (Diperbesar) -->
                            <div class="relative -mt-6 flex flex-col items-center justify-center w-full">
                                @if(auth()->user()->teacher?->homeroomClass)
                                    <a href="{{ route('scanner') }}"
                                        class="flex items-center justify-center h-14 w-14 rounded-full bg-sky-600 hover:bg-sky-700 text-white shadow-lg border-4 border-white dark:border-slate-800 transition transform hover:scale-105 active:scale-95">
                                        <span class="material-icons text-2xl">qr_code_scanner</span>
                                    </a>
                                @else
                                    @if($teacherScheduleId)
                                        <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $teacherScheduleId]) }}"
                                            class="flex items-center justify-center h-14 w-14 rounded-full bg-sky-600 hover:bg-sky-700 text-white shadow-lg border-4 border-white dark:border-slate-800 transition transform hover:scale-105 active:scale-95">
                                            <span class="material-icons text-2xl">qr_code_scanner</span>
                                        </a>
                                    @else
                                        <button @click="alert('Tidak ada jadwal mengajar aktif hari ini untuk melakukan presensi mata pelajaran.')"
                                            class="flex items-center justify-center h-14 w-14 rounded-full bg-gray-400 dark:bg-slate-700 text-white shadow-lg border-4 border-white dark:border-slate-800 transition">
                                            <span class="material-icons text-2xl">qr_code_scanner</span>
                                        </button>
                                    @endif
                                @endif
                                <span class="text-[9px] font-semibold mt-1 text-slate-500 dark:text-gray-400">Scan QR</span>
                            </div>

                            <a href="{{ route('teacher.subject.attendance.report') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">assessment</span>
                                <span class="text-[10px] mt-0.5">Rekap Mapel</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200 text-gray-500 dark:text-gray-400">
                                <span class="material-icons">menu</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @elseif(auth()->user()->role === 'parent')
                            <a href="{{ route('parent.dashboard') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">home</span>
                                <span class="text-[10px] mt-0.5">Beranda</span>
                            </a>
                            <a href="{{ route('parent.leave-requests.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">assignment_turned_in</span>
                                <span class="text-[10px] mt-0.5">Izin/Sakit</span>
                            </a>
                            <a href="{{ route('chat.index') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">chat</span>
                                <span class="text-[10px] mt-0.5">Obrolan</span>
                            </a>
                            <a href="{{ route('profile.edit') }}"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200">
                                <span class="material-icons">account_circle</span>
                                <span class="text-[10px] mt-0.5">Profil</span>
                            </a>
                            <button @click="mobileMenuOpen = true"
                                class="nav-item flex flex-col items-center justify-center text-center py-2 w-full transition-colors duration-200 text-gray-500 dark:text-gray-400">
                                <span class="material-icons">menu</span>
                                <span class="text-[10px] mt-0.5">Lainnya</span>
                            </button>
                        @endif
                    </div>
                </nav>

                <!-- Mobile Menu Bottom Sheet -->
                <div x-show="mobileMenuOpen" class="relative z-50 lg:hidden" style="display: none;" x-transition>
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-900/80 transition-opacity" @click="mobileMenuOpen = false"></div>

                    <!-- Bottom Sheet Container -->
                    <div class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85vh] flex-col rounded-t-3xl bg-white dark:bg-slate-800 p-6 shadow-2xl transition-transform duration-300"
                         x-show="mobileMenuOpen"
                         x-transition:enter="transform transition ease-out duration-300"
                         x-transition:enter-start="translate-y-full"
                         x-transition:enter-end="translate-y-0"
                         x-transition:leave="transform transition ease-in duration-200"
                         x-transition:leave-start="translate-y-0"
                         x-transition:leave-end="translate-y-full">
                         
                         <!-- Drag handle indicator -->
                         <div class="mx-auto h-1.5 w-12 rounded-full bg-gray-300 dark:bg-gray-600 mb-5"></div>

                         <!-- Title / Close -->
                         <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-100 dark:border-slate-700">
                             <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                 <span class="material-icons text-sky-500">grid_view</span>
                                 Menu Aplikasi
                             </h3>
                             <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                 <span class="material-icons">close</span>
                             </button>
                         </div>

                         <!-- Scrollable items -->
                         <div class="overflow-y-auto pb-8 space-y-6">
                             @if(in_array(auth()->user()->role, ['admin', 'operator']))
                                 <div class="text-xs font-bold uppercase text-gray-400 tracking-wider">Pemindai & Laporan</div>
                                 <div class="grid grid-cols-2 gap-3">
                                     <a href="{{ route('admin.leave_requests.index') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition col-span-2">
                                         <span class="material-icons text-amber-600 dark:text-amber-400 text-3xl mb-1.5">assignment_turned_in</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Persetujuan Izin Siswa</span>
                                     </a>
                                     <a href="{{ route('scanner') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                         <span class="material-icons text-sky-600 dark:text-sky-400 text-3xl mb-1.5">qr_code_scanner</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pemindai Hadir</span>
                                     </a>
                                     <a href="{{ route('permit.scanner') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                         <span class="material-icons text-indigo-600 dark:text-indigo-400 text-3xl mb-1.5">assignment</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pemindai Izin</span>
                                     </a>
                                     <a href="{{ route('admin.reports.create') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                         <span class="material-icons text-emerald-600 dark:text-emerald-400 text-3xl mb-1.5">bar_chart</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Laporan Siswa</span>
                                     </a>
                                     <a href="{{ route('admin.reports.teacher.index') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                         <span class="material-icons text-teal-600 dark:text-teal-400 text-3xl mb-1.5">analytics</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Laporan Guru</span>
                                     </a>
                                 </div>
                             @elseif(auth()->user()->role === 'teacher')
                                  <div class="text-xs font-bold uppercase text-gray-400 tracking-wider">Pemindai & Menu Guru</div>
                                  <div class="grid grid-cols-2 gap-3">
                                      <a href="{{ route('sso.lms') }}" class="flex flex-col items-center justify-center p-4 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 rounded-2xl transition col-span-2 text-indigo-600 dark:text-indigo-400">
                                          <span class="material-icons text-indigo-600 dark:text-indigo-400 text-3xl mb-1.5">school</span>
                                          <span class="text-xs font-bold">LMS Mokopani</span>
                                      </a>
                                      <a href="{{ route('teacher.subject.attendance.history') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition col-span-2">
                                          <span class="material-icons text-indigo-600 dark:text-indigo-400 text-3xl mb-1.5">history_edu</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Riwayat Presensi Mapel</span>
                                     </a>
                                     <a href="{{ route('scanner') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                         <span class="material-icons text-sky-600 dark:text-sky-400 text-3xl mb-1.5">qr_code_scanner</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pemindai Hadir</span>
                                     </a>
                                     <a href="{{ route('permit.scanner') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                         <span class="material-icons text-indigo-600 dark:text-indigo-400 text-3xl mb-1.5">assignment</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pemindai Izin</span>
                                     </a>
                                     @if(auth()->user()->teacher?->homeroomClass)
                                         <a href="{{ route('teacher.leave_requests.index') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition col-span-2">
                                             <span class="material-icons text-amber-600 dark:text-amber-400 text-3xl mb-1.5">assignment_turned_in</span>
                                             <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Persetujuan Izin Wali Kelas</span>
                                         </a>
                                         <a href="{{ route('teacher.attendance.history') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                             <span class="material-icons text-blue-600 dark:text-blue-400 text-3xl mb-1.5">history</span>
                                             <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Riwayat Kehadiran</span>
                                         </a>
                                         <a href="{{ route('chat.index') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition">
                                             <span class="material-icons text-pink-600 dark:text-pink-400 text-3xl mb-1.5">chat</span>
                                             <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Obrolan Ortu</span>
                                         </a>
                                     @endif
                                     @if(auth()->user()->teacher?->coachingExtracurriculars()->exists())
                                         <a href="{{ route('teacher.extracurricular-attendance.index') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition col-span-2">
                                             <span class="material-icons text-rose-600 dark:text-rose-400 text-3xl mb-1.5">star</span>
                                             <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Absensi Ekskul</span>
                                         </a>
                                     @endif
                                 </div>
                             @elseif(auth()->user()->role === 'parent')
                                 <div class="text-xs font-bold uppercase text-gray-400 tracking-wider">Lainnya</div>
                                 <div class="grid grid-cols-2 gap-3">
                                     <a href="{{ route('parent.dashboard') }}#ekskul" @click="mobileMenuOpen = false" class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 rounded-2xl transition col-span-2">
                                         <span class="material-icons text-purple-600 dark:text-purple-400 text-3xl mb-1.5">star</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Kegiatan Ekstrakurikuler</span>
                                     </a>
                                 </div>
                             @endif

                             <!-- Akun & Keluar -->
                             <div class="text-xs font-bold uppercase text-gray-400 tracking-wider pt-2">Pengaturan Akun</div>
                             <div class="space-y-2">
                                 <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                     <span class="material-icons text-slate-600 dark:text-slate-400">manage_accounts</span>
                                     <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Edit Profil Saya</span>
                                 </a>
                                 @if(auth()->user()->role === 'admin')
                                     <a href="{{ route('admin.settings.appearance') }}" class="flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                         <span class="material-icons text-slate-600 dark:text-slate-400">palette</span>
                                         <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tampilan & Logo</span>
                                     </a>
                                 @endif
                                 <form method="POST" action="{{ route('logout') }}">
                                     @csrf
                                     <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-100 dark:hover:bg-red-950/40 transition">
                                         <span class="material-icons">logout</span>
                                         <span class="text-sm font-semibold">Keluar / Logout</span>
                                     </button>
                                 </form>
                             </div>
                         </div>
                    </div>
                </div>
            @endauth
        </div>
    </div>

    <div x-data="{ show: false }" @scroll.window="show = (window.pageYOffset > 300)"
        class="fixed bottom-5 right-5 z-50 hidden lg:block">
        <button x-show="show" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" x-transition
            class="p-3 rounded-full bg-sky-600 text-white shadow-lg hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500"
            aria-label="Kembali ke atas" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="w-6 w-6">
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
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const navItems = document.querySelectorAll('.nav-item');
                const currentPath = window.location.pathname;
                navItems.forEach(item => {
                    const hrefAttr = item.getAttribute('href');
                    if (!hrefAttr) return;
                    try {
                        let itemPath = '';
                        if (hrefAttr.startsWith('http')) {
                            itemPath = new URL(hrefAttr).pathname;
                        } else {
                            itemPath = new URL(hrefAttr, window.location.origin).pathname;
                        }
                        
                        if (currentPath === itemPath || (itemPath !== '/' && currentPath.startsWith(itemPath))) {
                            item.classList.add('text-sky-500', 'dark:text-sky-400');
                            item.classList.remove('text-gray-500', 'dark:text-gray-400');
                        } else {
                            item.classList.add('text-gray-500', 'dark:text-gray-400');
                            item.classList.remove('text-sky-500', 'dark:text-sky-400');
                        }
                    } catch (e) {
                        // Ignored
                    }
                });
            });
        </script>
    @endauth
</body>

</html>