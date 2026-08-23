<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Panel Kiri (Hero Showcase - Hidden on Mobile) -->
        <div class="relative hidden w-0 flex-1 lg:flex flex-col justify-between bg-slate-900 p-12 overflow-hidden group">
            <!-- Background Image with Gradient Overlay -->
            <div class="absolute inset-0 flex items-center justify-center">
                <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-[7000ms] ease-out group-hover:scale-105 opacity-50" 
                     src="{{ asset('images/register-illustration.png?v=2') }}" 
                     alt="Ilustrasi Registrasi Akun">
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-sky-950/40"></div>
            
            <!-- Top Branding -->
            <div class="relative z-10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-500/20 backdrop-blur-md border border-sky-400/30 flex items-center justify-center text-sky-400 shadow-lg">
                    <span class="material-icons text-xl">school</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-lg text-white tracking-tight leading-tight">{{ config('app.name', 'Presensi Siswa') }}</h3>
                    <p class="text-xs text-sky-300/80 font-medium">{{ $appName ?? 'Portal Kehadiran Digital' }}</p>
                </div>
            </div>

            <!-- Bottom Showcase Text -->
            <div class="relative z-10 max-w-lg">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-500/10 border border-sky-400/20 text-sky-300 text-xs font-semibold mb-4 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Registrasi Pengguna Baru</span>
                </div>
                <h2 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight tracking-tight drop-shadow-sm">
                    Bergabung dengan Komunitas Sekolah.
                </h2>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                    Daftarkan akun Anda untuk memantau absensi siswa, mengajukan izin secara digital, dan menerima notifikasi kehadiran real-time.
                </p>
            </div>
        </div>

        <!-- Panel Kanan (Form Registrasi) -->
        <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-slate-900">
            <div class="mx-auto w-full max-w-sm sm:w-96">
                
                <!-- Brand Header -->
                <div class="text-left">
                    <div class="flex items-center gap-3 mb-6">
                        <x-application-logo class="h-11 w-auto text-sky-600 dark:text-sky-400" />
                        <div>
                            <p class="font-extrabold text-lg text-slate-900 dark:text-white tracking-tight leading-tight">{{ config('app.name', 'Presensi') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-tight">{{ $appName ?? 'Portal Kehadiran Digital' }}</p>
                        </div>
                    </div>
                    
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        Buat Akun Baru
                    </h2>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="font-bold text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300 transition-colors">Masuk di sini</a>
                    </p>
                </div>

                <div class="mt-8" x-data="{ showPass: false, showConfirmPass: false }">
                    <form method="POST" action="{{ route('register') }}" class="space-y-4">
                        @csrf
                        
                        <!-- Nama -->
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Nama Lengkap
                            </label>
                            <div class="relative">
                                <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">person_outline</span>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                                       class="w-full text-xs font-semibold pl-10 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15"
                                       placeholder="Nama Lengkap Anda" />
                            </div>
                            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Alamat Email
                            </label>
                            <div class="relative">
                                <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">mail_outline</span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                                       class="w-full text-xs font-semibold pl-10 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15"
                                       placeholder="nama@email.com" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">lock_outline</span>
                                <input id="password" :type="showPass ? 'text' : 'password'" name="password" required autocomplete="new-password"
                                       class="w-full text-xs font-semibold pl-10 pr-11 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15"
                                       placeholder="Minimal 8 karakter" />
                                <button type="button" 
                                        @click="showPass = !showPass" 
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none"
                                        title="Tampilkan / Sembunyikan Kata Sandi">
                                    <span class="material-icons text-sm" x-text="showPass ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                        </div>

                        <!-- Konfirmasi Password -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Konfirmasi Kata Sandi
                            </label>
                            <div class="relative">
                                <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">lock_reset</span>
                                <input id="password_confirmation" :type="showConfirmPass ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                                       class="w-full text-xs font-semibold pl-10 pr-11 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15"
                                       placeholder="Ulangi kata sandi" />
                                <button type="button" 
                                        @click="showConfirmPass = !showConfirmPass" 
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none"
                                        title="Tampilkan / Sembunyikan Konfirmasi Kata Sandi">
                                    <span class="material-icons text-sm" x-text="showConfirmPass ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-2">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                                <span>Daftarkan Akun</span>
                                <span class="material-icons text-base">person_add</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer Link -->
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-400 transition-colors">
                        <span class="material-icons text-xs">arrow_back</span>
                        <span>Kembali ke Halaman Beranda</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>

