{{-- PERBAIKAN: Menambahkan state 'showLogoutConfirm' untuk modal --}}
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
}" @keydown.escape.window="showLogoutConfirm = false" class="flex items-center gap-x-4 lg:gap-x-6">
    
    {{-- Tombol Notifikasi & Dark Mode --}}
    <div class="flex items-center gap-x-2">
        {{-- Notifikasi untuk Admin --}}
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.leave_requests.index') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" title="Pengajuan Izin">
                <span class="sr-only">Lihat notifikasi pengajuan izin</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                {{-- Variabel dari LogoServiceProvider --}}
                @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $pendingLeaveRequestsCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.chat.index') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" title="Pesan Ortu">
                <span class="sr-only">Lihat notifikasi obrolan</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.397 48.397 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                {{-- Variabel dari LogoServiceProvider --}}
                @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $totalUnreadMessagesCount }}</span>
                @endif
            </a>
            <button @click="toggleDarkMode()" type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">
                <span class="sr-only">Ganti Tema</span>
                <svg x-show="!darkMode" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                <svg x-show="darkMode" style="display: none;" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.95-4.223-1.591 1.591M5.25 12H3m4.223-4.95L6.343 6.343M12 6a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z" /></svg>
            </button>
        @endif
        
        {{-- Notifikasi untuk Guru (Wali Kelas) & Ortu --}}
        @if(auth()->user()->role === 'teacher' && auth()->user()->teacher?->homeroomClass || auth()->user()->role === 'parent')
            
            {{-- IKON UNTUK ORANG TUA: Membuat pengajuan izin --}}
            @if(auth()->user()->role === 'parent')
            <a href="{{ route('parent.leave-requests.create') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" title="Buat Pengajuan Izin">
                <span class="sr-only">Buat Pengajuan Izin</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </a>
            @endif

            {{-- IKON UNTUK GURU WALI KELAS: Melihat pengajuan izin --}}
            @if(auth()->user()->role === 'teacher' && auth()->user()->teacher?->homeroomClass)
            <a href="{{ route('teacher.leave_requests.index') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" title="Pengajuan Izin Masuk">
                <span class="sr-only">Lihat notifikasi pengajuan izin</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                {{-- MENGGUNAKAN VARIABEL YANG BENAR DARI LogoServiceProvider --}}
                @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $teacherPendingLeaveRequestsCount }}</span>
                @endif
            </a>
            @endif

            {{-- IKON OBROLAN (Chat) --}}
            <a href="{{ route('chat.index') }}" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" title="Obrolan">
                <span class="sr-only">Lihat notifikasi obrolan</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.397 48.397 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                {{-- Variabel dari LogoServiceProvider --}}
                @if(isset($totalUnreadMessagesCount) && $totalUnreadMessagesCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">{{ $totalUnreadMessagesCount }}</span>
                @endif
            </a>
        @endif
    </div>
    
    <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200 dark:lg:bg-slate-700" aria-hidden="true"></div>

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
                <a href="#" @click.prevent="showLogoutConfirm = true" class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-600">Log out</a>
            </div>
        </div>
    @endauth

    {{-- Form Logout Tersembunyi --}}
    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
        @csrf
    </form>

    {{-- Modal Konfirmasi Logout --}}
    <div x-show="showLogoutConfirm" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" style="display: none;">
        <div @click.away="showLogoutConfirm = false" x-show="showLogoutConfirm" x-transition class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                </div>
                <h3 class="mt-5 text-lg font-medium text-gray-900 dark:text-white">Konfirmasi Log Out</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Apakah Anda yakin ingin keluar dari sesi ini?
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-center gap-4">
                <x-secondary-button @click="showLogoutConfirm = false">
                    Batal
                </x-secondary-button>
                <x-danger-button @click="$refs.logoutForm.submit()">
                    Ya, Log Out
                </x-danger-button>
            </div>
        </div>
    </div>
</div>
