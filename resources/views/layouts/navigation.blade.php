<nav x-data="{ open: false }" class="bg-white shadow-sm border-b border-slate-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('scanner') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-sky-600" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:-my-px sm:ml-10 sm:flex">
                    <a href="{{ route('scanner') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out
                        {{ request()->routeIs('scanner') ? 'border-b-2 border-sky-500 text-sky-700' : 'text-slate-600 hover:text-slate-800 focus:outline-none' }}">
                        Pemindai
                    </a>
                    <a href="{{ route('students.list') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out
                        {{ request()->routeIs('students.list') ? 'border-b-2 border-sky-500 text-sky-700' : 'text-slate-600 hover:text-slate-800 focus:outline-none' }}">
                        Daftar Siswa
                    </a>
                    
                    @auth
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                {{ request()->routeIs('admin.dashboard*') ? 'border-b-2 border-sky-500 text-sky-700' : 'text-slate-600 hover:text-slate-800 focus:outline-none' }}">
                                Dasbor Admin
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown atau Link Login/Register -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @auth
                    <!-- Dropdown Pengguna (Telah Diperbaiki) -->
                    <div x-data="{ dropdownOpen: false }" class="relative">
                        <button @click="dropdownOpen = !dropdownOpen" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-600 bg-white hover:text-slate-800 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" :class="{'rotate-180': dropdownOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </div>
                        </button>

                        <div x-show="dropdownOpen" 
                             @click.away="dropdownOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 rounded-md shadow-lg origin-top-right z-50"
                             style="display: none;">
                            <div class="rounded-md ring-1 ring-black ring-opacity-5 py-1 bg-white">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-sky-50 hover:text-sky-700">
                                    Profil
                                </a>

                                <!-- Form Logout -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="{{ route('logout') }}" 
                                       class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-sky-50 hover:text-sky-700"
                                       onclick="event.preventDefault(); this.closest('form').submit();">
                                        Log Out
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Link Login & Register -->
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-sky-600">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 px-4 py-2 rounded-md">Register</a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 focus:text-slate-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width-2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        {{-- Konten responsive menu tetap sama --}}
    </div>
</nav>

{{-- SCRIPT ALPINE.JS DITAMBAHKAN DI SINI UNTUK MEMASTIKAN DROPDOWN BERFUNGSI --}}
{{-- Sebaiknya script ini ada di layout utama, tetapi ditambahkan di sini agar komponen ini mandiri --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
