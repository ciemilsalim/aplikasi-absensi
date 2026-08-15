<!-- Sidebar Container -->
<div class="flex grow flex-col gap-y-4 overflow-y-auto bg-white dark:bg-slate-900 pb-4 border-r border-slate-200/80 dark:border-slate-800 transition-all duration-300 select-none no-scrollbar" :class="sidebarCollapsed ? 'px-2.5' : 'px-4'">
    
    <!-- Brand / School Header -->
    <div class="flex h-16 shrink-0 items-center transition-all duration-300 border-b border-slate-100 dark:border-slate-800/80" :class="sidebarCollapsed ? 'justify-center' : 'px-2 gap-x-3'">
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex items-center gap-3 group" :title="sidebarCollapsed ? '{{ config('app.name', 'Presensi') }}' : ''">
            <div class="relative flex items-center justify-center">
                <x-application-logo class="block h-9 w-auto flex-shrink-0 transition-transform group-hover:scale-105" />
            </div>
            <div x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <p class="font-bold text-base text-slate-900 dark:text-white tracking-tight leading-tight truncate">{{ config('app.name', 'Presensi') }}</p>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300">PRO</span>
                </div>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-tight truncate max-w-[150px]">{{ $appName ?? 'Sistem Informasi Sekolah' }}</p>
            </div>
        </a>
    </div>
    
    <!-- Navigation Links -->
    <nav class="flex flex-1 flex-col pt-2">
        <ul role="list" class="flex flex-1 flex-col gap-y-6">
            
            <!-- SECTION 1: MENU UTAMA -->
            <li>
                <div x-show="!sidebarCollapsed" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Menu Utama</div>
                <ul role="list" class="mt-1.5 space-y-1">
                    @auth
                        {{-- Dashboard: Admin / Operator / Satpam --}}
                        @if(auth()->user()->hasAnyRole(['admin', 'operator', 'satpam']))
                            @php $isActive = request()->routeIs('admin.dashboard'); @endphp
                            <li>
                                <a href="{{ route('admin.dashboard') }}" :title="sidebarCollapsed ? 'Dasbor Utama' : ''" 
                                   class="{{ $isActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110">dashboard</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Dasbor Utama</span>
                                    @if($isActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                        
                        {{-- Dashboard: Parent --}}
                        @elseif(auth()->user()->hasRole('parent'))
                            @php $isActive = request()->routeIs('parent.dashboard'); @endphp
                            <li>
                                <a href="{{ route('parent.dashboard') }}" :title="sidebarCollapsed ? 'Dasbor Anak' : ''" 
                                   class="{{ $isActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110">dashboard</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Dasbor Anak</span>
                                    @if($isActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            @php $isActive = request()->routeIs('parent.leave-requests.*'); @endphp
                            <li>
                                <a href="{{ route('parent.leave-requests.index') }}" :title="sidebarCollapsed ? 'Izin & Sakit' : ''" 
                                   class="{{ $isActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110">assignment_turned_in</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Izin / Sakit</span>
                                    @if($isActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            <li>
                                <a href="{{ route('parent.dashboard') }}#ekskul" :title="sidebarCollapsed ? 'Ekstrakurikuler' : ''" 
                                   class="text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <span class="material-icons text-xl shrink-0 text-purple-500">star</span>
                                        <span x-show="!sidebarCollapsed" class="truncate">Ekstrakurikuler</span>
                                    </span>
                                    <span x-show="!sidebarCollapsed" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 ml-auto">Kegiatan</span>
                                </a>
                            </li>
                            
                            @php $isActive = request()->routeIs('chat.*'); @endphp
                            <li>
                                <a href="{{ route('chat.index') }}" :title="sidebarCollapsed ? 'Obrolan' : ''" 
                                   class="{{ $isActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110">chat</span>
                                        <span x-show="!sidebarCollapsed" class="truncate">Obrolan</span>
                                    </span>
                                    @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                        <span class="inline-flex items-center justify-center h-4.5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold shadow-xs" :class="sidebarCollapsed ? 'absolute -top-1 -right-1' : ''">{{ $totalUnreadMessagesCount }}</span>
                                    @elseif($isActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            @php $isActive = request()->routeIs('parent.guide'); @endphp
                            <li>
                                <a href="{{ route('parent.guide') }}" :title="sidebarCollapsed ? 'Panduan Penggunaan' : ''" 
                                   class="{{ $isActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 text-sky-500">auto_stories</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Panduan Sistem</span>
                                    @if($isActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>

                        {{-- Dashboard: Teacher --}}
                        @elseif(auth()->user()->hasRole('teacher'))
                            @php $isDashboardActive = request()->routeIs('teacher.dashboard'); @endphp
                            <li>
                                <a href="{{ route('teacher.dashboard') }}" :title="sidebarCollapsed ? 'Dasbor Guru' : ''" 
                                   class="{{ $isDashboardActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110">dashboard</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Dasbor Guru</span>
                                    @if($isDashboardActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            {{-- Absensi Guru (Hanya aktif untuk rute absensi mandiri guru) --}}
                            @php $isTeacherAttendanceActive = request()->routeIs(['teacher.attendance.dashboard', 'teacher.attendance.scanner']); @endphp
                            <li>
                                <a href="{{ route('teacher.attendance.dashboard') }}" :title="sidebarCollapsed ? 'Absensi Guru' : ''" 
                                   class="{{ $isTeacherAttendanceActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110">person_pin</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Absensi Saya</span>
                                    @if($isTeacherAttendanceActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        {{-- Scanner Menus for Staff & Teachers --}}
                        @if(auth()->user()->hasAnyRole(['admin', 'operator', 'teacher', 'satpam']))
                            @php $isScannerActive = request()->routeIs('scanner'); @endphp
                            <li>
                                <a href="{{ route('scanner') }}" :title="sidebarCollapsed ? 'Pemindai Hadir' : ''" 
                                   class="{{ $isScannerActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110 text-sky-600 dark:text-sky-400">qr_code_scanner</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Pemindai Masuk/Pulang</span>
                                    @if($isScannerActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            @php $isPermitActive = request()->routeIs('permit.scanner'); @endphp
                            <li>
                                <a href="{{ route('permit.scanner') }}" :title="sidebarCollapsed ? 'Pemindai Izin Keluar' : ''" 
                                   class="{{ $isPermitActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0 transition-transform group-hover:scale-110 text-indigo-600 dark:text-indigo-400">assignment</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Pemindai Izin Keluar</span>
                                    @if($isPermitActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>
            </li>

            <!-- SECTION 2: GURU / TEACHER INTEGRATION & CLASSROOM -->
            @auth
                @if(auth()->user()->hasRole('teacher'))
                    <li>
                        <div x-show="!sidebarCollapsed" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Aplikasi Terintegrasi</div>
                        <ul role="list" class="mt-1.5 space-y-1">
                            <li>
                                <a href="{{ route('sso.lms') }}" :title="sidebarCollapsed ? 'LMS Mokopani' : ''" 
                                   class="group flex items-center rounded-xl p-2.5 text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 border border-indigo-100 dark:border-indigo-900/40 transition-all duration-200 shadow-xs" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0">school</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">LMS Mokopani</span>
                                    <span x-show="!sidebarCollapsed" class="material-icons text-xs ml-auto opacity-70">open_in_new</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Guru Mata Pelajaran --}}
                    @if(auth()->user()->teacher?->teachingAssignments()->exists())
                    <li>
                        <div x-show="!sidebarCollapsed" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Guru Mata Pelajaran</div>
                        <ul role="list" class="mt-1.5 space-y-1">
                            @php $isSubjectReportActive = request()->routeIs(['teacher.subject.attendance.report', 'teacher.subject.attendance.preview', 'teacher.subject.attendance.print', 'teacher.subject.attendance.charts']); @endphp
                            <li>
                                <a href="{{ route('teacher.subject.attendance.report') }}" :title="sidebarCollapsed ? 'Rekap Absensi Mapel' : ''" 
                                   class="{{ $isSubjectReportActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0">assessment</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Rekap Absensi Mapel</span>
                                    @if($isSubjectReportActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            @php $isSubjectHistoryActive = request()->routeIs(['teacher.subject.attendance.history', 'teacher.subject.attendance.scanner']); @endphp
                            <li>
                                <a href="{{ route('teacher.subject.attendance.history') }}" :title="sidebarCollapsed ? 'Riwayat Presensi Mapel' : ''" 
                                   class="{{ $isSubjectHistoryActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0">history_edu</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Riwayat Presensi Mapel</span>
                                    @if($isSubjectHistoryActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- Wali Kelas --}}
                    @if(auth()->user()->teacher?->homeroomClass)
                    <li>
                        <div x-show="!sidebarCollapsed" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Wali Kelas</div>
                        <ul role="list" class="mt-1.5 space-y-1">
                            @php $isLeaveActive = request()->routeIs('teacher.leave_requests.*'); @endphp
                            <li>
                                <a href="{{ route('teacher.leave_requests.index') }}" :title="sidebarCollapsed ? 'Pengajuan Izin' : ''" 
                                   class="{{ $isLeaveActive ? 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-600 dark:text-amber-300 font-bold border border-amber-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-amber-600 hover:bg-amber-50/50 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <span class="material-icons text-xl shrink-0 text-amber-500">assignment_turned_in</span>
                                        <span x-show="!sidebarCollapsed" class="truncate">Pengajuan Izin</span>
                                    </span>
                                    @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                                        <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center h-4.5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold shadow-xs">{{ $teacherPendingLeaveRequestsCount }}</span>
                                    @elseif($isLeaveActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-amber-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            {{-- Riwayat Kelas binaan wali kelas (hanya aktif untuk attendance.history, charts, print, excel) --}}
                            @php $isClassHistoryActive = request()->routeIs(['teacher.attendance.history', 'teacher.attendance.charts', 'teacher.attendance.print*', 'teacher.attendance.export.excel']); @endphp
                            <li>
                                <a href="{{ route('teacher.attendance.history') }}" :title="sidebarCollapsed ? 'Riwayat Kehadiran' : ''" 
                                   class="{{ $isClassHistoryActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                    <span class="material-icons text-xl shrink-0">history</span>
                                    <span x-show="!sidebarCollapsed" class="truncate">Riwayat Kelas</span>
                                    @if($isClassHistoryActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                            
                            @php $isChatActive = request()->routeIs('chat.*'); @endphp
                            <li>
                                <a href="{{ route('chat.index') }}" :title="sidebarCollapsed ? 'Obrolan' : ''" 
                                   class="{{ $isChatActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <span class="material-icons text-xl shrink-0 text-pink-500">chat</span>
                                        <span x-show="!sidebarCollapsed" class="truncate">Obrolan Ortu</span>
                                    </span>
                                    @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                        <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center h-4.5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold shadow-xs">{{ $totalUnreadMessagesCount }}</span>
                                    @elseif($isChatActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- Pembina Ekskul --}}
                    @if(auth()->user()->teacher?->coachingExtracurriculars()->exists())
                    <li>
                        <div x-show="!sidebarCollapsed" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Pembina Ekskul</div>
                        <ul role="list" class="mt-1.5 space-y-1">
                            @php $isEkskulActive = request()->routeIs('teacher.extracurricular-attendance.*'); @endphp
                            <li>
                                <a href="{{ route('teacher.extracurricular-attendance.index') }}" :title="sidebarCollapsed ? 'Absensi Ekskul' : ''" 
                                   class="{{ $isEkskulActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                                   :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                                    <span class="flex gap-x-3 items-center">
                                        <span class="material-icons text-xl shrink-0 text-rose-500">star</span>
                                        <span x-show="!sidebarCollapsed" class="truncate">Presensi Ekskul</span>
                                    </span>
                                    @if($isEkskulActive)
                                        <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                    @else
                                        <span x-show="!sidebarCollapsed" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">Aktif</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif
                @endif
            @endauth
            
            <!-- SECTION 3: ADMINISTRASI & LAPORAN -->
            @if(Auth::check() && auth()->user()->hasAnyRole(['admin', 'operator']))
            <li>
                <div x-show="!sidebarCollapsed" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Administrasi & Sinkron</div>
                <ul role="list" class="mt-1.5 space-y-1">
                    <li>
                        <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard" :title="sidebarCollapsed ? 'Portal SIPADA' : ''" 
                           class="group flex items-center rounded-xl p-2.5 text-xs font-semibold bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/50 border border-sky-100 dark:border-sky-900/40 transition-all duration-200 shadow-xs" 
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                            <span class="material-icons text-xl shrink-0">swap_horiz</span>
                            <span x-show="!sidebarCollapsed" class="truncate">Portal Data SIPADA</span>
                            <span x-show="!sidebarCollapsed" class="material-icons text-xs ml-auto opacity-70">open_in_new</span>
                        </a>
                    </li>

                    @php $isAdminLeaveActive = request()->routeIs('admin.leave_requests.*'); @endphp
                    <li>
                        <a href="{{ route('admin.leave_requests.index') }}" :title="sidebarCollapsed ? 'Pengajuan Izin Siswa' : ''" 
                           class="{{ $isAdminLeaveActive ? 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-600 dark:text-amber-300 font-bold border border-amber-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-amber-600 hover:bg-amber-50/50 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                            <span class="flex gap-x-3 items-center">
                                <span class="material-icons text-xl shrink-0 text-amber-500">assignment_turned_in</span>
                                <span x-show="!sidebarCollapsed" class="truncate">Pengajuan Izin</span>
                            </span>
                            @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                                <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center h-4.5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold shadow-xs">{{ $pendingLeaveRequestsCount }}</span>
                            @elseif($isAdminLeaveActive)
                                <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-amber-500 ml-auto shrink-0"></span>
                            @endif
                        </a>
                    </li>

                    @php $isAdminChatActive = request()->routeIs('admin.chat.*'); @endphp
                    <li>
                        <a href="{{ route('admin.chat.index') }}" :title="sidebarCollapsed ? 'Pesan Orang Tua' : ''" 
                           class="{{ $isAdminChatActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-x-3 px-3'">
                            <span class="flex gap-x-3 items-center">
                                <span class="material-icons text-xl shrink-0 text-sky-500">chat</span>
                                <span x-show="!sidebarCollapsed" class="truncate">Pesan Ortu</span>
                            </span>
                            @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                                <span x-show="!sidebarCollapsed" class="inline-flex items-center justify-center h-4.5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold shadow-xs">{{ $totalUnreadMessagesCount }}</span>
                            @elseif($isAdminChatActive)
                                <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                            @endif
                        </a>
                    </li>

                    {{-- Laporan Dropdown --}}
                    @php 
                        $isStudentReportActive = request()->routeIs(['admin.reports.create', 'admin.reports.charts', 'admin.reports.generate']);
                        $isTeacherReportActive = request()->routeIs('admin.reports.teacher.*');
                        $isReportOpen = $isStudentReportActive || $isTeacherReportActive;
                    @endphp
                    <li x-data="{ open: {{ $isReportOpen ? 'true' : 'false' }} }">
                        <button @click="open = !open" :title="sidebarCollapsed ? 'Laporan' : ''" 
                                class="group flex items-center w-full rounded-xl p-2.5 text-xs transition-all duration-200 {{ $isReportOpen ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                            <span class="material-icons text-xl shrink-0">bar_chart</span>
                            <span x-show="!sidebarCollapsed" class="truncate">Laporan Presensi</span>
                            <span x-show="!sidebarCollapsed" class="material-icons text-base ml-auto transition-transform duration-200 opacity-60" :class="{ 'rotate-90': open }">chevron_right</span>
                        </button>
                        <ul x-show="open && !sidebarCollapsed" x-transition class="mt-1 ml-4 pl-3 space-y-1 border-l-2 border-slate-200 dark:border-slate-700/80">
                            <li>
                                <a href="{{ route('admin.reports.create') }}" class="{{ $isStudentReportActive ? 'text-sky-600 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-950/50' : 'text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-slate-200' }} flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <span>Laporan Siswa</span>
                                    @if($isStudentReportActive)
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.reports.teacher.index') }}" class="{{ $isTeacherReportActive ? 'text-sky-600 dark:text-sky-400 font-bold bg-sky-50 dark:bg-sky-950/50' : 'text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-slate-200' }} flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <span>Laporan Guru</span>
                                    @if($isTeacherReportActive)
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>

                    @if(auth()->user()->hasRole('admin'))
                        @php $isSettingsActive = request()->routeIs(['admin.settings.*']); @endphp
                        <li>
                            <a href="{{ route('admin.settings.appearance') }}" :title="sidebarCollapsed ? 'Tampilan & Logo' : ''" 
                               class="{{ $isSettingsActive ? 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 font-bold border border-sky-500/20 shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 border border-transparent' }} group flex items-center rounded-xl p-2.5 text-xs transition-all duration-200" 
                               :class="sidebarCollapsed ? 'justify-center px-2' : 'gap-x-3 px-3'">
                                <span class="material-icons text-xl shrink-0">palette</span>
                                <span x-show="!sidebarCollapsed" class="truncate">Tampilan & Logo</span>
                                @if($isSettingsActive)
                                    <span x-show="!sidebarCollapsed" class="w-1.5 h-1.5 rounded-full bg-sky-500 ml-auto shrink-0"></span>
                                @endif
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
            @endif

        </ul>
    </nav>

    <!-- Bottom User Profile Card (Sidebar Footer) -->
    @auth
        <div class="mt-auto pt-3 border-t border-slate-100 dark:border-slate-800/80">
            <div class="flex items-center rounded-xl p-2 bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 transition-all"
                 :class="sidebarCollapsed ? 'justify-center' : 'gap-x-3'">
                <img class="h-8 w-8 rounded-full object-cover ring-2 ring-sky-500/30 flex-shrink-0" 
                     src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=0284c7&background=e0f2fe' }}" 
                     alt="{{ Auth::user()->name }}">
                <div x-show="!sidebarCollapsed" class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] font-semibold text-sky-600 dark:text-sky-400 capitalize leading-tight mt-0.5 truncate">{{ Auth::user()->role }}</p>
                </div>
            </div>
        </div>
    @endauth
</div>

