@php
    $teacherScheduleId = null;
    $isPrincipalUser = false;
    $pendingJournalsCount = 0;
    $pendingLeaveRequestsCount = 0;
    $isChatConversation = (
        request()->routeIs(['admin.chat.*', 'chat.*']) && 
        (request()->filled('selectedParent') || request()->route('selectedParent') || request()->route('conversation') || (isset($selectedParent) && $selectedParent && $selectedParent->exists) || (isset($activeConversation) && $activeConversation))
    );
    
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum', 'waka_kurikulum', 'waka kurikulum'])) {
            $isPrincipalUser = true;
            if (\Illuminate\Support\Facades\Schema::hasTable('teaching_journals') && \Illuminate\Support\Facades\Schema::hasColumn('teaching_journals', 'is_verified')) {
                try {
                    $pendingJournalsCount = \App\Models\TeachingJournal::where('is_verified', false)->count();
                } catch (\Throwable $e) {
                    $pendingJournalsCount = 0;
                }
            }
        } elseif ($user->hasAnyRole(['admin', 'operator', 'satpam'])) {
            if (\Illuminate\Support\Facades\Schema::hasTable('leave_requests')) {
                try {
                    $pendingLeaveRequestsCount = \App\Models\LeaveRequest::where('status', 'menunggu')->count();
                } catch (\Throwable $e) {
                    $pendingLeaveRequestsCount = 0;
                }
            }
        } elseif ($user->role === 'teacher' || $user->hasRole('teacher')) {
            $teacher = $user->teacher;
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
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Presensi') }} - Sistem Presensi Digital</title>

    @if (isset($appLogoPath) && $appLogoPath)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $appLogoPath) }}">
    @endif

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <meta name="theme-color" content="#0284c7" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    <script>
        var serverDarkModeEnabled = @json($darkModeEnabled ?? false);
        if (localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && serverDarkModeEnabled)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <!-- Tailwind CDN with Extended Design Tokens -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
                        display: ['"Plus Jakarta Sans"', 'sans-serif']
                    },
                    colors: {
                        sky: { 
                            50: '#f0f9ff', 
                            100: '#e0f2fe', 
                            200: '#bae6fd', 
                            300: '#7dd3fc', 
                            400: '#38bdf8', 
                            500: '#0ea5e9', 
                            600: '#0284c7', 
                            700: '#0369a1', 
                            800: '#075985', 
                            900: '#0c4a6e', 
                            950: '#082f49' 
                        },
                        slate: {
                            750: '#293548',
                            850: '#172033',
                            900: '#0f172a',
                            950: '#0a0f1d'
                        }
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.04)',
                        'glow': '0 0 20px -5px rgba(2, 132, 199, 0.4)',
                        'glow-lg': '0 0 35px -5px rgba(2, 132, 199, 0.5)'
                    },
                    borderRadius: {
                        'xl': '0.875rem',
                        '2xl': '1.25rem',
                        '3xl': '1.75rem'
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')

    <style>
        /* Custom subtle scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.6);
        }
        .dark ::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.4);
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.7);
        }

        /* Page Loader & Animations */
        .loader-container {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            transition: opacity 0.4s ease-out, visibility 0.4s ease-out;
        }
        .dark .loader-container {
            background: #0f172a;
        }
        .loader-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .content-wrapper {
            opacity: 0;
            transition: opacity 0.35s ease-out;
        }
        .content-visible {
            opacity: 1;
        }

        /* Custom utility classes */
        .glass-header {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .dock-shadow {
            box-shadow: 0 -4px 20px -2px rgba(0, 0, 0, 0.06), 0 -2px 6px -1px rgba(0, 0, 0, 0.04);
        }
        .dark .dock-shadow {
            box-shadow: 0 -4px 25px -2px rgba(0, 0, 0, 0.35), 0 -2px 8px -1px rgba(0, 0, 0, 0.25);
        }

        /* Skeleton Loading Shimmer Animation */
        .skeleton {
            background: linear-gradient(
                90deg,
                rgba(226, 232, 240, 0.6) 25%,
                rgba(241, 245, 249, 0.95) 50%,
                rgba(226, 232, 240, 0.6) 75%
            );
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s infinite ease-in-out;
        }
        .dark .skeleton {
            background: linear-gradient(
                90deg,
                rgba(30, 41, 59, 0.6) 25%,
                rgba(51, 65, 85, 0.9) 50%,
                rgba(30, 41, 59, 0.6) 75%
            );
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s infinite ease-in-out;
        }
        @keyframes skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>

<body class="h-full antialiased bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans selection:bg-sky-500 selection:text-white {{ $isChatConversation ? 'p-0 pb-0 overflow-hidden h-[100dvh]' : 'pb-20 lg:pb-0' }}">
    
    <!-- Page Loader -->
    <div id="page-loader" class="loader-container">
        <div class="flex flex-col items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-white/90 dark:bg-slate-850/90 shadow-xl shadow-slate-200/50 dark:shadow-slate-950/50 flex items-center justify-center border border-slate-200/60 dark:border-slate-700/60 backdrop-blur-sm">
                <svg class="w-6 h-6 animate-spin text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <p class="text-[11px] font-bold tracking-wider text-slate-500 dark:text-slate-400 uppercase">Memuat Aplikasi...</p>
        </div>
    </div>

    <div id="page-content" class="content-wrapper {{ $isChatConversation ? 'h-full overflow-hidden' : '' }}">
        <div x-data="{ 
            sidebarOpen: false, 
            mobileMenuOpen: false,
            showMobileLogoutConfirm: false,
            showNoScheduleModal: false,
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
            }
        }" class="relative {{ $isChatConversation ? 'h-full max-h-full overflow-hidden' : 'min-h-screen' }} flex flex-col">


            <!-- Sidebar Desktop (Fixed Left) -->
            <aside class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:flex-col transition-all duration-300 ease-in-out"
                 :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'">
                @include('layouts.sidebar')
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out {{ $isChatConversation ? 'min-h-0' : '' }}" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
                
                    <!-- Sticky Top Navigation Bar -->
                    <header class="{{ $isChatConversation ? 'hidden lg:flex' : 'flex' }} sticky top-0 z-30 h-16 shrink-0 items-center justify-between gap-x-4 border-b border-slate-200/80 dark:border-slate-800/90 bg-white/90 dark:bg-slate-900/90 glass-header px-4 sm:px-6 lg:px-8 shadow-xs">
                        
                        <!-- Mobile Brand Logo -->
                        <div class="flex items-center gap-3 lg:hidden">
                            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex items-center gap-2.5">
                                <x-application-logo class="h-8 w-auto text-sky-600 dark:text-sky-500" />
                                <div class="leading-tight">
                                    <span class="font-bold text-base text-slate-900 dark:text-white tracking-tight block">
                                        {{ config('app.name', 'Presensi') }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block -mt-0.5 truncate max-w-[150px]">
                                        {{ $appName ?? 'Portal Absensi' }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <!-- Desktop Sidebar Toggle Button -->
                        <button @click="toggleSidebar()" type="button" 
                                class="hidden lg:inline-flex items-center justify-center w-10 h-10 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                :title="sidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'">
                            <span class="material-icons text-2xl" x-text="sidebarCollapsed ? 'menu_open' : 'menu'"></span >
                        </button>

                        <!-- Topbar Profile, Academic Period & Utilities -->
                        <div class="flex flex-1 gap-x-3 sm:gap-x-4 self-stretch items-center justify-end">
                            @include('layouts.topbar-profile')
                        </div>
                    </header>

                    <!-- Page Main Container -->
                    <main class="flex-1 {{ $isChatConversation ? 'p-0 sm:p-0 pb-0 lg:pb-0 h-[100dvh] lg:h-[calc(100vh-4rem)] min-h-0 overflow-hidden flex flex-col' : 'py-5 sm:py-8 pb-28 lg:pb-8' }}">
                        <div class="{{ $isChatConversation ? 'w-full h-full p-0 max-w-full flex flex-col flex-1 min-h-0 overflow-hidden' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6' }}">
                            @if (isset($header) && !$isChatConversation)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-200/60 dark:border-slate-800/60">
                                    {{ $header }}
                                </div>
                            @endif

                            {{ $slot }}
                        </div>
                    </main>

                    <!-- Footer Desktop (Hidden on Mobile or in Chat View) -->
                    <footer class="mt-auto hidden {{ $isChatConversation ? '' : 'lg:block' }} border-t border-slate-200/80 dark:border-slate-800/80 bg-white/50 dark:bg-slate-900/50 py-4 px-4 sm:px-6 text-center text-xs text-slate-500 dark:text-slate-400">
                        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 justify-center">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ config('app.name', 'Presensi') }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300">v2.5</span>
                                <span>&copy; {{ date('Y') }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px]">
                                <a href="{{ route('guide') }}" class="hover:text-sky-600 dark:hover:text-sky-400 font-semibold transition-colors flex items-center gap-1">
                                    <span class="material-icons text-xs text-sky-500">auto_stories</span>
                                    <span>Panduan Pengguna</span>
                                </a>
                                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                <a href="{{ route('about') }}" class="hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Tentang Sistem</a>
                                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                <a href="https://www.zahradev.online" target="_blank" class="hover:text-sky-600 dark:hover:text-sky-400 font-medium transition-colors">ZahraDev</a>
                            </div>
                        </div>
                    </footer>
                </div>

                <!-- Mobile Floating Bottom Navigation Dock (Hidden on Chat Conversation) -->
                @auth
                    @if(!$isChatConversation)
                    <nav class="fixed bottom-0 inset-x-0 bg-white/95 dark:bg-slate-900/95 glass-header border-t border-slate-200/90 dark:border-slate-800 z-40 px-2 sm:px-4 lg:hidden dock-shadow safe-area-pb">
                    <div class="flex justify-around items-center max-w-lg mx-auto h-16">
                        
                        <!-- Role: Kepala Sekolah & Wakasek Kurikulum (Executive) -->
                        @if(auth()->user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum', 'waka_kurikulum', 'waka kurikulum']))
                            @php $isPrincipalDash = request()->routeIs('principal.dashboard'); @endphp
                            <a href="{{ route('principal.dashboard') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isPrincipalDash ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400' }} transition-all duration-200">
                                <div class="relative">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">account_balance</span>
                                    @if($isPrincipalDash)
                                        <span class="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] {{ $isPrincipalDash ? 'font-bold' : 'font-medium' }} mt-0.5">Eksekutif</span>
                            </a>

                            @php $isPrincipalJournal = request()->routeIs('admin.teaching_journals.*'); @endphp
                            <a href="{{ route('admin.teaching_journals.index') }}"
                                class="nav-item group relative flex flex-col items-center justify-center py-1 w-full {{ $isPrincipalJournal ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400' }} transition-all duration-200">
                                <div class="relative">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">verified_user</span>
                                    @if(isset($pendingJournalsCount) && $pendingJournalsCount > 0)
                                        <span class="absolute -top-1 -right-2 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-amber-500 text-[9px] font-black text-slate-950 shadow-xs animate-pulse">
                                            {{ $pendingJournalsCount }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] {{ $isPrincipalJournal ? 'font-bold' : 'font-medium' }} mt-0.5">Supervisi</span>
                            </a>

                            <!-- Elevated Executive Monitoring Button -->
                            @php $isMonitoringChart = request()->routeIs('admin.reports.charts'); @endphp
                            <div class="relative -mt-6 flex flex-col items-center justify-center">
                                <a href="{{ route('admin.reports.charts') }}"
                                    class="flex items-center justify-center h-14 w-14 rounded-full bg-gradient-to-tr from-indigo-600 via-indigo-700 to-purple-600 text-white shadow-lg shadow-indigo-600/35 border-4 border-slate-50 dark:border-slate-950 transition-all transform hover:scale-105 active:scale-95"
                                    title="Monitoring Presensi Terpadu">
                                    <span class="material-icons text-2xl">insights</span>
                                </a>
                                <span class="text-[9px] font-bold mt-1 text-slate-700 dark:text-slate-300">Monitoring</span>
                            </div>

                            @php $isPrincipalReport = request()->routeIs(['admin.reports.create', 'admin.reports.teacher.*', 'admin.reports.generate']); @endphp
                            <a href="{{ route('admin.reports.create') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isPrincipalReport ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400' }} transition-all duration-200">
                                <div class="relative">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">assessment</span>
                                    @if($isPrincipalReport)
                                        <span class="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] {{ $isPrincipalReport ? 'font-bold' : 'font-medium' }} mt-0.5">Laporan</span>
                            </a>

                            <button @click="mobileMenuOpen = true"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">grid_view</span>
                                <span class="text-[10px] font-medium mt-0.5">Lainnya</span>
                            </button>

                        <!-- Role: Admin / Operator / Satpam -->
                        @elseif(in_array(auth()->user()->role, ['admin', 'operator', 'satpam']) || auth()->user()->hasAnyRole(['admin', 'operator', 'satpam']))
                            @php $isAdminDash = request()->routeIs('admin.dashboard'); @endphp
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full min-h-[44px] {{ $isAdminDash ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">dashboard</span>
                                <span class="text-[10px] {{ $isAdminDash ? 'font-bold' : 'font-medium' }} mt-0.5">Dasbor</span>
                            </a>
                            
                            @php $isLeaveReq = request()->routeIs('admin.leave_requests.*'); @endphp
                            <a href="{{ route('admin.leave_requests.index') }}"
                                class="nav-item group relative flex flex-col items-center justify-center py-1 w-full min-h-[44px] {{ $isLeaveReq ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <div class="relative">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">assignment_turned_in</span>
                                    @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                                        <span class="absolute -top-1 -right-2 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-xs animate-pulse">{{ $pendingLeaveRequestsCount }}</span>
                                    @endif
                                </div>
                                <span class="text-[10px] {{ $isLeaveReq ? 'font-bold' : 'font-medium' }} mt-0.5">Izin Siswa</span>
                            </a>
                            
                            <!-- Elevated Scan QR Button -->
                            <div class="relative -mt-6 flex flex-col items-center justify-center">
                                <a href="{{ route('scanner') }}"
                                    class="flex items-center justify-center h-14 w-14 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-600/30 border-4 border-slate-50 dark:border-slate-950 transition-all transform hover:scale-105 active:scale-95"
                                    title="Pemindai Kehadiran QR">
                                    <span class="material-icons text-2xl">qr_code_scanner</span>
                                </a>
                                <span class="text-[9px] font-bold mt-1 text-slate-700 dark:text-slate-300">Scan QR</span>
                            </div>

                            @php $isAdminChat = request()->routeIs('admin.chat.*'); @endphp
                            <a href="{{ route('admin.chat.index') }}"
                                class="nav-item group relative flex flex-col items-center justify-center py-1 w-full min-h-[44px] {{ $isAdminChat ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <div class="relative">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">chat</span>
                                    @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                        <span class="absolute top-0 right-1/4 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-xs">{{ $totalUnreadMessagesCount }}</span>
                                    @endif
                                </div>
                                <span class="text-[10px] {{ $isAdminChat ? 'font-bold' : 'font-medium' }} mt-0.5">Pesan</span>
                            </a>

                            <button @click="mobileMenuOpen = true"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full min-h-[44px] text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">grid_view</span>
                                <span class="text-[10px] font-medium mt-0.5">Lainnya</span>
                            </button>

                        <!-- Role: Guru (Teacher) -->
                        @elseif(auth()->user()->role === 'teacher' || auth()->user()->hasRole('teacher'))
                            @php $isTeacherDash = request()->routeIs('teacher.dashboard'); @endphp
                            <a href="{{ route('teacher.dashboard') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isTeacherDash ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">dashboard</span>
                                <span class="text-[10px] {{ $isTeacherDash ? 'font-bold' : 'font-medium' }} mt-0.5">Dasbor</span>
                            </a>

                            @php $isTeacherAtt = request()->routeIs(['teacher.attendance.dashboard', 'teacher.attendance.scanner']); @endphp
                            <a href="{{ route('teacher.attendance.dashboard') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isTeacherAtt ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">person_pin</span>
                                <span class="text-[10px] {{ $isTeacherAtt ? 'font-bold' : 'font-medium' }} mt-0.5">Presensi</span>
                            </a>

                            <!-- Elevated Scan QR Button -->
                            <div class="relative -mt-6 flex flex-col items-center justify-center">
                                @if(auth()->user()->teacher?->homeroomClass)
                                    <a href="{{ route('scanner') }}"
                                        class="flex items-center justify-center h-14 w-14 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-600/30 border-4 border-slate-50 dark:border-slate-950 transition-all transform hover:scale-105 active:scale-95">
                                        <span class="material-icons text-2xl">qr_code_scanner</span>
                                    </a>
                                @else
                                    @if(isset($teacherScheduleId) && $teacherScheduleId)
                                        <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $teacherScheduleId]) }}"
                                            class="flex items-center justify-center h-14 w-14 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-600/30 border-4 border-slate-50 dark:border-slate-950 transition-all transform hover:scale-105 active:scale-95">
                                            <span class="material-icons text-2xl">qr_code_scanner</span>
                                        </a>
                                    @else
                                        <button @click="window.dispatchEvent(new CustomEvent('open-no-schedule-modal'))"
                                            type="button"
                                            class="flex items-center justify-center h-14 w-14 rounded-full bg-slate-400 dark:bg-slate-700 hover:bg-slate-500 text-white shadow-lg border-4 border-slate-50 dark:border-slate-950 transition-all active:scale-95 cursor-pointer"
                                            title="Scan QR Presensi Mapel">
                                            <span class="material-icons text-2xl">qr_code_scanner</span>
                                        </button>
                                    @endif
                                @endif
                                <span class="text-[9px] font-bold mt-1 text-slate-700 dark:text-slate-300">Scan QR</span>
                            </div>

                            @if(auth()->user()->teacher?->homeroomClass)
                                @php $isHomeroomHistory = request()->routeIs(['teacher.attendance.history', 'teacher.attendance.print']); @endphp
                                <a href="{{ route('teacher.attendance.history') }}"
                                    class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isHomeroomHistory ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">groups</span>
                                    <span class="text-[10px] {{ $isHomeroomHistory ? 'font-bold' : 'font-medium' }} mt-0.5">Siswa</span>
                                </a>
                            @else
                                @php 
                                    $isSubjectRep = request()->routeIs([
                                        'teacher.subject.attendance.report', 
                                        'teacher.subject.attendance.preview', 
                                        'teacher.subject.attendance.print', 
                                        'teacher.subject.attendance.charts',
                                        'teacher.extracurricular-attendance.report',
                                        'teacher.extracurricular-attendance.print'
                                    ]); 
                                @endphp
                                <a href="{{ route('teacher.subject.attendance.report') }}"
                                    class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isSubjectRep ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                    <span class="material-icons text-2xl group-hover:scale-110 transition-transform">assessment</span>
                                    <span class="text-[10px] {{ $isSubjectRep ? 'font-bold' : 'font-medium' }} mt-0.5">Rekap</span>
                                </a>
                            @endif

                            <button @click="mobileMenuOpen = true"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">more_horiz</span>
                                <span class="text-[10px] font-medium mt-0.5">Lainnya</span>
                            </button>

                        <!-- Role: Orang Tua (Parent) -->
                        @elseif(auth()->user()->role === 'parent' || auth()->user()->hasRole('parent'))
                            @php $isParentDash = request()->routeIs('parent.dashboard'); @endphp
                            <a href="{{ route('parent.dashboard') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isParentDash ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">dashboard</span>
                                <span class="text-[10px] {{ $isParentDash ? 'font-bold' : 'font-medium' }} mt-0.5">Dasbor</span>
                            </a>

                            @php $isParentLeave = request()->routeIs('parent.leave-requests.*'); @endphp
                            <a href="{{ route('parent.leave-requests.index') }}"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full {{ $isParentLeave ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">assignment_turned_in</span>
                                <span class="text-[10px] {{ $isParentLeave ? 'font-bold' : 'font-medium' }} mt-0.5">Izin/Sakit</span>
                            </a>

                            <!-- Elevated Action: Ajukan Izin Cepat -->
                            @if(auth()->user()->parent && auth()->user()->parent->students()->exists())
                            <div class="relative -mt-6 flex flex-col items-center justify-center">
                                <a href="{{ route('parent.leave-requests.create') }}"
                                    class="flex items-center justify-center h-14 w-14 rounded-full bg-gradient-to-tr from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30 border-4 border-slate-50 dark:border-slate-950 transition-all transform hover:scale-105 active:scale-95">
                                    <span class="material-icons text-2xl">add</span>
                                </a>
                                <span class="text-[9px] font-bold mt-1 text-slate-700 dark:text-slate-300">Ajukan Izin</span>
                            </div>
                            @endif

                            @php $isParentChat = request()->routeIs('chat.*'); @endphp
                            <a href="{{ route('chat.index') }}"
                                class="nav-item group relative flex flex-col items-center justify-center py-1 w-full {{ $isParentChat ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400' }} transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">chat</span>
                                @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                    <span class="absolute top-0 right-1/4 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-xs">{{ $totalUnreadMessagesCount }}</span>
                                @endif
                                <span class="text-[10px] {{ $isParentChat ? 'font-bold' : 'font-medium' }} mt-0.5">Obrolan</span>
                            </a>

                            <button @click="mobileMenuOpen = true"
                                class="nav-item group flex flex-col items-center justify-center py-1 w-full text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-all duration-200">
                                <span class="material-icons text-2xl group-hover:scale-110 transition-transform">more_horiz</span>
                                <span class="text-[10px] font-medium mt-0.5">Lainnya</span>
                            </button>
                        @endif

                    </div>
                </nav>
                @endif

                <!-- Modern Mobile Menu Bottom Sheet Drawer -->
                <div x-show="mobileMenuOpen" class="relative z-50 lg:hidden" style="display: none;" x-transition>
                    
                    <!-- Backdrop Overlay -->
                    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="mobileMenuOpen = false"></div>

                    <!-- Bottom Sheet Card -->
                    <div class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85vh] flex-col rounded-t-3xl bg-white dark:bg-slate-900 p-6 shadow-2xl border-t border-slate-200 dark:border-slate-800 transition-transform duration-300"
                         x-show="mobileMenuOpen"
                         x-transition:enter="transform transition ease-out duration-300"
                         x-transition:enter-start="translate-y-full"
                         x-transition:enter-end="translate-y-0"
                         x-transition:leave="transform transition ease-in duration-200"
                         x-transition:leave-start="translate-y-0"
                         x-transition:leave-end="translate-y-full">
                         
                         <!-- Drag Handle (Standard 36dp x 4dp) -->
                         <div class="mx-auto h-1 w-9 rounded-full bg-slate-300 dark:bg-slate-700 mb-4"></div>

                         <!-- Header -->
                         <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                             <div class="flex items-center gap-2.5">
                                 <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                                     <span class="material-icons text-lg">grid_view</span>
                                 </div>
                                 <div>
                                     <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">Menu Navigasi</h3>
                                     <p class="text-[11px] text-slate-500 dark:text-slate-400">Pilih modul yang ingin diakses</p>
                                 </div>
                             </div>
                             <button @click="mobileMenuOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                 <span class="material-icons text-xl">close</span>
                             </button>
                         </div>

                         <!-- Menu Grid Items -->
                         <div class="overflow-y-auto pb-8 space-y-5 no-scrollbar">
                             
                             <!-- Kepala Sekolah / Executive Section -->
                             @if(auth()->user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum', 'waka_kurikulum', 'waka kurikulum']))
                                 <!-- Executive Greeting & Status Card -->
                                 <div class="p-3.5 rounded-2xl bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-900 text-white shadow-lg border border-indigo-800/40 mb-3">
                                     <div class="flex items-center justify-between gap-3">
                                         <div class="flex items-center gap-3 min-w-0">
                                             <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 flex items-center justify-center font-black shrink-0 ring-2 ring-indigo-400/30">
                                                 <span class="material-icons text-xl">account_balance</span>
                                             </div>
                                             <div class="min-w-0">
                                                 <div class="flex items-center gap-1.5">
                                                     <h4 class="text-xs font-bold truncate text-white">{{ Auth::user()->name }}</h4>
                                                     <span class="px-1.5 py-0.2 rounded text-[8px] font-black bg-indigo-500/40 text-indigo-200 border border-indigo-400/30">EXECUTIVE</span>
                                                 </div>
                                                 <p class="text-[10px] text-indigo-200 truncate">Kepala Sekolah & Supervisi</p>
                                             </div>
                                         </div>
                                         <span class="text-[9px] font-semibold text-slate-300 bg-white/10 px-2 py-1 rounded-lg shrink-0">
                                             {{ \Carbon\Carbon::today()->translatedFormat('d M Y') }}
                                         </span>
                                     </div>
                                 </div>

                                 <!-- Modul Utama & Supervisi -->
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Supervisi & Dasbor Utama</div>
                                     <div class="grid grid-cols-2 gap-2.5">
                                         <a href="{{ route('principal.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-900/40 text-indigo-950 dark:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all col-span-2">
                                             <div class="p-2 bg-indigo-600 text-white rounded-xl shadow-xs">
                                                 <span class="material-icons text-lg">account_balance</span>
                                             </div>
                                             <div class="flex-1 min-w-0">
                                                 <div class="text-xs font-bold truncate">Dasbor Eksekutif</div>
                                                 <div class="text-[10px] text-indigo-700/80 dark:text-indigo-300/80">KPI presensi & performa harian</div>
                                             </div>
                                             <span class="material-icons text-indigo-400 text-sm">chevron_right</span>
                                         </a>

                                         <a href="{{ route('admin.teaching_journals.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all col-span-2">
                                             <div class="p-2 bg-amber-500 text-slate-950 rounded-xl shadow-xs">
                                                 <span class="material-icons text-lg font-bold">verified_user</span>
                                             </div>
                                             <div class="flex-1 min-w-0">
                                                 <div class="text-xs font-bold truncate">Supervisi Jurnal Mengajar</div>
                                                 <div class="text-[10px] text-amber-700/80 dark:text-amber-300/80">Verifikasi materi & refleksi guru</div>
                                             </div>
                                             @if(isset($pendingJournalsCount) && $pendingJournalsCount > 0)
                                                 <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-slate-950 shadow-xs">{{ $pendingJournalsCount }} Menunggu</span>
                                             @endif
                                         </a>
                                     </div>
                                 </div>

                                 <!-- Modul Monitoring Presensi Terpadu -->
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Monitoring Presensi Terpadu</div>
                                     <div class="grid grid-cols-2 gap-2.5 mb-3">
                                         <a href="{{ route('admin.reports.charts') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all">
                                             <span class="material-icons text-indigo-600 dark:text-indigo-400 text-xl">donut_large</span>
                                             <div class="min-w-0">
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Grafik Siswa</span>
                                                 <span class="text-[9px] text-slate-400 block truncate">Tren kehadiran</span>
                                             </div>
                                         </a>

                                         <a href="{{ route('admin.reports.create') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-emerald-50 dark:hover:bg-slate-800 transition-all">
                                             <span class="material-icons text-emerald-600 dark:text-emerald-400 text-xl">bar_chart</span>
                                             <div class="min-w-0">
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Rekap Siswa</span>
                                                 <span class="text-[9px] text-slate-400 block truncate">Laporan harian</span>
                                             </div>
                                         </a>

                                         <a href="{{ route('admin.reports.teacher.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-teal-50 dark:hover:bg-slate-800 transition-all">
                                             <span class="material-icons text-teal-600 dark:text-teal-400 text-xl">analytics</span>
                                             <div class="min-w-0">
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Rekap Guru</span>
                                                 <span class="text-[9px] text-slate-400 block truncate">Kehadiran pendidik</span>
                                             </div>
                                         </a>

                                         <a href="{{ route('teacher.subject.attendance.report') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-sky-50 dark:hover:bg-slate-800 transition-all">
                                             <span class="material-icons text-sky-600 dark:text-sky-400 text-xl">history_edu</span>
                                             <div class="min-w-0">
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Rekap Mapel</span>
                                                 <span class="text-[9px] text-slate-400 block truncate">Sesi pembelajaran</span>
                                             </div>
                                         </a>

                                         <a href="{{ route('teacher.subject.attendance.report', ['type' => 'cocurricular']) }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-purple-50 dark:hover:bg-slate-800 transition-all">
                                             <span class="material-icons text-purple-600 dark:text-purple-400 text-xl">psychology</span>
                                             <div class="min-w-0">
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Kokurikuler</span>
                                                 <span class="text-[9px] text-slate-400 block truncate">Proyek P5</span>
                                             </div>
                                         </a>

                                         <a href="{{ route('admin.extracurriculars.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-amber-50 dark:hover:bg-slate-800 transition-all">
                                             <span class="material-icons text-amber-600 dark:text-amber-400 text-xl">military_tech</span>
                                             <div class="min-w-0">
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Ekstrakurikuler</span>
                                                 <span class="text-[9px] text-slate-400 block truncate">Kegiatan minat</span>
                                             </div>
                                         </a>
                                     </div>
                                 </div>

                                 <!-- Pemindai & Kiosk Lapangan -->
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Pemindai & Kiosk Lapangan</div>
                                     <div class="grid grid-cols-2 gap-2.5 mb-3">
                                         <a href="{{ route('scanner') }}" @click="mobileMenuOpen = false" class="flex flex-col items-center text-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all group">
                                             <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                 <span class="material-icons text-xl">qr_code_scanner</span>
                                             </div>
                                             <span class="text-xs font-bold text-slate-800 dark:text-white">Scan Masuk/Pulang</span>
                                             <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Pemindai Kiosk</span>
                                         </a>

                                         <a href="{{ route('permit.scanner') }}" @click="mobileMenuOpen = false" class="flex flex-col items-center text-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all group">
                                             <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                 <span class="material-icons text-xl">assignment</span>
                                             </div>
                                             <span class="text-xs font-bold text-slate-800 dark:text-white">Scan Izin Keluar</span>
                                             <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Izin Siswa</span>
                                         </a>
                                     </div>
                                 </div>

                                 <!-- Aplikasi Terintegrasi -->
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Aplikasi Terintegrasi</div>
                                     <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
                                         <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                             <div class="p-2 bg-sky-600 text-white rounded-xl shadow-xs">
                                                 <span class="material-icons text-lg">swap_horiz</span>
                                             </div>
                                             <div class="flex-1 min-w-0">
                                                 <div class="text-xs font-bold truncate">Portal Data SIPADA</div>
                                                 <div class="text-[10px] text-slate-500 dark:text-slate-400">Pangkalan data sekolah</div>
                                             </div>
                                             <span class="material-icons text-xs text-slate-400">open_in_new</span>
                                         </a>

                                         <a href="{{ route('sso.lms') }}" @click="console.log('[SSO Presensi Mobile] Mengalihkan ke LMS Mokopani via SSO:', '{{ route('sso.lms') }}'); mobileMenuOpen = false;" class="flex items-center gap-3 p-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md hover:from-indigo-700 hover:to-indigo-800 transition-all">
                                             <div class="p-2 bg-white/20 rounded-xl backdrop-blur-xs">
                                                 <span class="material-icons text-lg text-white">school</span>
                                             </div>
                                             <div class="flex-1 min-w-0">
                                                 <div class="text-xs font-bold truncate">LMS Mokopani</div>
                                                 <div class="text-[10px] text-indigo-100 truncate">Ruang kelas daring & tugas</div>
                                             </div>
                                             <span class="material-icons text-sm text-white/80">open_in_new</span>
                                         </a>
                                     </div>
                                 </div>

                             <!-- Admin / Operator Section -->
                             @elseif(in_array(auth()->user()->role, ['admin', 'operator', 'satpam']) || auth()->user()->hasAnyRole(['admin', 'operator', 'satpam']))
                                  
                                  <!-- 1. Kategori: Manajemen Presensi & Persetujuan -->
                                  <div class="space-y-2.5">
                                      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Manajemen Presensi & Persetujuan</div>
                                      
                                      <!-- Persetujuan Izin Siswa (Full Width) -->
                                      <a href="{{ route('admin.leave_requests.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3.5 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all min-h-[44px]">
                                          <div class="p-2 bg-amber-500 text-white rounded-xl shadow-xs shrink-0">
                                              <span class="material-icons text-lg">assignment_turned_in</span>
                                          </div>
                                          <div class="flex-1 min-w-0">
                                              <div class="text-xs font-bold truncate">Persetujuan Izin Siswa</div>
                                              <div class="text-[10px] text-amber-700/80 dark:text-amber-300/80 truncate">Tindak lanjuti permohonan izin/sakit</div>
                                          </div>
                                          @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                                              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white shadow-xs shrink-0">{{ $pendingLeaveRequestsCount }}</span>
                                          @endif
                                      </a>

                                      <!-- Pemindai Lapangan (2 Grid) -->
                                      <div class="grid grid-cols-2 gap-2.5">
                                          <a href="{{ route('scanner') }}" @click="mobileMenuOpen = false" class="flex flex-col items-center text-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-sky-50 dark:hover:bg-slate-800 transition-all group min-h-[44px]">
                                              <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                                                  <span class="material-icons text-xl">qr_code_scanner</span>
                                              </div>
                                              <span class="text-xs font-bold text-slate-800 dark:text-white truncate w-full">Scan Hadir/Pulang</span>
                                              <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Pemindai Kiosk</span>
                                          </a>

                                          <a href="{{ route('permit.scanner') }}" @click="mobileMenuOpen = false" class="flex flex-col items-center text-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all group min-h-[44px]">
                                              <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                                                  <span class="material-icons text-xl">assignment</span>
                                              </div>
                                              <span class="text-xs font-bold text-slate-800 dark:text-white truncate w-full">Scan Izin Keluar</span>
                                              <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Izin Siswa</span>
                                          </a>
                                      </div>

                                      <!-- Rekap Laporan (2 Grid) -->
                                      <div class="grid grid-cols-2 gap-2.5">
                                          <a href="{{ route('admin.reports.create') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-emerald-50 dark:hover:bg-slate-800 transition-all min-h-[44px]">
                                              <span class="material-icons text-emerald-600 dark:text-emerald-400 text-xl shrink-0">bar_chart</span>
                                              <div class="min-w-0">
                                                  <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Laporan Siswa</span>
                                                  <span class="text-[9px] text-slate-400 block truncate">Rekap kehadiran</span>
                                              </div>
                                          </a>

                                          <a href="{{ route('admin.reports.teacher.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-teal-50 dark:hover:bg-slate-800 transition-all min-h-[44px]">
                                              <span class="material-icons text-teal-600 dark:text-teal-400 text-xl shrink-0">analytics</span>
                                              <div class="min-w-0">
                                                  <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block truncate">Laporan Guru</span>
                                                  <span class="text-[9px] text-slate-400 block truncate">Kehadiran pendidik</span>
                                              </div>
                                          </a>
                                      </div>
                                  </div>

                                  <!-- 2. Kategori: Data & Integrasi Sekolah -->
                                  <div class="space-y-2.5">
                                      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Data Sekolah & Integrasi</div>
                                      
                                      <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all min-h-[44px]">
                                          <div class="p-2 bg-sky-600 text-white rounded-xl shadow-xs shrink-0">
                                              <span class="material-icons text-lg">swap_horiz</span>
                                          </div>
                                          <div class="flex-1 min-w-0">
                                              <div class="text-xs font-bold truncate">Portal Data SIPADA</div>
                                              <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Pangkalan data siswa & guru</div>
                                          </div>
                                          <span class="material-icons text-xs text-slate-400 shrink-0">open_in_new</span>
                                      </a>

                                      <a href="{{ route('sso.lms') }}" @click="console.log('[SSO Presensi Mobile] Mengalihkan ke LMS Mokopani via SSO:', '{{ route('sso.lms') }}'); mobileMenuOpen = false;" class="flex items-center gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md hover:from-indigo-700 hover:to-indigo-800 transition-all min-h-[44px]">
                                          <div class="p-2 bg-white/20 rounded-xl backdrop-blur-xs shrink-0">
                                              <span class="material-icons text-lg text-white">school</span>
                                          </div>
                                          <div class="flex-1 min-w-0">
                                              <div class="text-xs font-bold truncate">LMS Mokopani</div>
                                              <div class="text-[10px] text-indigo-100 truncate">Buka ruang kelas daring & tugas</div>
                                          </div>
                                          <span class="material-icons text-sm text-white/80 shrink-0">open_in_new</span>
                                      </a>
                                  </div>

                                  <!-- 3. Kategori: Pengaturan & Bantuan -->
                                  <div class="space-y-2.5">
                                      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Pengaturan & Bantuan</div>
                                      <div class="grid grid-cols-2 gap-2.5">
                                          <a href="{{ route('guide') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all min-h-[44px]">
                                              <span class="material-icons text-sky-600 dark:text-sky-400 text-lg shrink-0">auto_stories</span>
                                              <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">Panduan</span>
                                          </a>

                                          <a href="{{ route('admin.settings.appearance') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all min-h-[44px]">
                                              <span class="material-icons text-slate-600 dark:text-slate-400 text-lg shrink-0">palette</span>
                                              <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">Tampilan</span>
                                          </a>
                                      </div>
                                  </div>

                             <!-- Teacher Section -->
                             @elseif(auth()->user()->role === 'teacher' || auth()->user()->hasRole('teacher'))
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Aplikasi Terintegrasi</div>
                                     <a href="{{ route('sso.lms') }}" @click="console.log('[SSO Presensi Mobile] Mengalihkan ke LMS Mokopani via SSO:', '{{ route('sso.lms') }}'); mobileMenuOpen = false;" class="flex items-center gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md hover:from-indigo-700 hover:to-indigo-800 transition-all mb-3">
                                         <div class="p-2 bg-white/20 rounded-xl backdrop-blur-xs">
                                             <span class="material-icons text-xl text-white">school</span>
                                         </div>
                                         <div>
                                             <h4 class="text-xs font-bold">LMS Mokopani</h4>
                                             <p class="text-[10px] text-indigo-100">Buka ruang kelas daring & tugas</p>
                                         </div>
                                         <span class="material-icons text-sm ml-auto opacity-75">open_in_new</span>
                                     </a>
                                 </div>

                                 <!-- Pemindai Kiosk Siswa & Izin Keluar -->
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Pemindai & Kiosk Siswa</div>
                                     <div class="grid grid-cols-2 gap-2.5 mb-3">
                                         <a href="{{ route('scanner') }}" @click="mobileMenuOpen = false" class="flex flex-col items-center text-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-sky-50 dark:hover:bg-slate-800 transition-all group">
                                             <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                 <span class="material-icons text-xl">qr_code_scanner</span>
                                             </div>
                                             <span class="text-xs font-bold text-slate-800 dark:text-white">Scan Masuk/Pulang</span>
                                             <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Absensi Harian Siswa</span>
                                         </a>

                                         <a href="{{ route('permit.scanner') }}" @click="mobileMenuOpen = false" class="flex flex-col items-center text-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all group">
                                             <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                 <span class="material-icons text-xl">assignment</span>
                                             </div>
                                             <span class="text-xs font-bold text-slate-800 dark:text-white">Scan Izin Keluar</span>
                                             <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Izin Tinggalkan Sekolah</span>
                                         </a>
                                     </div>
                                 </div>

                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Menu Mengajar & Penugasan</div>
                                     <div class="grid grid-cols-2 gap-2.5">
                                         <a href="{{ route('teacher.subject.attendance.history') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all col-span-2">
                                             <span class="material-icons text-indigo-600 dark:text-indigo-400 text-xl">history_edu</span>
                                             <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Riwayat Presensi Mapel</span>
                                         </a>

                                         @if(auth()->user()->teacher?->cocurriculars()->exists())
                                             <a href="{{ route('teacher.dashboard', ['view' => 'fasilitator_kokurikuler']) }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-900/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all col-span-2">
                                                 <span class="material-icons text-indigo-600 dark:text-indigo-400 text-xl">psychology</span>
                                                 <div class="flex-1 min-w-0">
                                                     <div class="text-xs font-bold text-indigo-950 dark:text-indigo-200 truncate">Presensi Kokurikuler</div>
                                                     <div class="text-[10px] text-indigo-600 dark:text-indigo-400">Tim Fasilitator Proyek</div>
                                                 </div>
                                                 <span class="material-icons text-indigo-400 text-sm">chevron_right</span>
                                             </a>
                                         @endif

                                         @if(auth()->user()->teacher?->homeroomClass)
                                             <a href="{{ route('teacher.leave_requests.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-all col-span-2">
                                                 <span class="material-icons text-amber-600 dark:text-amber-400 text-xl">assignment_turned_in</span>
                                                 <div class="flex-1 min-w-0">
                                                     <div class="text-xs font-bold text-amber-900 dark:text-amber-200 truncate">Izin Siswa Binaan</div>
                                                 </div>
                                                 @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                                                     <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">{{ $teacherPendingLeaveRequestsCount }}</span>
                                                 @endif
                                             </a>
                                             <a href="{{ route('teacher.attendance.history') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                                 <span class="material-icons text-blue-600 dark:text-blue-400 text-xl">history</span>
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Riwayat Harian</span>
                                             </a>
                                             <a href="{{ route('chat.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                                 <span class="material-icons text-pink-600 dark:text-pink-400 text-xl">chat</span>
                                                 <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Obrolan Ortu</span>
                                             </a>
                                         @endif

                                         @if(auth()->user()->teacher?->coachingExtracurriculars()->exists())
                                             <a href="{{ route('teacher.extracurricular-attendance.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-slate-800 transition-all col-span-2">
                                                 <span class="material-icons text-amber-600 dark:text-amber-400 text-xl">military_tech</span>
                                                 <div class="flex-1 min-w-0">
                                                     <div class="text-xs font-bold text-amber-950 dark:text-amber-200 truncate">Presensi Ekstrakurikuler</div>
                                                     <div class="text-[10px] text-amber-600 dark:text-amber-400">Pembina Kegiatan</div>
                                                 </div>
                                                 <span class="material-icons text-amber-400 text-sm">chevron_right</span>
                                             </a>
                                         @endif
                                     </div>
                                 </div>

                             <!-- Parent Section -->
                             @elseif(auth()->user()->role === 'parent' || auth()->user()->hasRole('parent'))
                                 <div>
                                     <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Bantuan & Kegiatan</div>
                                     <div class="grid grid-cols-1 gap-2.5">
                                         <a href="{{ route('parent.guide') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3.5 rounded-2xl bg-sky-50 dark:bg-sky-950/30 border border-sky-200/60 dark:border-sky-900/40 text-sky-800 dark:text-sky-200 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition-all">
                                             <div class="p-2 bg-sky-600 text-white rounded-xl shadow-xs">
                                                 <span class="material-icons text-lg">auto_stories</span>
                                             </div>
                                             <div>
                                                 <div class="text-xs font-bold">Panduan Penggunaan Aplikasi</div>
                                                 <div class="text-[10px] text-sky-600 dark:text-sky-400">Petunjuk izin & pemantauan kehadiran</div>
                                             </div>
                                         </a>

                                         <a href="{{ route('parent.dashboard') }}#ekskul" @click="mobileMenuOpen = false" class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                             <div class="p-2 bg-purple-500 text-white rounded-xl shadow-xs">
                                                 <span class="material-icons text-lg">star</span>
                                             </div>
                                             <div>
                                                 <div class="text-xs font-bold">Kegiatan Ekstrakurikuler</div>
                                                 <div class="text-[10px] text-slate-500 dark:text-slate-400">Jadwal & absensi ekskul putra/putri</div>
                                             </div>
                                         </a>
                                     </div>
                                 </div>
                             @endif

                             <!-- Account & Settings Menu -->
                             <div class="pt-2">
                                 <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Akun & Pengaturan</div>
                                 <div class="space-y-2">
                                     <a href="{{ route('profile.edit') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                         <span class="material-icons text-slate-500 dark:text-slate-400 text-xl">manage_accounts</span>
                                         <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Profil & Keamanan Akun</span>
                                     </a>

                                     @if(auth()->user()->role === 'admin')
                                         <a href="{{ route('admin.settings.appearance') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                             <span class="material-icons text-slate-500 dark:text-slate-400 text-xl">palette</span>
                                             <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Tema & Logo Sekolah</span>
                                         </a>
                                     @endif

                                     <a href="{{ route('guide') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-4 py-3 bg-sky-50 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/60 rounded-xl hover:bg-sky-100 dark:hover:bg-sky-900/50 transition-colors">
                                         <span class="material-icons text-sky-600 dark:text-sky-400 text-xl">auto_stories</span>
                                         <span class="text-xs font-bold text-sky-800 dark:text-sky-200">Panduan Penggunaan</span>
                                     </a>

                                     <button type="button" 
                                             @click="mobileMenuOpen = false; showMobileLogoutConfirm = true" 
                                             class="w-full flex items-center gap-3 px-4 py-3 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/40 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-colors text-left">
                                         <span class="material-icons text-xl">logout</span>
                                         <span class="text-xs font-bold">Keluar / Logout</span>
                                     </button>
                                 </div>
                             </div>

                             <!-- Mobile Drawer Footer Info -->
                             <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500">
                                 <div class="flex items-center gap-2">
                                     <a href="{{ route('guide') }}" @click="mobileMenuOpen = false" class="hover:text-sky-600 dark:hover:text-sky-400 font-semibold transition-colors">
                                         Panduan
                                     </a>
                                     <span>&bull;</span>
                                     <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="hover:text-sky-600 dark:hover:text-sky-400 font-semibold transition-colors">
                                         Tentang
                                     </a>
                                 </div>
                                 <span>{{ config('app.name', 'Presensi') }} v2.5</span>
                             </div>
                         </div>
                    </div>
                </div>

                {{-- Form Logout Mobile Tersembunyi --}}
                <form method="POST" action="{{ route('logout') }}" x-ref="mobileLogoutForm" class="hidden">
                    @csrf
                </form>

                {{-- Modal Konfirmasi Logout Mobile (Teleported to body for precise full-screen centering) --}}
                <template x-teleport="body">
                    <div x-show="showMobileLogoutConfirm" 
                         x-cloak
                         style="display: none;" 
                         class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                        
                        <!-- Backdrop Overlay -->
                        <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" 
                             x-show="showMobileLogoutConfirm"
                             x-transition:enter="ease-out duration-200" 
                             x-transition:enter-start="opacity-0" 
                             x-transition:enter-end="opacity-100" 
                             x-transition:leave="ease-in duration-150" 
                             x-transition:leave-start="opacity-100" 
                             x-transition:leave-end="opacity-0" 
                             @click="showMobileLogoutConfirm = false"></div>
                        
                        <!-- Modal Content Box -->
                        <div @click.away="showMobileLogoutConfirm = false" 
                             x-show="showMobileLogoutConfirm" 
                             x-transition:enter="ease-out duration-200" 
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
                             x-transition:leave="ease-in duration-150" 
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
                             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                             class="relative bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-2xl border border-slate-200/80 dark:border-slate-800 max-w-sm w-full text-center z-10 transform transition-all">
                            
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 mb-4 ring-8 ring-rose-500/10">
                                <span class="material-icons text-3xl">logout</span>
                            </div>
                            
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfirmasi Keluar</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                Apakah Anda yakin ingin keluar dari sesi akun ini?
                            </p>

                            <div class="mt-6 flex items-center justify-center gap-3">
                                <button @click="showMobileLogoutConfirm = false" type="button" 
                                        class="flex-1 min-h-[48px] px-5 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors">
                                    Batal
                                </button>
                                <button @click="$refs.mobileLogoutForm.submit()" type="button" 
                                        class="flex-1 min-h-[48px] px-5 py-3 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-sm shadow-md shadow-rose-600/25 transition-all active:scale-95">
                                    Ya, Keluar
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            @endauth
        </div>
    </div>

    <!-- Back to Top Floating Button -->
    <div x-data="{ show: false }" @scroll.window="show = (window.pageYOffset > 300)"
        class="fixed bottom-6 right-6 z-40 hidden lg:block">
        <button x-show="show" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" x-transition
            class="p-3 rounded-full bg-slate-900/80 dark:bg-slate-800/90 text-white backdrop-blur-sm shadow-lg hover:bg-sky-600 dark:hover:bg-sky-600 focus:outline-none transition-all transform hover:scale-105 active:scale-95"
            aria-label="Kembali ke atas" style="display: none;">
            <span class="material-icons text-xl">arrow_upward</span>
        </button>
    </div>

    <!-- Global Student Photo Preview Modal -->
    <x-photo-preview-modal />

    <!-- Global No Schedule Warning Modal -->
    <x-no-schedule-modal />

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

    <!-- Active Mobile Tab Highlighting -->
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
                            item.classList.add('text-sky-600', 'dark:text-sky-400');
                            item.classList.remove('text-slate-500', 'dark:text-slate-400');
                        } else {
                            item.classList.add('text-slate-500', 'dark:text-slate-400');
                            item.classList.remove('text-sky-600', 'dark:text-sky-400');
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