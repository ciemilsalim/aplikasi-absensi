<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Panel Kiri (Hero Showcase - Hidden on Mobile) -->
        <div class="relative hidden w-0 flex-1 lg:flex flex-col justify-between bg-slate-900 p-12 overflow-hidden group">
            <!-- Background Image with Gradient Overlay -->
            <div class="absolute inset-0 flex items-center justify-center">
                <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-[7000ms] ease-out group-hover:scale-105 opacity-50" 
                     src="{{ asset('images/forgot-password-illustration.png') }}" 
                     alt="Ilustrasi Reset Password">
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
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Pemulihan Keamanan Akun</span>
                </div>
                <h2 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight tracking-tight drop-shadow-sm">
                    Jangan Khawatir, Kami Siap Membantu.
                </h2>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                    Cukup masukkan alamat email yang terdaftar dan kami akan mengirimkan tautan verifikasi aman untuk mengatur ulang kata sandi Anda.
                </p>
            </div>
        </div>

        <!-- Panel Kanan (Form) -->
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
                        Lupa Kata Sandi?
                    </h2>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                        Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
                    </p>
                </div>

                <div class="mt-8">
                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                        @csrf

                        <!-- Alamat Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Alamat Email Terdaftar
                            </label>
                            <div class="relative">
                                <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">mail_outline</span>
                                <input id="email" type="email" name="email" :value="old('email')" required autofocus
                                       class="w-full text-xs font-semibold pl-10 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15"
                                       placeholder="nama@email.com" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-2">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                                <span>Kirim Link Reset Password</span>
                                <span class="material-icons text-base">send</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer Link -->
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-400 transition-colors">
                        <span class="material-icons text-xs">arrow_back</span>
                        <span>Kembali ke Halaman Login</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>

