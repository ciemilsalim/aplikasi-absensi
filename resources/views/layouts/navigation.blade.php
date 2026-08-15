<nav x-data="{ open: false, showLogoutConfirm: false }" @keydown.escape.window="showLogoutConfirm = false" 
     class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 transition-colors">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-sky-500/10 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                            <span class="material-icons text-xl">fingerprint</span>
                        </div>
                        <div>
                            <span class="block font-extrabold text-base text-slate-900 dark:text-white tracking-tight leading-none">
                                {{ config('app.name', 'Presensi') }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-semibold leading-none">Sistem Absensi Terpadu</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden lg:flex lg:items-center lg:gap-1.5">
                    @auth
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" 
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="material-icons text-base">dashboard</span>
                                <span>Dasbor</span>
                            </a>
                        @endif
                        @if(auth()->user()->role === 'parent')
                            <a href="{{ route('parent.dashboard') }}" 
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('parent.dashboard') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="material-icons text-base">family_restroom</span>
                                <span>Dasbor Anak</span>
                            </a>
                            <a href="{{ route('parent.leave-requests.index') }}" 
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('parent.leave-requests.*') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="material-icons text-base">event_note</span>
                                <span>Izin & Sakit</span>
                            </a>
                        @endif
                        @if(auth()->user()->role === 'teacher')
                            <a href="{{ route('teacher.dashboard') }}" 
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('teacher.dashboard') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="material-icons text-base">dashboard</span>
                                <span>Dasbor Guru</span>
                            </a>
                            <a href="{{ route('scanner') }}" 
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('scanner') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="material-icons text-base">qr_code_scanner</span>
                                <span>Scan Masuk/Pulang</span>
                            </a>
                            <a href="{{ route('permit.scanner') }}" 
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('permit.scanner') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="material-icons text-base">assignment</span>
                                <span>Scan Izin Keluar</span>
                            </a>
                            @if(Auth::user()->teacher && Auth::user()->teacher->homeroomClass)
                                <a href="{{ route('teacher.leave_requests.index') }}" 
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('teacher.leave_requests.*') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span class="material-icons text-base">assignment_turned_in</span>
                                    <span>Persetujuan Izin</span>
                                </a>
                                <a href="{{ route('teacher.attendance.history') }}" 
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all {{ request()->routeIs('teacher.attendance.history') ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span class="material-icons text-base">calendar_view_month</span>
                                    <span>Matriks Kelas</span>
                                </a>
                            @endif
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown / Public Auth Buttons -->
            <div class="hidden lg:flex lg:items-center lg:gap-3">
                @auth
                    <div x-data="{ dropdownOpen: false }" class="relative">
                        <button @click="dropdownOpen = !dropdownOpen" 
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-850 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700 transition shadow-2xs">
                            <div class="w-6 h-6 rounded-xl bg-sky-600 text-white text-[10px] font-bold flex items-center justify-center">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span>{{ Auth::user()->name }}</span>
                            <span class="material-icons text-sm text-slate-400">expand_more</span>
                        </button>
                        <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition 
                             class="absolute right-0 mt-2 w-52 rounded-2xl shadow-xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-700 py-1.5 z-50 text-xs font-semibold" style="display: none;">
                            <a href="{{ route('profile.edit') }}" 
                               class="flex items-center gap-2.5 px-4 py-2.5 text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800 transition">
                                <span class="material-icons text-base text-slate-400">person_outline</span>
                                <span>Profil Pengguna</span>
                            </a>
                            <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                            <a href="#" @click.prevent="showLogoutConfirm = true; dropdownOpen = false" 
                               class="flex items-center gap-2.5 px-4 py-2.5 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                <span class="material-icons text-base text-rose-500">logout</span>
                                <span>Keluar (Log Out)</span>
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <span class="material-icons text-sm">login</span>
                        <span>Masuk</span>
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all active:scale-95">
                            <span class="material-icons text-sm">person_add</span>
                            <span>Daftar Akun</span>
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger Button (Mobile) -->
            <div class="-mr-2 flex items-center lg:hidden">
                <button @click="open = ! open" 
                        class="p-2 rounded-2xl text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition">
                    <span class="material-icons text-2xl" x-text="open ? 'close' : 'menu'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Mobile Drawer Menu -->
    <div x-show="open" x-transition class="lg:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 pt-3 pb-5 space-y-3">
        <div class="space-y-1">
            @if(Auth::guest() || (Auth::check() && in_array(auth()->user()->role, ['admin', 'operator', 'teacher'])))
                <a href="{{ route('scanner') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                    <span class="material-icons text-base text-sky-500">qr_code_scanner</span>
                    <span>Pemindai Kiosk</span>
                </a>
            @endif
            @auth
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">dashboard</span>
                        <span>Dasbor Admin</span>
                    </a>
                    <a href="{{ route('admin.leave_requests.index') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">assignment_turned_in</span>
                        <span>Persetujuan Izin</span>
                    </a>
                    <a href="{{ route('admin.reports.create') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">picture_as_pdf</span>
                        <span>Laporan Presensi</span>
                    </a>
                @endif
                @if(auth()->user()->role === 'parent')
                    <a href="{{ route('parent.dashboard') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">family_restroom</span>
                        <span>Dasbor Anak</span>
                    </a>
                    <a href="{{ route('parent.leave-requests.index') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">event_note</span>
                        <span>Pengajuan Izin/Sakit</span>
                    </a>
                @endif
                @if(auth()->user()->role === 'teacher')
                    <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">dashboard</span>
                        <span>Dasbor Guru</span>
                    </a>
                    <a href="{{ route('scanner') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-sky-500">qr_code_scanner</span>
                        <span>Scan Masuk/Pulang</span>
                    </a>
                    <a href="{{ route('permit.scanner') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-800">
                        <span class="material-icons text-base text-indigo-500">assignment</span>
                        <span>Scan Izin Keluar</span>
                    </a>
                    @if(Auth::user()->teacher && Auth::user()->teacher->homeroomClass)
                        <a href="{{ route('teacher.leave_requests.index') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                            <span class="material-icons text-base text-sky-500">assignment_turned_in</span>
                            <span>Validasi Izin Siswa</span>
                        </a>
                        <a href="{{ route('teacher.attendance.history') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800">
                            <span class="material-icons text-base text-sky-500">calendar_view_month</span>
                            <span>Riwayat Matriks Kelas</span>
                        </a>
                    @endif
                @endif
            @endauth
        </div>

        <!-- Responsive User Profile / Auth Area -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
            @auth
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="w-9 h-9 rounded-2xl bg-sky-600 text-white font-bold text-xs flex items-center justify-center">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-xs text-slate-900 dark:text-white">{{ Auth::user()->name }}</div>
                        <div class="text-[11px] text-slate-400">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3.5 py-2 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <span class="material-icons text-sm text-slate-400">person_outline</span>
                        <span>Profil Pengguna</span>
                    </a>
                    <button @click="showLogoutConfirm = true; open = false" type="button" 
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 rounded-2xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                        <span class="material-icons text-sm">logout</span>
                        <span>Log Out</span>
                    </button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-2 pt-2">
                    <a href="{{ route('login') }}" class="flex items-center justify-center py-2.5 px-3 rounded-2xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300">
                        Masuk
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="flex items-center justify-center py-2.5 px-3 rounded-2xl bg-sky-600 text-white text-xs font-bold shadow-sm">
                            Daftar
                        </a>
                    @endif
                </div>
            @endauth
        </div>
    </div>

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
                 class="relative bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-2xl border border-slate-200/80 dark:border-slate-800 max-w-sm w-full text-center z-10 transform transition-all">
                
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 mb-4 ring-8 ring-rose-500/10">
                    <span class="material-icons text-3xl">logout</span>
                </div>
                
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfirmasi Keluar</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin keluar dari sesi akun ini?
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <button @click="showLogoutConfirm = false" type="button" 
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors">
                        Batal
                    </button>
                    <button @click="$refs.logoutForm.submit()" type="button" 
                            class="flex-1 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-600/25 transition-all active:scale-95">
                        Ya, Keluar
                    </button>
                </div>
            </div>
        </div>
    </template>
</nav>


