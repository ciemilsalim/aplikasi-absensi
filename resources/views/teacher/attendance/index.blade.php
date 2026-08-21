<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')],
                    ['title' => 'Absen Guru', 'url' => route('teacher.attendance.dashboard')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Absensi Mandiri Guru
                </h1>
            </div>
            
            <div class="hidden sm:flex items-center gap-2">
                <a href="{{ route('teacher.attendance.scanner') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/25 transition-all">
                    <span class="material-icons text-base">qr_code_scanner</span>
                    <span>Buka Scanner Wajah</span>
                </a>
            </div>
        </div>
    </x-slot>

    @push('styles')
    <style>
        body > footer, body > .back-to-top-button { display: none !important; }
        footer.mobile-footer { display: block !important; }
    </style>
    @endpush

    <div class="max-w-7xl mx-auto space-y-6 pb-24 sm:pb-8">

        {{-- ========================================================================= --}}
        {{-- 1. TAMPILAN KHUSUS MOBILE (< sm breakpoint)                               --}}
        {{-- Sesuai Standar UX Mobile: Hero Status Hari Ini, Primary CTA, Rekap Bulan  --}}
        {{-- ========================================================================= --}}
        <div class="block sm:hidden space-y-5">
            
            <!-- Header Sapaan Sederhana & Ramah Jempol (64-72px) -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img class="h-12 w-12 rounded-2xl object-cover ring-2 ring-sky-500/20 bg-slate-800 shrink-0 shadow-sm" 
                         src="{{ Auth::user()->profile_photo_url }}" 
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=0284c7&background=e0f2fe';" 
                         alt="{{ Auth::user()->name }}">
                    <div class="min-w-0">
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight truncate">
                            Halo, {{ $teacher->name }} 👋
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                            Presensi Mandiri Guru &bull; SMP Negeri 1 Biau
                        </p>
                    </div>
                </div>

                @if($hasFaceRegistered)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/40 shrink-0">
                        <span class="material-icons text-xs">face</span>
                        <span>Wajah Terdaftar</span>
                    </span>
                @else
                    <a href="{{ route('teacher.attendance.scanner') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-900/40 shrink-0">
                        <span class="material-icons text-xs">face_retouching_natural</span>
                        <span>Daftar Wajah</span>
                    </a>
                @endif
            </div>

            <!-- HERO CARD: STATUS PRESENSI HARI INI (PRIORITAS 1) -->
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <!-- Header Card -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">
                            PRESENSI HARI INI
                        </span>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                            {{ now()->translatedFormat('l, d F Y') }}
                        </p>
                    </div>

                    @if($todayAttendance)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/40">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sudah Presensi</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300/40">
                            ● Belum Absen
                        </span>
                    @endif
                </div>

                @if($todayAttendance)
                    <!-- Detail Presensi Hari Ini -->
                    <div class="bg-emerald-50/50 dark:bg-emerald-950/20 p-4 rounded-2xl border border-emerald-200/60 dark:border-emerald-900/40 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-icons text-emerald-600 dark:text-emerald-400 text-xl">check_circle</span>
                                <div>
                                    <span class="text-xs font-extrabold text-emerald-900 dark:text-emerald-200 block">
                                        Status: {{ ucfirst($todayAttendance->status) }}
                                    </span>
                                    <span class="text-[11px] text-emerald-700 dark:text-emerald-300">
                                        Pukul {{ $todayAttendance->created_at->format('H:i:s') }} WIB
                                    </span>
                                </div>
                            </div>

                            @if($todayAttendance->photo_evidence)
                                <button onclick="showPhoto('{{ asset('storage/'.$todayAttendance->photo_evidence) }}')" 
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                                    Lihat Foto
                                </button>
                            @endif
                        </div>

                        @if($todayAttendance->latitude && $todayAttendance->longitude)
                            <div class="pt-1 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-emerald-200/40 dark:border-emerald-900/30">
                                <span>Titik Koordinat:</span>
                                <a href="https://www.google.com/maps?q={{ $todayAttendance->latitude }},{{ $todayAttendance->longitude }}" target="_blank" class="text-sky-600 dark:text-sky-400 font-bold hover:underline flex items-center gap-0.5">
                                    <span>Buka di Peta</span>
                                    <span class="material-icons text-xs">open_in_new</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Tombol Scanner Ulang -->
                    <div class="pt-1">
                        <a href="{{ route('teacher.attendance.scanner') }}" 
                           class="w-full min-h-[44px] py-2.5 flex items-center justify-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 font-bold text-xs transition-colors">
                            <span class="material-icons text-base text-sky-500">replay</span>
                            <span>Buka Scanner Lagi</span>
                        </a>
                    </div>
                @else
                    <!-- Belum Presensi: Primary Action CTA -->
                    <div class="bg-sky-50/60 dark:bg-sky-950/20 p-4 rounded-2xl border border-sky-200/60 dark:border-sky-900/40 space-y-1.5">
                        <div class="flex items-center gap-2 text-sky-900 dark:text-sky-200 font-bold text-xs">
                            <span class="material-icons text-sky-500 text-lg">location_on</span>
                            <span>Area Presensi: SMP Negeri 1 Biau</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Pastikan Anda berada dalam radius sekolah ({{ $settings['attendance_radius'] ?? 100 }}m) dan izinkan akses kamera & GPS.
                        </p>
                    </div>

                    <div class="pt-1">
                        <a href="{{ route('teacher.attendance.scanner') }}" 
                           class="w-full min-h-[48px] py-3 flex items-center justify-center gap-2 rounded-2xl bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white font-bold text-sm shadow-md shadow-sky-600/25 active:scale-95 transition-all">
                            <span class="material-icons text-lg">qr_code_scanner</span>
                            <span>REKAM KEHADIRAN SEKARANG</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- REKAP BULAN INI (4 STATS CARDS) -->
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-sky-500 text-base">assessment</span>
                        <span>Rekap Bulan {{ now()->translatedFormat('F Y') }}</span>
                    </h3>
                    <span class="text-xs font-extrabold text-sky-600 dark:text-sky-400">
                        {{ $monthlyPercentage }}% Kehadiran
                    </span>
                </div>

                <div class="grid grid-cols-4 gap-2 text-center">
                    <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-800">
                        <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block">Hadir</span>
                        <span class="text-base font-black text-emerald-700 dark:text-emerald-300">{{ $monthlyStats['hadir'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200/60 dark:border-sky-800">
                        <span class="text-[10px] uppercase font-bold text-sky-600 dark:text-sky-400 block">Izin</span>
                        <span class="text-base font-black text-sky-700 dark:text-sky-300">{{ $monthlyStats['izin'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-800">
                        <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400 block">Sakit</span>
                        <span class="text-base font-black text-amber-700 dark:text-amber-300">{{ $monthlyStats['sakit'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-800">
                        <span class="text-[10px] uppercase font-bold text-rose-600 dark:text-rose-400 block">Alpa</span>
                        <span class="text-base font-black text-rose-700 dark:text-rose-300">{{ $monthlyStats['alpa'] }}</span>
                    </div>
                </div>
            </div>

            <!-- RIWAYAT PRESENSI SAYA (MOBILE LIST 64-72px) -->
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-2">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-sky-500 text-base">history</span>
                        <span>Riwayat Presensi Mandiri</span>
                    </h3>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($attendances as $attendance)
                        <div class="py-3 flex items-center justify-between gap-3 min-h-[64px]">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 
                                    @if($attendance->status == 'hadir') bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400
                                    @elseif($attendance->status == 'izin') bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400
                                    @elseif($attendance->status == 'sakit') bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400
                                    @else bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 @endif">
                                    <span class="material-icons text-lg">
                                        @if($attendance->status == 'hadir') task_alt
                                        @elseif($attendance->status == 'izin') mail
                                        @elseif($attendance->status == 'sakit') medical_services
                                        @else cancel @endif
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white">
                                        {{ $attendance->created_at->translatedFormat('d M Y') }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        Pukul {{ $attendance->created_at->format('H:i:s') }} WIB
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold 
                                    @if($attendance->status == 'hadir') bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60
                                    @elseif($attendance->status == 'izin') bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200/60
                                    @elseif($attendance->status == 'sakit') bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60
                                    @else bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/60 @endif">
                                    {{ ucfirst($attendance->status) }}
                                </span>

                                @if($attendance->photo_evidence)
                                    <button onclick="showPhoto('{{ asset('storage/'.$attendance->photo_evidence) }}')" 
                                            class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-sky-600 flex items-center justify-center" title="Foto">
                                        <span class="material-icons text-base">photo_camera</span>
                                    </button>
                                @endif

                                @if($attendance->latitude && $attendance->longitude)
                                    <a href="https://www.google.com/maps?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank"
                                       class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-sky-600 flex items-center justify-center" title="Peta">
                                        <span class="material-icons text-base">map</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-400">
                            Belum ada riwayat absensi mandiri.
                        </div>
                    @endforelse
                </div>

                @if($attendances->hasPages())
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        {{ $attendances->links() }}
                    </div>
                @endif
            </div>

        </div>

        {{-- ========================================================================= --}}
        {{-- 2. TAMPILAN DESKTOP (≥ sm breakpoint)                                      --}}
        {{-- Tata Letak Modern: 2-Kolom, Hero Banner, Stats, Tabel Lengkap              --}}
        {{-- ========================================================================= --}}
        <div class="hidden sm:block space-y-6">

            <!-- Hero Banner Desktop -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 text-white p-7 shadow-lg border border-sky-900/50">
                <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute top-0 right-1/4 w-32 h-32 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
                
                <div class="relative z-10 flex items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-sky-500/20 bg-slate-800 shrink-0" 
                             src="{{ Auth::user()->profile_photo_url }}" 
                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=0284c7&background=e0f2fe';" 
                             alt="{{ Auth::user()->name }}">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-500/20 text-[10px] font-bold text-sky-300 border border-sky-400/20 mb-1">
                                <span class="material-icons text-xs">badge</span>
                                <span>Presensi Mandiri Guru</span>
                            </div>
                            <h2 class="text-xl font-extrabold text-white tracking-tight leading-snug">
                                Selamat Bertugas, {{ $teacher->name }}!
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Catat kehadiran harian Anda menggunakan teknologi pengenalan wajah biometrik dan verifikasi radius geolocation.
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 flex items-center gap-3">
                        <a href="{{ route('teacher.attendance.scanner') }}" 
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-sky-600 hover:bg-sky-500 active:scale-95 text-white text-xs font-extrabold shadow-lg shadow-sky-600/30 transition-all">
                            <span class="material-icons text-lg">qr_code_scanner</span>
                            <span>{{ $todayAttendance ? 'Buka Scanner Lagi' : 'Buka Scanner Wajah' }}</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Grid 4 Stats Desktop -->
            <div class="grid grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <span class="material-icons text-2xl">check_circle</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Hadir</span>
                        <h4 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $monthlyStats['hadir'] }} <span class="text-xs font-normal text-slate-400">Hari</span></h4>
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                        <span class="material-icons text-2xl">mail</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Izin</span>
                        <h4 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $monthlyStats['izin'] }} <span class="text-xs font-normal text-slate-400">Hari</span></h4>
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <span class="material-icons text-2xl">medical_services</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Sakit</span>
                        <h4 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $monthlyStats['sakit'] }} <span class="text-xs font-normal text-slate-400">Hari</span></h4>
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <span class="material-icons text-2xl">insights</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Persentase</span>
                        <h4 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $monthlyPercentage }}%</h4>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Absensi Desktop -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-sky-500 text-lg">table_chart</span>
                            Riwayat Absensi Mandiri Saya
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar rekaman presensi kehadiran harian Anda di SMP Negeri 1 Biau</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                        <thead class="text-[11px] text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-850 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 font-bold">Tanggal</th>
                                <th scope="col" class="px-6 py-3.5 font-bold">Waktu Masuk</th>
                                <th scope="col" class="px-6 py-3.5 font-bold">Status</th>
                                <th scope="col" class="px-6 py-3.5 font-bold">Lokasi Geolocation</th>
                                <th scope="col" class="px-6 py-3.5 font-bold text-center">Foto Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($attendances as $attendance)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                        {{ $attendance->created_at->translatedFormat('l, d F Y') }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $attendance->created_at->format('H:i:s') }} WIB
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 font-bold text-[11px] rounded-full inline-flex items-center gap-1
                                            @if($attendance->status == 'hadir') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200/60
                                            @elseif($attendance->status == 'izin') bg-sky-100 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300 border border-sky-200/60
                                            @elseif($attendance->status == 'sakit') bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200/60
                                            @else bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200/60 @endif">
                                            <span>{{ ucfirst($attendance->status) }}</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($attendance->latitude && $attendance->longitude)
                                            <a href="https://www.google.com/maps?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank" 
                                               class="inline-flex items-center gap-1 text-sky-600 dark:text-sky-400 font-bold hover:underline">
                                                <span class="material-icons text-sm">location_on</span>
                                                <span>{{ number_format($attendance->latitude, 5) }}, {{ number_format($attendance->longitude, 5) }}</span>
                                            </a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($attendance->photo_evidence)
                                            <button onclick="showPhoto('{{ asset('storage/'.$attendance->photo_evidence) }}')" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-sky-600 text-xs font-bold transition-colors">
                                                <span class="material-icons text-sm">visibility</span>
                                                <span>Lihat Foto</span>
                                            </button>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada riwayat data absensi mandiri.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($attendances->hasPages())
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800">
                        {{ $attendances->links() }}
                    </div>
                @endif
            </div>

        </div>

    </div>

    <!-- Modal Foto Bukti -->
    <div id="photo-modal" class="fixed inset-0 bg-black/80 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4" onclick="closePhoto()">
        <div class="relative max-w-md w-full bg-slate-900 rounded-3xl overflow-hidden shadow-2xl p-2 border border-slate-700" onclick="event.stopPropagation()">
            <img id="modal-image" src="" alt="Bukti Absensi" class="w-full h-auto rounded-2xl object-cover max-h-[75vh]">
            <button onclick="closePhoto()" class="absolute top-4 right-4 bg-black/60 hover:bg-black/80 text-white rounded-full p-2 backdrop-blur-xs transition-colors">
                <span class="material-icons text-base">close</span>
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        function showPhoto(url) {
            document.getElementById('modal-image').src = url;
            document.getElementById('photo-modal').classList.remove('hidden');
        }
        
        function closePhoto() {
            document.getElementById('photo-modal').classList.add('hidden');
        }
    </script>
    @endpush
</x-app-layout>
