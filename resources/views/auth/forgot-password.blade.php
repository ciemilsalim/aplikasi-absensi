<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Panel Kiri (Gambar) - Tersembunyi di mobile -->
        <div class="relative hidden w-0 flex-1 lg:block">
            <img class="absolute inset-0 h-full w-full object-cover" src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=2940&auto=format&fit=crop" alt="Sekelompok siswa sedang belajar bersama">
            <div class="absolute inset-0 bg-sky-900/40"></div>
             <div class="absolute bottom-0 left-0 p-12 text-white">
                 <h2 class="text-3xl font-bold leading-snug">Jangan Khawatir, Kami Siap Membantu.</h2>
                 <p class="mt-2 text-sky-200/80">Cukup masukkan email Anda dan kami akan mengirimkan link untuk mereset password Anda.</p>
             </div>
        </div>

        <!-- Panel Kanan (Form) -->
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-slate-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                     <a href="{{ route('welcome') }}">
                        <x-application-logo class="h-12 w-auto text-sky-600 dark:text-sky-500" />
                    </a>
                    <h2 class="mt-8 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Lupa Password?</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Masukkan alamat email Anda yang terdaftar dan kami akan mengirimkan instruksi untuk mengatur ulang password Anda.
                    </p>
                </div>

                <div class="mt-10">
                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                        @csrf

                        <!-- Alamat Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <div class="mt-2">
                                <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Tombol Submit -->
                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md bg-sky-600 px-3 py-3 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 transition">
                                Kirim Link Reset Password
                            </button>
                        </div>
                    </form>
                </div>
                 <div class="mt-8 text-center">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-sky-500 dark:text-slate-400 dark:hover:text-sky-400">
                        &larr; Kembali ke Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
