<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Panel Kiri (Gambar) - Tersembunyi di mobile -->
        <div class="relative hidden w-0 flex-1 lg:block bg-sky-50 dark:bg-slate-800 overflow-hidden">
            <style>
                @keyframes float-elegant-register {
                    0%, 100% { transform: translateY(0) scale(1.05); }
                    50% { transform: translateY(-12px) scale(1.07); }
                }
                .animate-float-elegant-register {
                    animation: float-elegant-register 8s ease-in-out infinite;
                }
            </style>
             <div class="absolute inset-0 flex items-center justify-center">
                 <img class="absolute inset-0 h-full w-full object-cover animate-float-elegant-register" src="{{ asset('images/register-illustration.png') }}" alt="Ilustrasi orang tua mendaftar secara online">
             </div>
             <div class="absolute inset-0 bg-gradient-to-t from-sky-900/80 via-sky-900/20 to-transparent"></div>
             <div class="absolute bottom-0 left-0 p-12 text-white z-10">
                 <h2 class="text-3xl font-bold drop-shadow-md">Bergabung dengan Komunitas Kami.</h2>
                 <p class="mt-2 text-sky-100 drop-shadow">Daftarkan diri Anda untuk mulai mengelola kehadiran dengan lebih baik.</p>
             </div>
        </div>

        <!-- Panel Kanan (Form) -->
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-slate-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                       {{-- PERBAIKAN: Menampilkan nama aplikasi dan nama sekolah --}}
                     <a href="{{ route('login') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-auto text-sky-600 dark:text-sky-500" />
                        <div>
                            <p class="font-bold text-xl text-slate-800 dark:text-white tracking-tight leading-tight">{{ config('app.name', 'Presensi') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-tight">{{ $appName ?? 'Nama Sekolah Anda' }}</p>
                        </div>
                    </a>
                    <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Buat Akun Baru</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="font-medium text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">Login di sini</a>
                    </p>
                </div>
                    

                <div class="mt-8">
                    {{-- PERBAIKAN: Menambahkan class 'space-y-6' untuk spasi yang konsisten --}}
                    <form method="POST" action="{{ route('register') }}" class="space-y-6">
                        @csrf
                        <!-- Nama -->
                        <div>
                            <x-input-label for="name" :value="__('Nama Lengkap')" />
                            <div class="mt-2">
                                <x-text-input id="name" class="block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                            </div>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <!-- Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                             <div class="mt-2">
                                <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <!-- Password -->
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                             <div class="mt-2">
                                <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" />
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <!-- Konfirmasi Password -->
                        <div>
                            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
                            <div class="mt-2">
                                <x-text-input id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>
                        <!-- Tombol Submit -->
                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md bg-sky-600 px-3 py-3 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 transition">
                                Daftar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Link Kembali ke Beranda BARU --}}
                <div class="mt-8 text-center">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-sky-500 dark:text-slate-400 dark:hover:text-sky-400">
                        &larr; Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
