<div x-data="{
    darkMode: localStorage.getItem('darkMode') === 'on' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        if (this.darkMode) {
            localStorage.setItem('darkMode', 'on');
            document.documentElement.classList.add('dark');
        } else {
            localStorage.setItem('darkMode', 'off');
            document.documentElement.classList.remove('dark');
        }
    },
    showLogoutConfirm: false
}" @keydown.escape.window="showLogoutConfirm = false" class="flex items-center gap-x-2 sm:gap-x-3.5">
    
    {{-- Global Academic Period Pill Switcher --}}
    @if(isset($globalSemesters) && $globalSemesters->count() > 0)
    <div class="hidden sm:flex items-center">
        <form action="{{ route('set-academic-period') }}" method="POST" id="global-switcher-form" class="m-0">
            @csrf
            <div class="relative flex items-center">
                <span class="material-icons absolute left-2.5 text-slate-400 dark:text-slate-500 text-sm pointer-events-none">event_note</span>
                <select name="semester_id" onchange="document.getElementById('global-switcher-form').submit()" 
                        class="block w-full rounded-xl border border-slate-200 dark:border-slate-700/80 py-1.5 pl-8 pr-7 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-50/80 dark:bg-slate-800/80 hover:bg-white dark:hover:bg-slate-800 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 transition-all cursor-pointer shadow-2xs">
                    @foreach($globalSemesters as $semester)
                        <option value="{{ $semester->id }}" {{ (isset($globalActiveSemesterId) && $globalActiveSemesterId == $semester->id) ? 'selected' : '' }}>
                            TA. {{ $semester->academicYear->name ?? '' }} - {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
    @endif

    {{-- Action & Notification Icon Buttons --}}
    <div class="flex items-center gap-x-1.5 sm:gap-x-1.5">
        
        {{-- Notifications for Kepala Sekolah / Wakasek Kurikulum --}}
        @if(auth()->user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum', 'waka_kurikulum', 'waka kurikulum']))
            @php
                $topbarPendingJournals = 0;
                if (\Illuminate\Support\Facades\Schema::hasTable('teaching_journals') && \Illuminate\Support\Facades\Schema::hasColumn('teaching_journals', 'is_verified')) {
                    try {
                        $topbarPendingJournals = \App\Models\TeachingJournal::where('is_verified', false)->count();
                    } catch (\Throwable $e) {
                        $topbarPendingJournals = 0;
                    }
                }
            @endphp
            <a href="{{ route('admin.teaching_journals.index') }}" 
               class="relative inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors" 
               title="Supervisi Jurnal Guru">
                <span class="material-icons text-xl sm:text-lg">verified_user</span>
                @if($topbarPendingJournals > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-amber-500 text-slate-950 text-[9px] font-black shadow-xs animate-pulse">{{ $topbarPendingJournals }}</span>
                @endif
            </a>

            <a href="{{ route('principal.dashboard') }}" 
               class="relative inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors" 
               title="Dasbor Eksekutif">
                <span class="material-icons text-xl sm:text-lg">account_balance</span>
            </a>
        @endif

        {{-- Notifications for Admin / Staff --}}
        @if(auth()->user()->hasAnyRole(['admin', 'operator']) && !auth()->user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster']))
            <a href="{{ route('admin.leave_requests.index') }}" 
               class="relative inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" 
               title="Persetujuan Izin Siswa">
                <span class="material-icons text-xl sm:text-lg">assignment_turned_in</span>
                @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-white text-[9px] font-bold shadow-xs animate-pulse">{{ $pendingLeaveRequestsCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.chat.index') }}" 
               class="relative inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" 
               title="Pesan Orang Tua">
                <span class="material-icons text-xl sm:text-lg">chat</span>
                @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-white text-[9px] font-bold shadow-xs">{{ $totalUnreadMessagesCount }}</span>
                @endif
            </a>
        @endif
        
        {{-- Notifications for Teacher & Parent --}}
        @if((auth()->user()->role === 'teacher' && auth()->user()->teacher?->homeroomClass) || auth()->user()->role === 'parent')
            
            {{-- Quick Create Leave Request (Parent) --}}
            @if(auth()->user()->role === 'parent')
            <a href="{{ route('parent.leave-requests.create') }}" 
               class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200/60 dark:border-amber-900/40 text-xs font-bold transition-all shadow-2xs" 
               title="Buat Pengajuan Izin Baru">
                <span class="material-icons text-base text-amber-600 dark:text-amber-400">add_circle_outline</span>
                <span>Izin / Sakit</span>
            </a>
            @endif

            {{-- Homeroom Teacher Leave Approvals --}}
            @if(auth()->user()->role === 'teacher' && auth()->user()->teacher?->homeroomClass)
            <a href="{{ route('teacher.leave_requests.index') }}" 
               class="relative inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" 
               title="Pengajuan Izin Masuk">
                <span class="material-icons text-xl sm:text-lg">assignment_turned_in</span>
                @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-white text-[9px] font-bold shadow-xs animate-pulse">{{ $teacherPendingLeaveRequestsCount }}</span>
                @endif
            </a>
            @endif

            {{-- Chat Notification --}}
            <a href="{{ route('chat.index') }}" 
               class="relative inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" 
               title="Obrolan">
                <span class="material-icons text-xl sm:text-lg">chat</span>
                @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-white text-[9px] font-bold shadow-xs">{{ $totalUnreadMessagesCount }}</span>
                @endif
            </a>
        @endif

        {{-- Dark Mode Toggle Button --}}
        <button @click="toggleDarkMode()" type="button" 
                class="inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                title="Ganti Mode Terang / Gelap">
            <span class="sr-only">Ganti Tema</span>
            <span x-show="!darkMode" class="material-icons text-xl text-amber-500">dark_mode</span>
            <span x-show="darkMode" style="display: none;" class="material-icons text-xl text-sky-400">light_mode</span>
        </button>
    </div>
    
    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>

    {{-- User Profile Pill Trigger & Dropdown --}}
    @auth
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" 
                    class="flex items-center gap-2 p-1.5 sm:p-1 pl-1.5 pr-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-700 min-h-[40px] sm:min-h-[36px]">
                <div class="relative">
                    <img class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20 bg-slate-200 dark:bg-slate-750" 
                         src="{{ Auth::user()->profile_photo_url }}" 
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=818cf8&background=e0e7ff';" 
                         alt="{{ Auth::user()->name }}">
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                </div>

                <div class="hidden md:flex flex-col text-left leading-tight">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                    @php
                        $displayRole = Auth::user()->role;
                        if (Auth::user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster'])) {
                            $displayRole = 'Kepala Sekolah';
                        } elseif (Auth::user()->hasAnyRole(['wakasek_kurikulum', 'wakasek kurikulum', 'waka_kurikulum'])) {
                            $displayRole = 'Wakasek Kurikulum';
                        } else {
                            $displayRole = str_replace('_', ' ', ucwords(Auth::user()->role, '_'));
                        }
                    @endphp
                    <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 capitalize">{{ $displayRole }}</span>
                </div>
                
                <span class="material-icons text-slate-400 text-base hidden sm:inline-block transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" @click.away="open = false" x-transition 
                 class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-2xl bg-white dark:bg-slate-850 p-2 shadow-xl ring-1 ring-slate-900/10 dark:ring-slate-800 focus:outline-none border border-slate-100 dark:border-slate-800" 
                 style="display: none;">
                
                <div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800 mb-1">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ Auth::user()->email }}</p>
                </div>

                <div class="space-y-0.5">
                    @if(Auth::user()->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster', 'wakasek_kurikulum', 'wakasek kurikulum']))
                        <a href="{{ route('principal.dashboard') }}" 
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors">
                            <span class="material-icons text-base text-indigo-500">account_balance</span>
                            Dasbor Eksekutif
                        </a>

                        <a href="{{ route('admin.teaching_journals.index') }}" 
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-icons text-base text-amber-500">verified_user</span>
                            Supervisi Jurnal
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-icons text-base text-slate-400">manage_accounts</span>
                        Profil & Akun
                    </a>

                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.settings.appearance') }}" 
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-icons text-base text-slate-400">palette</span>
                            Tema & Logo
                        </a>
                    @endif

                    <a href="#" @click.prevent="open = false; showLogoutConfirm = true" 
                       class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                        <span class="material-icons text-base text-rose-500">logout</span>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    @endauth

    {{-- Form Logout Tersembunyi --}}
    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
        @csrf
    </form>

    {{-- Modal Konfirmasi Logout (Teleported to body for precise full-screen centering) --}}
    <template x-teleport="body">
        <div x-show="showLogoutConfirm" 
             x-cloak
             style="display: none;" 
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
            
            <!-- Backdrop Overlay -->
            <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" 
                 x-show="showLogoutConfirm"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showLogoutConfirm = false"></div>

            <!-- Modal Content Box -->
            <div @click.away="showLogoutConfirm = false" 
                 x-show="showLogoutConfirm" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative w-full max-w-sm p-6 bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 text-center z-10 transform transition-all">
                
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 mb-4 ring-8 ring-rose-500/10">
                    <span class="material-icons text-3xl">logout</span>
                </div>
                
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfirmasi Keluar</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin mengakhiri sesi login ini?
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <button type="button" @click="showLogoutConfirm = false" 
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="$refs.logoutForm.submit()" 
                            class="flex-1 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/25 transition-all active:scale-95">
                        Ya, Keluar
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>


