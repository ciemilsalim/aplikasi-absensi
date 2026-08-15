<!-- Sidebar -->
<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-slate-800 pb-4 border-r border-gray-200 dark:border-slate-700 transition-all duration-300" :class="sidebarCollapsed ? 'px-3' : 'px-6'">
    <!-- Logo & Nama Aplikasi -->
    <div class="flex h-16 shrink-0 items-center transition-all duration-300" :class="sidebarCollapsed ? 'justify-center' : 'gap-x-3'">
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex items-center gap-3" :title="sidebarCollapsed ? '{{ config('app.name', 'Presensi') }}' : ''">
            <x-application-logo class="block h-9 w-auto flex-shrink-0" />
            <div x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <p class="font-bold text-lg text-slate-800 dark:text-white tracking-tight leading-tight">{{ config('app.name', 'Presensi') }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-tight">{{ $appName ?? 'Nama Sekolah' }}</p>
            </div>
        </a>
    </div>
    
    <!-- Menu Navigasi -->
    <nav class="flex flex-1 flex-col">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
                <div x-show="!sidebarCollapsed" class="text-xs font-semibold leading-6 text-gray-400">Menu Utama</div>
                <ul role="list" class="mt-2 space-y-1" :class="sidebarCollapsed ? '-mx-1' : '-mx-2'">
                    @auth
                        {{-- Menu Dasbor --}}
                        @if(auth()->user()->hasAnyRole(['admin', 'operator', 'satpam']))
                             <li>
                                <a href="{{ route('admin.dashboard') }}" :title="sidebarCollapsed ? 'Dasbor' : ''" class="{{ request()->routeIs('admin.dashboard') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                    <span x-show="!sidebarCollapsed">Dasbor</span>
                                </a>
                            </li>
                        @elseif(auth()->user()->hasRole('parent'))
                             <li>
                                <a href="{{ route('parent.dashboard') }}" :title="sidebarCollapsed ? 'Dasbor Anak' : ''" class="{{ request()->routeIs('parent.dashboard') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                    <span x-show="!sidebarCollapsed">Dasbor Anak</span>
                                </a>
                            </li>
                        @elseif(auth()->user()->hasRole('teacher'))
                             <li>
                                <a href="{{ route('teacher.dashboard') }}" :title="sidebarCollapsed ? 'Dasbor' : ''" class="{{ request()->routeIs('teacher.dashboard') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                    <span x-show="!sidebarCollapsed">Dasbor</span>
                                </a>
                            </li>
                             <li>
                                <a href="{{ route('teacher.attendance.dashboard') }}" :title="sidebarCollapsed ? 'Absensi Saya' : ''" class="{{ request()->routeIs('teacher.attendance.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                    <span x-show="!sidebarCollapsed">Absensi Saya</span>
                                </a>
                            </li>
                        @endif

                        {{-- Menu Pemindai untuk Admin, Operator, Guru, dan Satpam --}}
                        @if(auth()->user()->hasAnyRole(['admin', 'operator', 'teacher', 'satpam']))
                            <li>
                                <a href="{{ route('scanner') }}" :title="sidebarCollapsed ? 'Pemindai Kehadiran' : ''" class="{{ request()->routeIs('scanner') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                                    <span x-show="!sidebarCollapsed">Pemindai Kehadiran</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('permit.scanner') }}" :title="sidebarCollapsed ? 'Pemindai Izin' : ''" class="{{ request()->routeIs('permit.scanner') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                                    <span x-show="!sidebarCollapsed">Pemindai Izin</span>
                                </a>
                            </li>
                        @endif
                        
                        {{-- Menu Orang Tua --}}
                        @if(auth()->user()->hasRole('parent'))
                             <li>
                                <a href="{{ route('parent.leave-requests.index') }}" :title="sidebarCollapsed ? 'Izin/Sakit' : ''" class="{{ request()->routeIs('parent.leave-requests.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    <span x-show="!sidebarCollapsed">Izin/Sakit</span>
                                </a>
                            </li>
                             <li>
                                <a href="{{ route('parent.dashboard') }}#ekskul" :title="sidebarCollapsed ? 'Ekstrakurikuler' : ''" class="text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700 group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                                        <span x-show="!sidebarCollapsed">Ekstrakurikuler</span>
                                    </span>
                                    <span x-show="!sidebarCollapsed" class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 ml-auto">Baru</span>
                                </a>
                            </li>
                             <li>
                                <a href="{{ route('chat.index') }}" :title="sidebarCollapsed ? 'Obrolan' : ''" class="{{ request()->routeIs('chat.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.397 48.397 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                        <span x-show="!sidebarCollapsed">Obrolan</span>
                                    </span>
                                    @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-medium" :class="sidebarCollapsed ? 'absolute -top-1 -right-1' : ''">{{ $totalUnreadMessagesCount }}</span>
                                    @endif
                                </a>
                            </li>
                             <li>
                                <a href="{{ route('parent.guide') }}" :title="sidebarCollapsed ? 'Panduan Penggunaan' : ''" class="{{ request()->routeIs('parent.guide') ? 'bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <span class="material-icons text-xl shrink-0">auto_stories</span>
                                    <span x-show="!sidebarCollapsed">Panduan Penggunaan</span>
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>
            </li>

            {{-- == PENGELOMPOKAN MENU GURU == --}}
            @auth
                @if(auth()->user()->hasRole('teacher'))
                    <li>
                        <div x-show="!sidebarCollapsed" class="text-xs font-semibold leading-6 text-gray-400">Aplikasi Terintegrasi</div>
                        <ul role="list" class="mt-2 space-y-1" :class="sidebarCollapsed ? '-mx-1' : '-mx-2'">
                            <li>
                                <a href="{{ route('sso.lms') }}" :title="sidebarCollapsed ? 'LMS Mokopani' : ''" class="group flex rounded-md p-2 text-sm leading-6 font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 border border-indigo-100 dark:border-indigo-900/50 transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.315 48.315 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" /></svg>
                                    <span x-show="!sidebarCollapsed">LMS Mokopani</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Tampilkan menu ini HANYA jika guru punya tugas mengajar mapel --}}
                    @if(auth()->user()->teacher?->teachingAssignments()->exists())
                    <li>
                        <div x-show="!sidebarCollapsed" class="text-xs font-semibold leading-6 text-gray-400">Menu Guru Mapel</div>
                        <ul role="list" class="mt-2 space-y-1" :class="sidebarCollapsed ? '-mx-1' : '-mx-2'">
                            <li>
                                <a href="{{ route('teacher.subject.attendance.report') }}" :title="sidebarCollapsed ? 'Rekap Absensi Mapel' : ''" class="{{ request()->routeIs('teacher.subject.attendance.report*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5-1.5a1.5 1.5 0 0 1-1.5-1.5V6.75A2.25 2.25 0 0 1 4.5 4.5h15a2.25 2.25 0 0 1 2.25 2.25v12.75a1.5 1.5 0 0 1-1.5 1.5h-16.5a1.5 1.5 0 0 1-1.5-1.5Z" /></svg>
                                    <span x-show="!sidebarCollapsed">Rekap Absensi Mapel</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- Tampilkan menu ini HANYA jika guru adalah wali kelas --}}
                    @if(auth()->user()->teacher?->homeroomClass)
                    <li>
                        <div x-show="!sidebarCollapsed" class="text-xs font-semibold leading-6 text-gray-400">Menu Wali Kelas</div>
                        <ul role="list" class="mt-2 space-y-1" :class="sidebarCollapsed ? '-mx-1' : '-mx-2'">
                            <li>
                                <a href="{{ route('teacher.leave_requests.index') }}" :title="sidebarCollapsed ? 'Pengajuan Izin' : ''" class="{{ request()->routeIs('teacher.leave_requests.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg>
                                        <span x-show="!sidebarCollapsed">Pengajuan Izin</span>
                                    </span>
                                    @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                                        <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-medium">{{ $teacherPendingLeaveRequestsCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('teacher.attendance.history') }}" :title="sidebarCollapsed ? 'Riwayat Kehadiran' : ''" class="{{ request()->routeIs('teacher.attendance.history') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" /></svg>
                                    <span x-show="!sidebarCollapsed">Riwayat Kehadiran</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('chat.index') }}" :title="sidebarCollapsed ? 'Obrolan' : ''" class="{{ request()->routeIs('chat.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.397 48.397 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                        <span x-show="!sidebarCollapsed">Obrolan</span>
                                    </span>
                                    @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                        <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-medium">{{ $totalUnreadMessagesCount }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- Tampilkan menu ini HANYA jika guru adalah pembina ekskul --}}
                    @if(auth()->user()->teacher?->coachingExtracurriculars()->exists())
                    <li>
                        <div x-show="!sidebarCollapsed" class="text-xs font-semibold leading-6 text-gray-400">Menu Pembina Ekskul</div>
                        <ul role="list" class="mt-2 space-y-1" :class="sidebarCollapsed ? '-mx-1' : '-mx-2'">
                            <li>
                                <a href="{{ route('teacher.extracurricular-attendance.index') }}" :title="sidebarCollapsed ? 'Absensi Ekskul' : ''" class="{{ request()->routeIs('teacher.extracurricular-attendance.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                                        <span x-show="!sidebarCollapsed">Absensi Ekskul</span>
                                    </span>
                                    <span x-show="!sidebarCollapsed" class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">Baru</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif
                @endif
            @endauth
            
            @if(Auth::check() && auth()->user()->hasAnyRole(['admin', 'operator']))
            <li>
                <div x-show="!sidebarCollapsed" class="text-xs font-semibold leading-6 text-gray-400">Administrasi</div>
                <ul role="list" class="mt-2 space-y-1" :class="sidebarCollapsed ? '-mx-1' : '-mx-2'">
                         <li>
                             <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard" :title="sidebarCollapsed ? 'Portal Data SIPADA' : ''" class="bg-sky-50 dark:bg-sky-950/30 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-900/50 group flex rounded-md p-2 text-sm leading-6 font-semibold hover:bg-sky-100 dark:hover:bg-sky-900/50 transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                                 <span x-show="!sidebarCollapsed">Portal Data SIPADA</span>
                             </a>
                         </li>

                         <li>
                            <a href="{{ route('admin.leave_requests.index') }}" :title="sidebarCollapsed ? 'Pengajuan Izin' : ''" class="{{ request()->routeIs('admin.leave_requests.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                <span class="flex gap-x-3 items-center">
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg>
                                    <span x-show="!sidebarCollapsed">Pengajuan Izin</span>
                                </span>
                                @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                                    <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-medium">{{ $pendingLeaveRequestsCount }}</span>
                                @endif
                            </a>
                        </li>

                         <li>
                            <a href="{{ route('admin.chat.index') }}" :title="sidebarCollapsed ? 'Pesan Ortu' : ''" class="{{ request()->routeIs('admin.chat.*') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex items-center rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'justify-between gap-x-3'">
                                <span class="flex gap-x-3 items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.397 48.397 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                    <span x-show="!sidebarCollapsed">Pesan Ortu</span>
                                </span>
                                @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                    <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-medium">{{ $totalUnreadMessagesCount }}</span>
                                @endif
                            </a>
                        </li>

                         {{-- Laporan Dropdown --}}
                         @if(auth()->user()->hasAnyRole(['admin', 'operator']))
                             <li x-data="{ open: {{ request()->routeIs(['admin.reports.*']) ? 'true' : 'false' }} }">
                                 <button @click="open = !open" :title="sidebarCollapsed ? 'Laporan' : ''" class="group flex items-center w-full rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200 {{ request()->routeIs(['admin.reports.*']) ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }}" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                     <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                         <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5-1.5a1.5 1.5 0 0 1-1.5-1.5V6.75A2.25 2.25 0 0 1 4.5 4.5h15a2.25 2.25 0 0 1 2.25 2.25v12.75a1.5 1.5 0 0 1-1.5 1.5h-16.5a1.5 1.5 0 0 1-1.5-1.5Z" />
                                     </svg>
                                     <span x-show="!sidebarCollapsed">Laporan</span>
                                     <svg x-show="!sidebarCollapsed" class="ml-auto h-5 w-5 shrink-0 transition-transform duration-200" :class="{ 'rotate-90': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                                 </button>
                                 <ul x-show="open && !sidebarCollapsed" x-transition class="mt-1 ml-4 pl-4 space-y-1 border-l-2 border-slate-200 dark:border-slate-600">
                                      <li><a href="{{ route('admin.reports.create') }}" class="{{ request()->routeIs('admin.reports.create') ? 'text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300' }} block rounded-md p-2 text-sm leading-6 hover:bg-slate-50 dark:hover:bg-slate-700">Laporan Siswa</a></li>
                                      <li><a href="{{ route('admin.reports.teacher.index') }}" class="{{ request()->routeIs('admin.reports.teacher.*') ? 'text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300' }} block rounded-md p-2 text-sm leading-6 hover:bg-slate-50 dark:hover:bg-slate-700">Laporan Guru</a></li>
                                 </ul>
                             </li>
                             @if(auth()->user()->hasRole('admin'))
                                 <li>
                                     <a href="{{ route('admin.settings.appearance') }}" :title="sidebarCollapsed ? 'Tampilan & Logo' : ''" class="{{ request()->routeIs('admin.settings.appearance') ? 'bg-slate-100 dark:bg-slate-700 text-sky-600 dark:text-white' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 hover:bg-slate-50 dark:hover:bg-slate-700' }} group flex rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200" :class="sidebarCollapsed ? 'justify-center gap-x-0' : 'gap-x-3'">
                                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 shrink-0">
                                             <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                             <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                         </svg>
                                         <span x-show="!sidebarCollapsed">Tampilan & Logo</span>
                                     </a>
                                 </li>
                             @endif
                         @endif
            </ul>
        </li>
            @endif

        </ul>
    </nav>
</div>
