<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Panel Kiri (Gambar) -->
        <div class="relative hidden w-0 flex-1 lg:block">
            <img class="absolute inset-0 h-full w-full object-cover" src="https://images.unsplash.com/photo-1594608661623-aa0bd3a69d98?q=80&w=2798&auto=format&fit=crop" alt="Gedung sekolah modern yang cerah">
            <div class="absolute inset-0 bg-sky-900/40"></div>
             <div class="absolute bottom-0 left-0 p-12 text-white">
                 <h2 class="text-3xl font-bold leading-snug">Absensi Lebih Mudah, Data Lebih Akurat.</h2>
                 <p class="mt-2 text-sky-200/80">Selamat datang kembali di platform absensi andalan Anda.</p>
             </div>
        </div>

        <!-- Panel Kanan (Form) -->
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-slate-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                       {{-- PERBAIKAN: Menampilkan nama aplikasi dan nama sekolah --}}
                     <a href="{{ route('welcome') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-auto text-sky-600 dark:text-sky-500" />
                        <div>
                            <p class="font-bold text-xl text-slate-800 dark:text-white tracking-tight leading-tight">{{ config('app.name', 'AbsensiSiswa') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-tight">{{ $appName ?? 'Nama Sekolah Anda' }}</p>
                        </div>
                    </a>
                    <h2 class="mt-8 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Selamat Datang Kembali</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="font-medium text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">Daftar di sini</a>
                    </p>
                </div>

                <div class="mt-10">
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        <!-- Alamat Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <div class="mt-2">
                                <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <div class="mt-2">
                                <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" />
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Ingat Saya & Lupa Password -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-600 dark:bg-slate-800 dark:border-slate-600">
                                <label for="remember_me" class="ml-3 block text-sm leading-6 text-gray-900 dark:text-gray-300">Ingat saya</label>
                            </div>
                            @if (Route::has('password.request'))
                                <div class="text-sm">
                                    <a href="{{ route('password.request') }}" class="font-semibold text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">Lupa password?</a>
                                </div>
                            @endif
                        </div>

                        <!-- Tombol Submit -->
                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md bg-sky-600 px-3 py-3 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 transition">
                                Login
                            </button>
                        </div>
                    </form>
                </div>
                 <div class="mt-8 text-center">
                    <a href="{{ route('welcome') }}" class="text-sm font-medium text-slate-600 hover:text-sky-500 dark:text-slate-400 dark:hover:text-sky-400">
                        &larr; Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
