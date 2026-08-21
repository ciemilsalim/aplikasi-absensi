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
                        Presensi Ekstrakurikuler
                    </h1>
                    @if($activeYear && $activeSemester)
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>TA {{ $activeYear->name }} &bull; Semester {{ ucfirst($activeSemester->type ?? $activeSemester->name) }}</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors shadow-2xs">
                    <span class="material-icons text-sm text-slate-500">dashboard</span>
                    <span>Dasbor Pembina</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 pb-32 sm:pb-12" x-data="{ showGuide: false }">

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

        <!-- ================= 1. PUSAT AKSI OPERASIONAL (KEGIATAN BINAAN & ACTION CARDS DI ATAS) ================= -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-amber-500 text-lg">sports_soccer</span>
                        Kegiatan Binaan & Presensi Sesi Latihan
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pilih kegiatan binaan untuk langsung memulai scan presensi atau mengisi checklist manual
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
                @forelse ($extracurriculars as $ekskul)
                    @php
                        $stats = $todayStats[$ekskul->id] ?? null;
                        $isRecorded = ($stats && $stats['total'] > 0);
                        $memberCount = $ekskul->students_count;
                        $recordedCount = $stats['total'] ?? 0;
                    @endphp
                    <div class="relative bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 shadow-xs border transition-all duration-300 hover:shadow-md group overflow-hidden flex flex-col justify-between {{ $isRecorded ? 'border-amber-300/80 dark:border-amber-800/80' : 'border-slate-200/80 dark:border-slate-800' }}">
                        
                        <!-- Background Decorative Subtle Icon -->
                        <span class="material-icons absolute -right-3 -bottom-3 text-7xl opacity-[0.03] dark:opacity-[0.04] group-hover:scale-110 transition-transform duration-500 pointer-events-none">
                            workspace_premium
                        </span>

                        <div class="relative z-10 space-y-4">
                            <!-- Card Header: Nama Kegiatan (Primary Identity) & Status Sesi Hari Ini -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $isRecorded ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-md shadow-amber-500/25' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800' }}">
                                        <span class="material-icons text-2xl">sports_soccer</span>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight truncate">
                                            {{ $ekskul->name }}
                                        </h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $memberCount }} anggota aktif</span>
                                            <span>&bull;</span>
                                            <span class="truncate">Pembina: {{ $teacher->name }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Sesi Hari Ini Badge -->
                                @if($isRecorded)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-2xs shrink-0">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Sudah Dipresensi</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs shrink-0">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span>Belum Dimulai</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Detail Tanggal & Tim Pembina Tambahan -->
                            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-1">
                                <span class="flex items-center gap-1 font-medium">
                                    <span class="material-icons text-xs text-amber-500">event</span>
                                    <span>{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
                                </span>
                                
                                @if($ekskul->teachers && $ekskul->teachers->count() > 1)
                                    <span class="text-[11px] text-slate-400 font-medium">
                                        Tim: {{ $ekskul->teachers->count() }} Pembina
                                    </span>
                                @endif
                            </div>

                            <!-- Mini Summary Box Hari Ini (Jika Sudah Absen) -->
                            @if($isRecorded)
                                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850/70 border border-slate-100 dark:border-slate-800 grid grid-cols-4 gap-2 text-center">
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

                        <!-- Card Action Buttons: Clear Hierarchy (Primary -> Secondary -> Tertiary) -->
                        <div class="relative z-10 pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <!-- 1. PRIMARY ACTION: Mulai Scan QR -->
                            <a href="{{ route('teacher.extracurricular-attendance.scanner', $ekskul) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs shadow-md shadow-amber-500/25 transition-all text-center">
                                <span class="material-icons text-sm">qr_code_scanner</span>
                                <span>Mulai Scan QR</span>
                            </a>

                            <!-- 2. SECONDARY ACTION: Input Manual -->
                            <a href="{{ route('teacher.extracurricular-attendance.create', $ekskul) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors text-center shadow-2xs">
                                <span class="material-icons text-sm text-slate-500">checklist</span>
                                <span>Input Manual</span>
                            </a>

                            <!-- 3. TERTIARY ACTION: Lihat Rekap -->
                            <a href="{{ route('teacher.extracurricular-attendance.report', $ekskul) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-slate-50 hover:bg-slate-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors text-center">
                                <span class="material-icons text-sm text-slate-400">summarize</span>
                                <span>Lihat Rekap</span>
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-8 shadow-xs">
                        <div class="w-14 h-14 rounded-3xl bg-amber-50 dark:bg-amber-950/50 text-amber-500 mx-auto flex items-center justify-center mb-3 shadow-2xs">
                            <span class="material-icons text-2xl">sports_soccer</span>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-800 dark:text-white">Anda Belum Ditugaskan Sebagai Pembina</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                            Anda belum memiliki penugasan ekstrakurikuler aktif. Silakan koordinasikan dengan bagian Kesiswaan atau Admin Sekolah.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ================= 2. KPI STATISTIK SEMESTER (INFORMATIF DENGAN TARGET) ================= -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- KPI 1: Kegiatan Dibina -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kegiatan Dibina</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-base">sports_soccer</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $extracurriculars->count() }} <span class="text-xs font-bold text-slate-500">Kegiatan</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Penugasan aktif pembina
                    </p>
                </div>
            </div>

            <!-- KPI 2: Anggota Aktif -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Anggota Aktif</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-base">groups</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalMembers }} <span class="text-xs font-bold text-slate-500">Siswa</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Total siswa terdaftar
                    </p>
                </div>
            </div>

            <!-- KPI 3: Sesi Terlaksana -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sesi Terlaksana</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-base">fact_check</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalSessions }} <span class="text-xs font-bold text-slate-500">Sesi</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Sudah direkap semester ini
                    </p>
                </div>
            </div>

            <!-- KPI 4: Rata-rata Kehadiran (Focal Point) -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Hadir</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-icons text-base">pie_chart</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $avgAttendanceRate }}%</span>
                        @if($avgAttendanceRate >= 80)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                +{{ round($avgAttendanceRate - 80, 1) }}% target
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                -{{ round(80 - $avgAttendanceRate, 1) }}% target
                            </span>
                        @endif
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $avgAttendanceRate)) }}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium">Target kehadiran &ge; 80%</p>
                </div>
            </div>
        </div>

        <!-- ================= 3. RIWAYAT SESI LATIHAN TERKINI (ACTIONABLE) ================= -->
        @if(isset($recentSessions) && $recentSessions->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-base">history</span>
                            Riwayat Sesi Latihan Terkini
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar sesi pertemuan presensi yang baru-baru ini dilaksanakan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    @foreach($recentSessions as $session)
                        @php
                            $totalClassMembers = $session->extracurricular?->students_count ?? $session->total_students;
                            $isComplete = ($session->total_students >= $totalClassMembers && $totalClassMembers > 0);
                        @endphp
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
                                
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ $session->total_students }} / {{ $totalClassMembers }} anggota
                                    </span>
                                    
                                    @if($isComplete)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-200/60 dark:border-emerald-800">
                                            <span>Selesai</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-md border border-amber-200/60 dark:border-amber-800">
                                            <span>Belum Lengkap</span>
                                        </span>
                                    @endif
                                </div>
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

        <!-- ================= 4. PANDUAN PRAKTIS (COLLAPSIBLE / ON-DEMAND) ================= -->
        <div class="bg-amber-50/50 dark:bg-amber-950/20 rounded-3xl border border-amber-200/60 dark:border-amber-900/40 p-4 sm:p-5">
            <button type="button" @click="showGuide = !showGuide" class="w-full flex items-center justify-between gap-2 text-left">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-amber-600 dark:text-amber-400 text-lg">info</span>
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-amber-900 dark:text-amber-200">
                            Panduan Praktis Presensi Pembina
                        </h4>
                        <p class="text-[11px] text-amber-700/80 dark:text-amber-300/70">
                            3 langkah cepat: Mulai Scan QR &rarr; Catat Keterangan &rarr; Lihat Rekap
                        </p>
                    </div>
                </div>
                <span class="material-icons text-amber-600 dark:text-amber-400 text-lg transition-transform duration-200" :class="showGuide ? 'rotate-180' : ''">
                    expand_more
                </span>
            </button>

            <!-- Expanded 3-Step Guide -->
            <div x-show="showGuide" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4 pt-4 border-t border-amber-200/50 dark:border-amber-900/30 text-xs" x-transition>
                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200/50 dark:border-amber-900/40 shadow-2xs">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block mb-1">1. Buka Scanner / Manual</span>
                    <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                        Saat sesi latihan dimulai, klik <strong>Mulai Scan QR</strong> untuk memindai kartu anggota, atau gunakan <strong>Input Manual</strong>.
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200/50 dark:border-amber-900/40 shadow-2xs">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block mb-1">2. Catat Keterangan Dispensasi</span>
                    <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                        Tandai siswa yang izin atau sakit dan berikan catatan bila mengikuti lomba/latihan gabungan.
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200/50 dark:border-amber-900/40 shadow-2xs">
                    <span class="font-bold text-amber-600 dark:text-amber-400 block mb-1">3. Unduh & Cetak Rekap</span>
                    <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                        Buka menu <strong>Lihat Rekap</strong> untuk melihat matriks kehadiran semester dan mengekspor dokumen cetak resmi.
                    </p>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
