<nav x-data="{ open: false }" class="bg-white dark:bg-slate-800 shadow-sm border-b border-gray-100 dark:border-slate-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
               <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : route('welcome') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-9 w-auto" />
                        {{-- PERBAIKAN: Menampilkan nama aplikasi di mobile --}}
                        <span class="block font-bold text-xl text-slate-800 dark:text-white tracking-tight">
                            {{ config('app.name', 'AbsensiSiswa') }}
                        </span>
                    </a>
                </div>

                <!-- Navigation Links (Desktop - tampil di layar besar 'lg') -->
                <div class="hidden space-x-1 lg:-my-px lg:ml-10 lg:flex">
                    
                    @auth
                        {{-- PERBAIKAN: Memberikan akses pemindai ke semua guru --}}
                        @if(in_array(auth()->user()->role, ['admin', 'operator', 'teacher']))
                            <a href="{{ route('scanner') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('scanner') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                                Pemindai
                            </a>
                        @endif
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('admin.dashboard') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                Dasbor
                            </a>
                        @endif
                        @if(auth()->user()->role === 'parent')
                             <a href="{{ route('parent.dashboard') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('parent.dashboard') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                Dasbor Anak
                            </a>
                            <a href="{{ route('parent.leave-requests.index') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('parent.leave-requests.*') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                Izin/Sakit
                            </a>
                        @endif
                         @if(auth()->user()->role === 'teacher')
                            <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('teacher.dashboard') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">Dasbor</a>
                            @if(Auth::user()->teacher && Auth::user()->teacher->homeroomClass)
                                <a href="{{ route('teacher.leave_requests.index') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('teacher.leave_requests.*') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg>
                                    Pengajuan Izin
                                </a>
                                {{-- === TAUTAN BARU UNTUK DESKTOP === --}}
                                <a href="{{ route('teacher.attendance.history') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition {{ request()->routeIs('teacher.attendance.history') ? 'border-b-2 border-sky-500 text-sky-700 dark:text-sky-400' : 'text-slate-600 dark:text-gray-400 hover:text-slate-800 dark:hover:text-gray-200' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" /></svg>
                                    Riwayat Kehadiran
                                </a>
                            @endif
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown atau Link Login/Register -->
            <div class="hidden lg:flex lg:items-center lg:ml-6">
                @auth
                    <div x-data="{ dropdownOpen: false }" class="relative">
                        <button @click="dropdownOpen = !dropdownOpen" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-600 dark:text-gray-300 bg-white dark:bg-slate-800 hover:text-slate-800 dark:hover:text-gray-200 focus:outline-none transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-slate-500 dark:text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ml-1"><svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></div>
                        </button>
                        <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg origin-top-right z-50" style="display: none;">
                            <div class="rounded-md ring-1 ring-black ring-opacity-5 py-1 bg-white dark:bg-slate-700">
                                <a href="{{ route('profile.edit') }}" class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-slate-600 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-slate-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                                        Log Out
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center text-sm font-medium text-slate-600 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" /></svg>
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 inline-flex items-center text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 px-4 py-2 rounded-md">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3.375 19.5h17.25a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H3.375c-1.24 0-2.25 1.01-2.25 2.25v10.5a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            Register
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger (tampil di layar kecil & tablet) -->
            <div class="-mr-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </div>

<!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden" x-show="open" x-transition>
        <div class="pt-2 pb-3 space-y-1">
            @if(Auth::guest() || (Auth::check() && (Auth::user()->role === 'admin' || (Auth::user()->role === 'teacher' || (Auth::user()->role === 'operator')))))
                {{-- PERBAIKAN: Memberikan akses pemindai ke semua guru --}}
                <x-responsive-nav-link :href="route('scanner')" :active="request()->routeIs('scanner')">Pemindai</x-responsive-nav-link>
            @endif
            @auth
                @if(auth()->user()->role === 'admin')
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dasbor</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.parents.index')" :active="request()->routeIs('admin.parents.*')">Manajemen Ortu</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')">Data Guru</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.classes.index')" :active="request()->routeIs('admin.classes.*')">Data Kelas</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">Data Siswa</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.announcements.index')" :active="request()->routeIs('admin.announcements.*')">Pengumuman</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.leave_requests.index')" :active="request()->routeIs('admin.leave_requests.*')">Pengajuan Izin</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.reports.create')" :active="request()->routeIs('admin.reports.*')">Laporan</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.settings.identity')" :active="request()->routeIs('admin.settings.identity')">Pengaturan</x-responsive-nav-link>
                @endif
                @if(auth()->user()->role === 'parent')
                    <x-responsive-nav-link :href="route('parent.dashboard')" :active="request()->routeIs('parent.dashboard')">Dasbor Anak</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('parent.leave-requests.index')" :active="request()->routeIs('parent.leave-requests.*')">Izin/Sakit</x-responsive-nav-link>
                @endif
                @if(auth()->user()->role === 'teacher')
                    <x-responsive-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">Dasbor</x-responsive-nav-link>
                    @if(Auth::user()->teacher && Auth::user()->teacher->homeroomClass)
                        <x-responsive-nav-link :href="route('teacher.leave_requests.index')" :active="request()->routeIs('teacher.leave_requests.*')">Pengajuan Izin</x-responsive-nav-link>
                        {{-- === TAUTAN BARU UNTUK MOBILE === --}}
                        <x-responsive-nav-link :href="route('teacher.attendance.history')" :active="request()->routeIs('teacher.attendance.history')">Riwayat Kehadiran</x-responsive-nav-link>
                    @endif
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-slate-600">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('login')">Log in</x-responsive-nav-link>
                    @if (Route::has('register'))
                        <x-responsive-nav-link :href="route('register')">Register</x-responsive-nav-link>
                    @endif
                </div>
            @endauth
        </div>
    </div>
</nav>
