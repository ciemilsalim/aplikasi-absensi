<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Eksekutif', 'url' => route('principal.dashboard')],
                    ['title' => 'Overview Kepala Sekolah', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2.5 mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Dasbor Eksekutif Kepala Sekolah
                    </h1>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        Executive Mode
                    </span>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <a href="{{ route('admin.teaching_journals.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition-all active:scale-95">
                    <span class="material-icons text-sm">verified_user</span>
                    <span>Supervisi Jurnal Guru</span>
                    @if($pendingJournals > 0)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950">
                            {{ $pendingJournals }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.reports.create') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">picture_as_pdf</span>
                    <span>Rekap Laporan</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        
        <!-- Welcome Hero Executive Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 text-white p-6 sm:p-7 shadow-xl border border-indigo-900/40">
            <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute top-0 right-1/4 w-40 h-40 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="flex items-center gap-4">
                    <img class="h-16 w-16 sm:h-18 sm:w-18 rounded-2xl object-cover ring-4 ring-indigo-500/30 bg-slate-800 shrink-0 shadow-lg" 
                         src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=818cf8&background=1e1b4b' }}" 
                         alt="{{ Auth::user()->name }}">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-[10px] font-extrabold text-indigo-300 border border-indigo-400/20 mb-1">
                            <span class="material-icons text-xs">account_balance</span>
                            <span>Kepala Sekolah</span>
                        </div>
                        <h2 class="text-lg sm:text-2xl font-extrabold text-white tracking-tight leading-snug">
                            Selamat Datang, {{ Auth::user()->name }}
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 mt-0.5 max-w-2xl">
                            Pantau tingkat kedisiplinan harian, ketercapaian sesi pembelajaran mapel & kokurikuler, serta lakukan validasi supervisi jurnal mengajar guru.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white/5 backdrop-blur-md p-3.5 rounded-2xl border border-white/10 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 flex items-center justify-center font-black">
                        <span class="material-icons text-xl">event</span>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-extrabold text-indigo-200">Hari Ini</p>
                        <p class="text-xs font-bold text-white">{{ Carbon\Carbon::today()->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Key Performance Metrics Ribbons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            <!-- 1. Tingkat Kehadiran Harian Sekolah -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Kehadiran Harian</span>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shadow-2xs">
                        <span class="material-icons text-xl">how_to_reg</span>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ $dailyPresencePercentage }}%
                    </span>
                    <span class="text-xs text-slate-400 font-semibold">
                        ({{ $totalHadir }} / {{ $totalStudents }} Siswa)
                    </span>
                </div>
                <div class="mt-3 w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $dailyPresencePercentage) }}%"></div>
                </div>
            </div>

            <!-- 2. Pembelajaran & Mapel Hari Ini -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Sesi Mapel Hari Ini</span>
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold shadow-2xs">
                        <span class="material-icons text-xl">menu_book</span>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ $activeMapelSessionsToday }}
                    </span>
                    <span class="text-xs text-slate-400 font-semibold">
                        / {{ $totalMapelSessionsToday }} Jadwal Terdaftar
                    </span>
                </div>
                <div class="mt-3 flex items-center gap-2 text-[11px]">
                    <span class="font-bold text-sky-600 dark:text-sky-400">{{ $mapelHadirCount }} Hadir</span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400">{{ $mapelBolosCount }} Bolos</span>
                </div>
            </div>

            <!-- 3. Kokurikuler & Ekstrakurikuler -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Kokurikuler & Ekskul</span>
                    <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold shadow-2xs">
                        <span class="material-icons text-xl">psychology</span>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ $cocurricularProjectsCount }}
                    </span>
                    <span class="text-xs text-slate-400 font-semibold">
                        Proyek P5 / {{ $extracurricularsCount }} Ekskul
                    </span>
                </div>
                <div class="mt-3 flex items-center gap-2 text-[11px]">
                    <span class="font-bold text-purple-600 dark:text-purple-400">{{ $cocurricularSchedulesToday }} Sesi Hari Ini</span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="font-semibold text-slate-500">{{ $extraAttendancesThisWeek }} Kehadiran Ekskul</span>
                </div>
            </div>

            <!-- 4. Supervisi Jurnal Guru -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Supervisi Jurnal Guru</span>
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold shadow-2xs">
                        <span class="material-icons text-xl">verified</span>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ $verifiedJournals }}
                    </span>
                    <span class="text-xs text-slate-400 font-semibold">
                        / {{ $totalJournals }} Jurnal Tervalidasi
                    </span>
                </div>
                <div class="mt-3 flex items-center justify-between text-[11px]">
                    <span class="font-extrabold {{ $pendingJournals > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600' }}">
                        {{ $pendingJournals }} Menunggu Validasi
                    </span>
                    <span class="text-slate-400">{{ $activeTeachersWithJournal }} / {{ $totalTeachers }} Guru Aktif</span>
                </div>
            </div>
        </div>

        <!-- Main Executive Workspace (12 Cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Workspace: Grafik & Tabel Supervisi (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Grafik Tren Kehadiran Siswa -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-indigo-500 text-lg">insights</span>
                                Tren Persentase Kehadiran Siswa
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pantau stabilitas kehadiran harian 14 hari sekolah terakhir</p>
                        </div>
                        <span class="self-start sm:self-auto text-[10px] font-bold px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                            Rata-rata: {{ $dailyPresencePercentage }}%
                        </span>
                    </div>

                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="principalAttendanceChart"></canvas>
                    </div>
                </div>

                <!-- Jurnal Mengajar yang Menunggu Supervisi Kepala Sekolah -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-amber-500 text-lg">pending_actions</span>
                                Jurnal Guru Menunggu Supervisi
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar entri jurnal materi & refleksi guru yang siap diverifikasi</p>
                        </div>
                        <a href="{{ route('admin.teaching_journals.index', ['status' => 'unverified']) }}" 
                           class="text-xs font-bold text-indigo-600 hover:text-indigo-500 flex items-center gap-1">
                            <span>Lihat Semua ({{ $pendingJournals }})</span>
                            <span class="material-icons text-sm">arrow_forward</span>
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($recentPendingJournals as $journal)
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 hover:bg-slate-50/80 dark:hover:bg-slate-850/50 transition-colors">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold shrink-0">
                                        <span class="material-icons text-xl">auto_stories</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                                                {{ $journal->teacher?->name ?? '-' }}
                                            </span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold">
                                                {{ $journal->subject?->name ?? 'Mapel' }} • Kelas {{ $journal->schoolClass?->name ?? '-' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 line-clamp-1">
                                            <strong>Materi:</strong> {{ $journal->topic_material ?? $journal->learning_goal ?? '-' }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            Tanggal: {{ Carbon\Carbon::parse($journal->date)->translatedFormat('d F Y') }} • {{ $journal->jp ?? 2 }} JP
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                    <a href="{{ route('admin.teaching_journals.show', $journal->teacher_id) }}" 
                                       class="px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition-all shadow-2xs flex items-center gap-1">
                                        <span class="material-icons text-sm">visibility</span>
                                        <span>Periksa</span>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-slate-400 italic">
                                <span class="material-icons text-3xl text-emerald-500 block mb-1">check_circle</span>
                                Semua jurnal mengajar telah tervalidasi dan disupervisi.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Performa Kehadiran per Kelas Hari Ini -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                        <span class="material-icons text-sky-500 text-lg">grid_view</span>
                        Matriks Kehadiran Siswa per Rombel Kelas (Hari Ini)
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Gambaran sebaran kehadiran peserta didik pada masing-masing kelas</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto no-scrollbar pr-1">
                        @foreach($classAttendanceBreakdown as $cls)
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100">Kelas {{ $cls['name'] }}</h4>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        Hadir: <strong class="text-emerald-600">{{ $cls['hadir'] }}</strong> / {{ $cls['total'] }} Siswa
                                        @if($cls['alpha'] > 0)
                                            • <span class="text-rose-500 font-bold">Alpa: {{ $cls['alpha'] }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-black {{ $cls['percentage'] >= 90 ? 'text-emerald-600' : ($cls['percentage'] >= 75 ? 'text-sky-600' : 'text-rose-600') }}">
                                        {{ $cls['percentage'] }}%
                                    </span>
                                    <div class="w-16 bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full mt-1 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $cls['percentage'] >= 90 ? 'bg-emerald-500' : ($cls['percentage'] >= 75 ? 'bg-sky-500' : 'bg-rose-500') }}" style="width: {{ $cls['percentage'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Right Workspace: Breakdown & Fast Executive Actions (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Rincian Status Kehadiran Hari Ini -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                        <span class="material-icons text-indigo-500 text-lg">pie_chart</span>
                        Rincian Presensi Hari Ini
                    </h3>

                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50">
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Hadir Tepat Waktu
                            </span>
                            <span class="font-black text-xs text-emerald-700 dark:text-emerald-300">{{ $presentOnTimeCount }}</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/50">
                            <span class="text-xs font-bold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Hadir Terlambat
                            </span>
                            <span class="font-black text-xs text-amber-700 dark:text-amber-300">{{ $presentLateCount }}</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-purple-50/70 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900/50">
                            <span class="text-xs font-bold text-purple-800 dark:text-purple-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span> Sakit & Izin
                            </span>
                            <span class="font-black text-xs text-purple-700 dark:text-purple-300">{{ $sickCount + $permitCount }}</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-rose-50/70 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/50">
                            <span class="text-xs font-bold text-rose-800 dark:text-rose-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Tanpa Keterangan (Alpa)
                            </span>
                            <span class="font-black text-xs text-rose-700 dark:text-rose-300">{{ $alphaCount }}</span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span> Belum Tercatat / Hadir
                            </span>
                            <span class="font-black text-xs text-slate-700 dark:text-slate-300">{{ $unmarkedCount }}</span>
                        </div>

                        @if($activePermitsCount > 0)
                            <div class="flex items-center justify-between p-2.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800">
                                <span class="text-xs font-bold text-indigo-800 dark:text-indigo-300 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span> Sedang Izin Keluar
                                </span>
                                <span class="font-black text-xs text-indigo-700 dark:text-indigo-300">{{ $activePermitsCount }} Siswa</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Akses Monitoring & Supervisi Eksekutif -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-1">Pintasan Monitoring & Supervisi</h3>

                    <a href="{{ route('admin.teaching_journals.index') }}" 
                       class="group p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                            <span class="material-icons text-xl">auto_stories</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Supervisi Jurnal Mengajar</h4>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Verifikasi & Beri Catatan Guru</p>
                        </div>
                        <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>

                    <a href="{{ route('admin.reports.charts') }}" 
                       class="group p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-sky-300 dark:hover:border-sky-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                            <span class="material-icons text-xl">analytics</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Monitoring Presensi Harian</h4>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Laporan & Analisis Kedisiplinan</p>
                        </div>
                        <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>

                    <a href="{{ route('teacher.subject.attendance.report') }}" 
                       class="group p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                            <span class="material-icons text-xl">menu_book</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Monitoring Presensi Mapel</h4>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Rekapitulasi Sesi Mengajar Kelas</p>
                        </div>
                        <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>

                    <a href="{{ route('teacher.subject.attendance.report', ['type' => 'cocurricular']) }}" 
                       class="group p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                            <span class="material-icons text-xl">psychology</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Monitoring Kokurikuler</h4>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Rekap & Progres Proyek P5</p>
                        </div>
                        <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>
                </div>

            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const chartCtx = document.getElementById('principalAttendanceChart');

            if (chartCtx) {
                const ctx = chartCtx.getContext('2d');
                const labels = @json($chartDates);
                const data = @json($chartPercentages);

                // Create gradient background
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, isDarkMode ? 'rgba(99, 102, 241, 0.4)' : 'rgba(99, 102, 241, 0.25)');
                gradient.addColorStop(1, isDarkMode ? 'rgba(99, 102, 241, 0.0)' : 'rgba(99, 102, 241, 0.0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Kehadiran (%)',
                            data: data,
                            borderColor: '#6366f1',
                            borderWidth: 3,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#6366f1',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: (val) => val + '%',
                                    color: isDarkMode ? '#94a3b8' : '#64748b',
                                    font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                                },
                                grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                ticks: {
                                    color: isDarkMode ? '#94a3b8' : '#64748b',
                                    font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                                },
                                grid: { display: false }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDarkMode ? '#1e293b' : '#0f172a',
                                titleFont: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 12, weight: 'bold' },
                                bodyFont: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 12 },
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label: (ctx) => ' Kehadiran Sekolah: ' + ctx.parsed.y + '%'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
