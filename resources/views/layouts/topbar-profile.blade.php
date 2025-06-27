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
    }
}" class="flex items-center gap-x-4 lg:gap-x-6">
    
    {{-- Tombol Notifikasi & Dark Mode --}}
    <div class="flex items-center gap-x-2">
        {{-- Notifikasi untuk Admin --}}
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.leave_requests.index') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">
                <span class="sr-only">Lihat notifikasi</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">
                    {{ $pendingLeaveRequestsCount }}
                </span>
                @endif
            </a>
            <button @click="toggleDarkMode()" type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">
                <span class="sr-only">Ganti Tema</span>
                <svg x-show="!darkMode" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
                <svg x-show="darkMode" style="display: none;" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.95-4.223-1.591 1.591M5.25 12H3m4.223-4.95L6.343 6.343M12 6a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z" />
                </svg>
            </button>
        @endif
        
        {{-- Notifikasi untuk Guru Wali Kelas --}}
        @if(auth()->user()->role === 'teacher' && auth()->user()->teacher?->homeroomClass)
            <a href="{{ route('teacher.leave_requests.index') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">
                <span class="sr-only">Lihat notifikasi</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $teacherPendingLeaveRequestsCount }}</span>
                @endif
            </a>
        @endif
    </div>
    
    <!-- Separator -->
    <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200 dark:lg:bg-slate-700" aria-hidden="true"></div>

    <!-- Menu Profil Pengguna -->
    @auth
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="-m-1.5 flex items-center p-1.5">
                <span class="inline-block h-8 w-8 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700">
                    <svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </span>
                <span class="hidden lg:flex lg:items-center">
                    <span class="ml-4 text-sm font-semibold leading-6 text-gray-900 dark:text-white" aria-hidden="true">{{ Auth::user()->name }}</span>
                    <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </span>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white dark:bg-slate-700 py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none" style="display: none;">
                <a href="{{ route('profile.edit') }}" class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-600">Profil Anda</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-600">Log out</a>
                </form>
            </div>
        </div>
    @endauth
</div>
