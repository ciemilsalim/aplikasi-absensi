<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Presensi', 'url' => route('admin.dashboard')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Dasbor & Pemantauan Kehadiran
                </h1>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Monitoring</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- === PERINGATAN HARI EFEKTIF === --}}
        @if(isset($isEffectiveDaysSet) && !$isEffectiveDaysSet)
            <div class="bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300/80 dark:border-amber-800/60 p-4 rounded-2xl shadow-xs">
                <div class="flex items-start gap-3.5">
                    <div class="p-2 bg-amber-500 text-white rounded-xl shadow-xs shrink-0">
                        <span class="material-icons text-xl">warning_amber</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-amber-900 dark:text-amber-100">Perhatian: Hari Efektif Belajar Belum Dikonfigurasi</h4>
                        <p class="text-xs text-amber-800 dark:text-amber-300/90 mt-0.5 leading-relaxed">
                            Jumlah hari efektif sekolah untuk bulan ini belum diisi di portal data. Kalkulasi persentase pada rekap laporan menggunakan estimasi hari kerja.
                        </p>
                        <a href="{{ env('SIPADA_URL', 'http://localhost:8000') }}/dashboard" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 dark:text-amber-400 hover:underline mt-1.5">
                            Atur di SIPADA (Sistem Pangkalan Data) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Hero Section: Welcome & Quick Access -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            
            <!-- Hero Welcome Card (8 cols) -->
            <div class="lg:col-span-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-sky-950 text-white p-6 sm:p-7 shadow-lg border border-slate-800/80">
                <!-- Background decorative elements -->
                <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute right-10 top-0 w-40 h-40 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col justify-between h-full space-y-4">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold text-sky-200 border border-white/10">
                            <span class="material-icons text-xs">calendar_today</span>
                            <span>{{ $selectedDate->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight leading-snug">
                            Selamat Datang, {{ Auth::user()->name }}! 👋
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
                            Pantau tingkat kehadiran harian, pantau siswa yang sedang izin keluar, dan cetak rekapitulasi kehadiran dengan cepat.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <a href="{{ route('scanner') }}" target="_blank" 
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-white text-xs font-bold shadow-md shadow-sky-500/25 transition-all transform hover:scale-105 active:scale-95">
                            <span class="material-icons text-base">qr_code_scanner</span>
                            <span>Buka Scanner Hadir</span>
                        </a>
                        <a href="{{ route('permit.scanner') }}" target="_blank" 
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-md text-white text-xs font-bold border border-white/20 transition-all">
                            <span class="material-icons text-base">assignment</span>
                            <span>Buka Scanner Izin</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Action Summary Widget (4 cols) -->
            <div class="lg:col-span-4 rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                            <span class="material-icons text-sky-500 text-base">bolt</span>
                            Akses Cepat
                        </h3>
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Pintasan</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <a href="{{ route('admin.leave_requests.index') }}" 
                           class="flex flex-col items-center justify-center text-center p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all group">
                            <span class="material-icons text-2xl text-amber-600 dark:text-amber-400 mb-1 group-hover:scale-110 transition-transform">fact_check</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Izin Siswa</span>
                            <span class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold mt-0.5">Persetujuan</span>
                        </a>

                        <a href="{{ route('admin.reports.create') }}" 
                           class="flex flex-col items-center justify-center text-center p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-900/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all group">
                            <span class="material-icons text-2xl text-emerald-600 dark:text-emerald-400 mb-1 group-hover:scale-110 transition-transform">bar_chart</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Rekap Laporan</span>
                            <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold mt-0.5">PDF / Excel</span>
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1"><span class="material-icons text-sm text-sky-500">schedule</span> Presensi Realtime</span>
                    <a href="{{ route('admin.chat.index') }}" class="font-bold text-sky-600 dark:text-sky-400 hover:underline">Pesan Ortu &rarr;</a>
                </div>
            </div>
        </div>
        
        {{-- KARTU STATISTIK KPI (6 Grid Cards) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4">
            
            <!-- 1. Total Kehadiran -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Hadir Total</span>
                    <div class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <span class="material-icons text-base">how_to_reg</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ $overallAttendancePercentage }}%</div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-teal-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $overallAttendancePercentage) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Siswa Tepat Waktu -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tepat Waktu</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-base">task_alt</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">{{ $overallOnTimePercentage }}%</div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $overallOnTimePercentage) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- 3. Siswa Terlambat -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Terlambat</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-icons text-base">alarm_on</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 tracking-tight">{{ $overallLatenessPercentage }}%</div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $overallLatenessPercentage) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- 4. Total Tidak Hadir -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tidak Hadir</span>
                    <div class="w-8 h-8 rounded-xl bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                        <span class="material-icons text-base">person_off</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-orange-600 dark:text-orange-400 tracking-tight">{{ $overallAbsentPercentage }}%</div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-orange-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $overallAbsentPercentage) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- 5. Siswa Izin -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Siswa Izin</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-icons text-base">assignment</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 tracking-tight">{{ $totalIzin }} <span class="text-xs font-semibold text-slate-400">Siswa</span></div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Disetujui hari ini</p>
                </div>
            </div>

            <!-- 6. Siswa Sakit -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Siswa Sakit</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-base">healing</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">{{ $totalSakit }} <span class="text-xs font-semibold text-slate-400">Siswa</span></div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Keterangan sakit</p>
                </div>
            </div>

        </div>

        {{-- GRAFIK KEHADIRAN PER KELAS --}}
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-sky-500 text-lg">leaderboard</span>
                        Persentase Kehadiran per Rombongan Belajar (Hadir Efektif)
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Data kehadiran kelas pada tanggal: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $selectedDate->translatedFormat('l, d F Y') }}</span>
                    </p>
                </div>
            </div>
            
            <div class="h-72 md:h-80 w-full">
                <canvas id="classAttendanceChart"></canvas>
            </div>
        </div>

        <!-- Panel Pemantauan Langsung (Izin Keluar & Belum Pulang) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Panel Siswa Sedang Izin Keluar -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">time_to_leave</span>
                            Siswa Sedang Izin Keluar
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Daftar siswa yang keluar dan belum kembali ke sekolah</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">
                        {{ $studentsOnPermit->count() }} Siswa
                    </span>
                </div>

                <div class="flex-1 divide-y divide-slate-100 dark:divide-slate-800/80 overflow-y-auto max-h-80 no-scrollbar">
                    @forelse($studentsOnPermit as $permit)
                        <div class="p-4 flex items-start gap-3.5 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <span class="material-icons text-xl">directions_walk</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $permit->student->name }}</h4>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40">
                                        Keluar: {{ $permit->time_out->format('H:i') }}
                                    </span>
                                </div>
                                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $permit->student->schoolClass->name ?? 'Tanpa Kelas' }}</p>
                                <div class="mt-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-[11px] text-slate-600 dark:text-slate-300 italic border border-slate-100 dark:border-slate-700/60">
                                    "{{ $permit->reason }}"
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                <span class="material-icons text-2xl">check_circle</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">Tidak ada siswa yang sedang izin keluar saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Siswa Belum Absen Pulang -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-sky-500 text-lg">meeting_room</span>
                            Siswa Belum Absen Pulang
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sudah hadir masuk tapi belum memindai presensi pulang</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300">
                        {{ $studentsNotCheckedOut->count() }} Siswa
                    </span>
                </div>

                <div class="flex-1 divide-y divide-slate-100 dark:divide-slate-800/80 overflow-y-auto max-h-80 no-scrollbar">
                    @forelse($studentsNotCheckedOut as $attendance)
                        <div class="p-4 flex items-center justify-between gap-3.5 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <img class="h-10 w-10 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700 shrink-0" 
                                     src="{{ $attendance->student->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($attendance->student->name) . '&color=0284c7&background=e0f2fe' }}" 
                                     alt="{{ $attendance->student->name }}">
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $attendance->student->name }}</h4>
                                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $attendance->student->schoolClass->name ?? 'Tanpa Kelas' }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] text-slate-400 block">Masuk</span>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-lg border border-emerald-200/60 dark:border-emerald-900/40">
                                    <span class="material-icons text-xs">login</span>
                                    {{ $attendance->attendance_time->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                <span class="material-icons text-2xl">done_all</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">Semua siswa yang hadir telah memindai presensi pulang.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Tabel Rekap Kehadiran Harian & Filter Bar -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6">
                
                <!-- Filter Toolbar -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-sky-500 text-lg">table_chart</span>
                            Rekap Presensi Harian
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Menampilkan data tanggal: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $selectedDate->translatedFormat('l, d F Y') }}</span>
                        </p>
                    </div>

                    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2.5">
                        <div class="w-full sm:w-48">
                            <x-text-input type="text" name="search" placeholder="Cari nama siswa..." value="{{ request('search') }}" />
                        </div>
                        
                        <select name="school_class_id" class="w-full sm:w-auto border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 rounded-xl text-sm shadow-2xs py-2.5 px-3">
                            <option value="">Semua Rombel/Kelas</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        
                        <div class="w-full sm:w-auto">
                            <x-text-input type="date" name="tanggal" id="tanggal" value="{{ $selectedDate->format('Y-m-d') }}" />
                        </div>

                        <x-primary-button type="submit">
                            <span class="material-icons text-sm">filter_alt</span>
                            <span>Filter</span>
                        </x-primary-button>
                    </form>
                </div>
                
                <!-- Modern Responsive Table -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                        <thead class="text-[11px] font-bold uppercase tracking-wider bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Nama Siswa</th>
                                <th scope="col" class="px-5 py-3.5">Kelas</th>
                                <th scope="col" class="px-5 py-3.5">Jam Masuk</th>
                                <th scope="col" class="px-5 py-3.5">Jam Pulang</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse ($attendances as $attendance)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/50 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-xs">
                                                {{ substr($attendance->student->name ?? 'S', 0, 1) }}
                                            </div>
                                            <span>{{ $attendance->student->name ?? 'Siswa Dihapus' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 font-semibold text-slate-600 dark:text-slate-300">
                                        {{ $attendance->student->schoolClass->name ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if(in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                            <span class="text-slate-400">-</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900/40">
                                                <span class="material-icons text-xs">login</span>
                                                {{ $attendance->attendance_time->format('H:i:s') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if ($attendance->checkout_time)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-900/40">
                                                <span class="material-icons text-xs">logout</span>
                                                {{ $attendance->checkout_time->format('H:i:s') }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 font-medium">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        @if ($attendance->status === 'tepat_waktu')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Tepat Waktu
                                            </span>
                                        @elseif ($attendance->status === 'terlambat')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Terlambat
                                            </span>
                                        @elseif ($attendance->status === 'izin')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                                Izin
                                            </span>
                                        @elseif ($attendance->status === 'sakit')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Sakit
                                            </span>
                                        @elseif ($attendance->status === 'alpa')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Alpa
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">
                                        Tidak ada data kehadiran yang sesuai dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $attendances->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const classData = @json($classAttendanceStats);
            const isDarkMode = document.documentElement.classList.contains('dark');
            const labels = classData.map(item => item.name);
            const percentages = classData.map(item => item.percentage);
            const ctx = document.getElementById('classAttendanceChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Kehadiran (%)',
                        data: percentages,
                        backgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.7)' : 'rgba(2, 132, 199, 0.75)',
                        hoverBackgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.95)' : 'rgba(2, 132, 199, 0.95)',
                        borderColor: isDarkMode ? '#38bdf8' : '#0284c7',
                        borderWidth: 0,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 45
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 100, 
                            ticks: { 
                                callback: (value) => value + '%', 
                                color: isDarkMode ? '#94a3b8' : '#64748b',
                                font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                            }, 
                            grid: { 
                                color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' 
                            } 
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
                                label: (context) => ' Kehadiran Efektif: ' + context.parsed.y + '%' 
                            } 
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>

