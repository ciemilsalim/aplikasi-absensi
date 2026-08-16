<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'pembina_ekskul'])],
                    ['title' => 'Presensi Ekstrakurikuler', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Presensi & Kegiatan Ekstrakurikuler
                    </h1>
                    @if($activeYear && $activeSemester)
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>TA {{ $activeYear->name }} • Semester {{ ucfirst($activeSemester->type ?? $activeSemester->name) }}</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">dashboard</span>
                    <span>Dasbor Pembina</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs font-bold flex items-center gap-3 shadow-xs">
                <span class="material-icons text-lg text-emerald-600">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs font-bold flex items-center gap-3 shadow-xs">
                <span class="material-icons text-lg text-rose-600">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Hero Banner (Amber / Orange Theme) -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-amber-950/80 to-orange-950 text-white p-6 sm:p-7 shadow-lg border border-amber-900/40">
            <div class="absolute -right-10 -bottom-10 w-72 h-72 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute top-0 right-1/4 w-36 h-36 rounded-full bg-orange-500/10 blur-2xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-amber-500/30 bg-slate-800 shrink-0 shadow-md" 
                         src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=d97706&background=fef3c7' }}" 
                         alt="{{ Auth::user()->name }}">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-[10px] font-bold text-amber-300 border border-amber-400/20 mb-1">
                            <span class="material-icons text-xs">military_tech</span>
                            <span>Manajemen Presensi Ekstrakurikuler</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-snug">
                            Kegiatan Binaan: {{ $teacher->name }}
                        </h2>
                        <p class="text-xs text-amber-100/80 mt-0.5 max-w-xl">
                            Kelola rekaman presensi sesi latihan anggota secara fleksibel menggunakan Scanner Kamera (QR/Face AI) atau formulir manual cepat.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <div class="px-4 py-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-left">
                        <span class="text-[10px] uppercase font-bold text-amber-200/70 block">Tanggal Hari Ini</span>
                        <span class="text-xs font-black text-white flex items-center gap-1.5 mt-0.5">
                            <span class="material-icons text-amber-400 text-xs">event</span>
                            {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Key KPI Metrics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- KPI 1: Kegiatan Dibina -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ekskul Dibina</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-lg">sports_soccer</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $extracurriculars->count() }} <span class="text-xs font-semibold text-slate-500">Kegiatan</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-0.5">Penugasan aktif pembina</p>
                </div>
            </div>

            <!-- KPI 2: Total Anggota -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Anggota</span>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-lg">groups</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalMembers }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-0.5">Seluruh anggota terdaftar</p>
                </div>
            </div>

            <!-- KPI 3: Sesi Terlaksana -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sesi Latihan</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-lg">fact_check</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalSessions }} <span class="text-xs font-semibold text-slate-500">Pertemuan</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-0.5">Tercatat pada semester ini</p>
                </div>
            </div>

            <!-- KPI 4: Rata-rata Kehadiran -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Hadir</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-icons text-lg">pie_chart</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $avgAttendanceRate }}%
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full" style="width: {{ min(100, max(0, $avgAttendanceRate)) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Kartu Ekstrakurikuler (Grid Card Layout) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-amber-500 text-lg">workspace_premium</span>
                        Pilih Ekstrakurikuler untuk Presensi
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pilih mode presensi yang diinginkan untuk memulai sesi latihan anggota
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @forelse ($extracurriculars as $ekskul)
                    @php
                        $stats = $todayStats[$ekskul->id] ?? null;
                        $isRecorded = ($stats && $stats['total'] > 0);
                    @endphp
                    <div class="relative bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border transition-all duration-300 hover:shadow-md group overflow-hidden flex flex-col justify-between {{ $isRecorded ? 'border-amber-200 dark:border-amber-900/60' : 'border-slate-200/80 dark:border-slate-800' }}">
                        
                        <!-- Background Decorative Watermark -->
                        <span class="material-icons absolute -right-4 -bottom-4 text-8xl opacity-[0.03] dark:opacity-[0.04] group-hover:scale-110 transition-transform duration-500 pointer-events-none">
                            military_tech
                        </span>

                        <div class="relative z-10 space-y-4">
                            <!-- Card Header: Icon, Name & Status Badge -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $isRecorded ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800' }}">
                                        <span class="material-icons text-2xl">sports_soccer</span>
                                    </div>
                                    <div>
                                        <h4 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">
                                            {{ $ekskul->name }}
                                        </h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            {{ $ekskul->students_count }} Anggota Terdaftar
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Badge Hari Ini -->
                                @if($isRecorded)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-2xs shrink-0">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Sudah Absen Hari Ini</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs shrink-0">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span>Belum Ada Presensi</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Deskripsi & Info -->
                            @if(!empty($ekskul->description))
                                <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed">
                                    {{ $ekskul->description }}
                                </p>
                            @endif

                            <!-- Mini Summary Box Hari Ini -->
                            @if($isRecorded)
                                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800 grid grid-cols-4 gap-2 text-center">
                                    <div>
                                        <span class="text-[10px] font-bold text-emerald-600 uppercase block">Hadir</span>
                                        <span class="text-xs font-black text-slate-800 dark:text-white">{{ $stats['hadir'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-amber-600 uppercase block">Sakit</span>
                                        <span class="text-xs font-black text-slate-800 dark:text-white">{{ $stats['sakit'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-purple-600 uppercase block">Izin</span>
                                        <span class="text-xs font-black text-slate-800 dark:text-white">{{ $stats['izin'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-rose-600 uppercase block">Alpa</span>
                                        <span class="text-xs font-black text-slate-800 dark:text-white">{{ $stats['alpa'] }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Card Actions: 3 Tombol Lengkap & Mudah Digunakan -->
                        <div class="relative z-10 pt-5 mt-4 border-t border-slate-100 dark:border-slate-800/80 grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <!-- 1. Buka Scanner Kamera Live -->
                            <a href="{{ route('teacher.extracurricular-attendance.scanner', $ekskul) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs shadow-md shadow-amber-500/25 transition-all text-center">
                                <span class="material-icons text-sm">qr_code_scanner</span>
                                <span>Scanner Live</span>
                            </a>

                            <!-- 2. Form Input Manual Checklist -->
                            <a href="{{ route('teacher.extracurricular-attendance.create', $ekskul) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200/70 dark:border-slate-700 transition-colors text-center">
                                <span class="material-icons text-sm text-slate-500">checklist</span>
                                <span>Input Manual</span>
                            </a>

                            <!-- 3. Rekap & Cetak Laporan -->
                            <a href="{{ route('teacher.extracurricular-attendance.report', $ekskul) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200/70 dark:border-slate-700 transition-colors text-center">
                                <span class="material-icons text-sm text-slate-500">summarize</span>
                                <span>Rekap Laporan</span>
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full py-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-8 shadow-xs">
                        <div class="w-16 h-16 rounded-3xl bg-amber-50 dark:bg-amber-950/50 text-amber-500 mx-auto flex items-center justify-center mb-3 shadow-2xs">
                            <span class="material-icons text-3xl">sports_soccer</span>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-800 dark:text-white">Anda Belum Ditugaskan Sebagai Pembina</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                            Anda belum ditugaskan membina kegiatan ekstrakurikuler manapun. Silakan koordinasikan dengan bagian Kesiswaan atau Admin Sekolah.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Riwayat Sesi Presensi Terbaru -->
        @if(isset($recentSessions) && $recentSessions->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">history</span>
                            Riwayat Sesi Latihan Terkini
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar sesi pertemuan presensi yang baru-baru ini dilaksanakan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    @foreach($recentSessions as $session)
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800 flex flex-col justify-between gap-3">
                            <div>
                                <div class="flex items-center justify-between">
                                    <h4 class="font-extrabold text-xs text-slate-900 dark:text-white truncate max-w-[160px]">
                                        {{ $session->extracurricular?->name ?? 'Ekstrakurikuler' }}
                                    </h4>
                                    <span class="text-[10px] font-bold text-slate-400">
                                        {{ \Carbon\Carbon::parse($session->attendance_date)->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                    {{ $session->total_students }} Siswa Tercatat
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-[10px] font-bold">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 dark:text-emerald-400">H: {{ $session->hadir_count }}</span>
                                    <span class="text-amber-600 dark:text-amber-400">S: {{ $session->sakit_count }}</span>
                                    <span class="text-purple-600 dark:text-purple-400">I: {{ $session->izin_count }}</span>
                                    <span class="text-rose-600 dark:text-rose-400">A: {{ $session->alpa_count }}</span>
                                </div>

                                <a href="{{ route('teacher.extracurricular-attendance.create', array_merge(['extracurricular' => $session->extracurricular_id], ['date' => $session->attendance_date])) }}" 
                                   class="text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-0.5">
                                    <span>Tinjau</span>
                                    <span class="material-icons text-xs">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Panduan Ringkas Alur Presensi -->
        <div class="bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-amber-500/5 dark:from-amber-950/30 dark:to-orange-950/20 rounded-3xl border border-amber-200/80 dark:border-amber-900/50 p-6">
            <h4 class="text-xs font-black uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-2 mb-3">
                <span class="material-icons text-base">help_outline</span>
                Panduan Praktis Pembina Ekstrakurikuler
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3.5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-amber-200/50 dark:border-amber-900/40">
                    <span class="font-black text-amber-600 dark:text-amber-400 block mb-1">1. Buka Scanner / Manual</span>
                    <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                        Saat sesi latihan dimulai, buka Scanner Live untuk scan kartu QR/wajah anggota, atau gunakan form checklist manual.
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-amber-200/50 dark:border-amber-900/40">
                    <span class="font-black text-amber-600 dark:text-amber-400 block mb-1">2. Catat Keterangan Khusus</span>
                    <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                        Tandai siswa yang izin atau sakit dan beri catatan dispensasi (misal: mengikuti turnamen / latihan gabungan).
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-amber-200/50 dark:border-amber-900/40">
                    <span class="font-black text-amber-600 dark:text-amber-400 block mb-1">3. Unduh & Cetak Laporan</span>
                    <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                        Di akhir periode atau akhir semester, buka menu Rekap Laporan untuk mencetak dokumen presensi resmi bertanda tangan.
                    </p>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
