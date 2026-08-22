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
            <div class="lg:col-span-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-sky-950 text-white p-5 sm:p-7 shadow-lg border border-slate-800/80">
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
                            Pantau tingkat kehadiran harian siswa, pantau izin keluar, dan tindak lanjuti persetujuan kehadiran dengan cepat.
                        </p>
                    </div>

                    <!-- Clear CTA Hierarchy: Primary Scan + Secondary Izin -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2">
                        <a href="{{ route('scanner') }}" target="_blank" 
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-2xl bg-sky-500 hover:bg-sky-400 text-white text-sm font-bold shadow-lg shadow-sky-500/25 transition-all transform hover:scale-[1.02] active:scale-[0.98] min-h-[44px]">
                            <span class="material-icons text-lg">qr_code_scanner</span>
                            <span>Scan Kehadiran</span>
                        </a>
                        <a href="{{ route('permit.scanner') }}" target="_blank" 
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-sky-200 hover:text-white text-xs font-semibold backdrop-blur-md border border-white/15 transition-all min-h-[44px]">
                            <span class="material-icons text-base">assignment</span>
                            <span>Scan Izin Keluar &rarr;</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Action Summary Widget (4 cols) -->
            <div class="lg:col-span-4 rounded-3xl bg-white dark:bg-slate-900 p-5 sm:p-6 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                            <span class="material-icons text-sky-500 text-base">bolt</span>
                            Akses Cepat
                        </h3>
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Pintasan</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-3.5">
                        <a href="{{ route('admin.leave_requests.index') }}" 
                           class="relative flex flex-col items-center justify-center text-center p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all group min-h-[44px]">
                            @if(isset($pendingLeaveRequestsCount) && $pendingLeaveRequestsCount > 0)
                                <span class="absolute top-2 right-2 flex h-5 min-w-[20px] px-1.5 items-center justify-center rounded-full bg-rose-500 text-white text-[10px] font-extrabold shadow-xs animate-pulse">
                                    {{ $pendingLeaveRequestsCount }}
                                </span>
                            @endif
                            <span class="material-icons text-2xl text-amber-600 dark:text-amber-400 mb-1 group-hover:scale-110 transition-transform">assignment_turned_in</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Izin Siswa</span>
                            <span class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold mt-0.5">Persetujuan</span>
                        </a>

                        <a href="{{ route('admin.reports.create') }}" 
                           class="flex flex-col items-center justify-center text-center p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-900/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all group min-h-[44px]">
                            <span class="material-icons text-2xl text-emerald-600 dark:text-emerald-400 mb-1 group-hover:scale-110 transition-transform">bar_chart</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Rekap Laporan</span>
                            <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold mt-0.5">PDF / Excel</span>
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
                        <span class="material-icons text-sm text-sky-500">forum</span>
                        Komunikasi
                    </span>
                    <a href="{{ route('admin.chat.index') }}" class="font-bold text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-0.5">
                        <span>Pesan Orang Tua</span>
                        <span class="material-icons text-sm">chevron_right</span>
                    </a>
                </div>
            </div>
        </div>
        
        {{-- KARTU KONSOLIDASI RINGKASAN KEHADIRAN HARI INI --}}
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
            <!-- Header Ringkasan -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-xl">analytics</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Status Kehadiran Hari Ini</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Total <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $totalAllStudents }} Siswa</span> terdaftar dalam sistem
                        </p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div>
                    @if($hasAttendanceData)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900/40">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Presensi Berlangsung ({{ $totalRecorded }} Tercatat)</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span>Belum ada aktivitas presensi</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Main Statistics Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mt-5">
                
                <!-- 1. Hadir Total -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Hadir Total</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $hasAttendanceData ? $overallAttendancePercentage . '%' : '—' }}
                        </div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $totalPresent }} dari {{ $totalAllStudents }} Siswa
                        </p>
                    </div>
                </div>

                <!-- 2. Tepat Waktu -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Tepat Waktu</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ $totalOnTime }}
                        </div>
                        <p class="text-[11px] font-semibold text-emerald-700/80 dark:text-emerald-400/80 mt-0.5">
                            {{ $hasAttendanceData && ($totalOnTime + $totalLate > 0) ? $overallOnTimePercentage . '% hadir tepat' : 'Siswa' }}
                        </p>
                    </div>
                </div>

                <!-- 3. Terlambat -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-amber-50/40 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Terlambat</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">
                            {{ $totalLate }}
                        </div>
                        <p class="text-[11px] font-semibold text-amber-700/80 dark:text-amber-400/80 mt-0.5">
                            {{ $hasAttendanceData && ($totalOnTime + $totalLate > 0) ? $overallLatenessPercentage . '% keterlambatan' : 'Siswa' }}
                        </p>
                    </div>
                </div>

                <!-- 4. Izin -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-purple-50/40 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/30 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-purple-700 dark:text-purple-400">Siswa Izin</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight">
                            {{ $totalIzin }}
                        </div>
                        <p class="text-[11px] font-semibold text-purple-700/80 dark:text-purple-400/80 mt-0.5">
                            Izin disetujui
                        </p>
                    </div>
                </div>

                <!-- 5. Sakit -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400">Siswa Sakit</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                            {{ $totalSakit }}
                        </div>
                        <p class="text-[11px] font-semibold text-indigo-700/80 dark:text-indigo-400/80 mt-0.5">
                            Keterangan sakit
                        </p>
                    </div>
                </div>

                <!-- 6. Belum Absen / Alpa (Tanpa misleading 100% jika data kosong) -->
                <div class="p-3.5 sm:p-4 rounded-2xl {{ $hasAttendanceData && $totalAlpa > 0 ? 'bg-rose-50/40 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30' : 'bg-slate-50/70 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800/80' }} flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold {{ $hasAttendanceData && $totalAlpa > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">
                            {{ $hasAttendanceData ? 'Alpa / Tanpa Ket.' : 'Belum Absen' }}
                        </span>
                        <span class="w-2.5 h-2.5 rounded-full {{ $hasAttendanceData && $totalAlpa > 0 ? 'bg-rose-500' : 'bg-slate-400' }}"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black {{ $hasAttendanceData && $totalAlpa > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }} tracking-tight">
                            @if($hasAttendanceData)
                                {{ $totalAlpa }}
                            @else
                                {{ $totalAllStudents }}
                            @endif
                        </div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                            @if($hasAttendanceData)
                                {{ $totalUnrecorded }} belum tercatat
                            @else
                                Belum ada data
                            @endif
                        </p>
                    </div>
                </div>

            </div>

            <!-- Multi-segment visual bar -->
            @if($hasAttendanceData && $totalAllStudents > 0)
                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                        <span>Proporsi Kehadiran Siswa</span>
                        <span>{{ $totalRecorded }} dari {{ $totalAllStudents }} Siswa Tercatat</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden flex">
                        <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ ($totalOnTime / $totalAllStudents) * 100 }}%" title="Tepat Waktu: {{ $totalOnTime }}"></div>
                        <div class="bg-amber-500 h-full transition-all duration-500" style="width: {{ ($totalLate / $totalAllStudents) * 100 }}%" title="Terlambat: {{ $totalLate }}"></div>
                        <div class="bg-purple-500 h-full transition-all duration-500" style="width: {{ ($totalIzin / $totalAllStudents) * 100 }}%" title="Izin: {{ $totalIzin }}"></div>
                        <div class="bg-indigo-500 h-full transition-all duration-500" style="width: {{ ($totalSakit / $totalAllStudents) * 100 }}%" title="Sakit: {{ $totalSakit }}"></div>
                        <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ ($totalAlpa / $totalAllStudents) * 100 }}%" title="Alpa: {{ $totalAlpa }}"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- GRAFIK KEHADIRAN PER KELAS --}}
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-sky-500 text-lg">leaderboard</span>
                        <span>Persentase Kehadiran per Kelas</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Tingkat kehadiran efektif pada: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $selectedDate->translatedFormat('l, d F Y') }}</span>
                    </p>
                </div>

                <a href="{{ route('admin.reports.charts') }}" class="inline-flex items-center gap-1 text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline self-start sm:self-auto">
                    <span>Lihat Analisis Detail</span>
                    <span class="material-icons text-sm">arrow_forward</span>
                </a>
            </div>
            
            @if(!$hasAttendanceData || $totalPresent === 0)
                <!-- Compact Informative Empty State for Chart -->
                <div class="p-6 sm:p-8 rounded-2xl bg-slate-50/70 dark:bg-slate-850/50 border border-dashed border-slate-200 dark:border-slate-800 text-center flex flex-col items-center justify-center my-2">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-500 flex items-center justify-center mb-3">
                        <span class="material-icons text-2xl">bar_chart</span>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Kehadiran Kelas</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mt-1 leading-relaxed">
                        Grafik persentase kehadiran rombongan belajar akan terisi otomatis setelah presensi siswa tercatat hari ini.
                    </p>
                </div>
            @else
                <!-- Responsive Chart Container (Auto horizontal bar on mobile for readable labels) -->
                <div class="h-64 sm:h-80 w-full relative">
                    <canvas id="classAttendanceChart"></canvas>
                </div>
            @endif
        </div>

        <!-- Panel Pemantauan Langsung (Izin Keluar & Belum Pulang) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
            
            <!-- Panel Siswa Sedang Izin Keluar -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">time_to_leave</span>
                            <span>Siswa Sedang Izin Keluar</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Siswa yang meninggalkan sekolah dan belum kembali</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $studentsOnPermit->count() > 0 ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                        {{ $studentsOnPermit->count() }} Siswa
                    </span>
                </div>

                <div class="flex-1 divide-y divide-slate-100 dark:divide-slate-800/80 overflow-y-auto max-h-72 no-scrollbar">
                    @forelse($studentsOnPermit as $permit)
                        <div class="p-3.5 sm:p-4 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <span class="material-icons text-lg">directions_walk</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $permit->student->name }}</h4>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40 shrink-0">
                                        Keluar: {{ $permit->time_out->format('H:i') }}
                                    </span>
                                </div>
                                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $permit->student->schoolClass->name ?? 'Tanpa Kelas' }}</p>
                                <div class="mt-1.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-[11px] text-slate-600 dark:text-slate-300 italic border border-slate-100 dark:border-slate-700/60">
                                    "{{ $permit->reason }}"
                                </div>
                            </div>
                        </div>
                    @empty
                        <!-- Compact Empty State -->
                        <div class="p-4 sm:p-5 text-center">
                            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 text-xs font-semibold border border-emerald-200/50 dark:border-emerald-900/30">
                                <span class="material-icons text-base text-emerald-600">check_circle</span>
                                <span>Tidak ada siswa yang izin keluar saat ini.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Siswa Belum Absen Pulang -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-sky-500 text-lg">meeting_room</span>
                            <span>Siswa Belum Absen Pulang</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sudah hadir masuk tapi belum memindai presensi pulang</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $studentsNotCheckedOut->count() > 0 ? 'bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                        {{ $studentsNotCheckedOut->count() }} Siswa
                    </span>
                </div>

                <div class="flex-1 divide-y divide-slate-100 dark:divide-slate-800/80 overflow-y-auto max-h-72 no-scrollbar">
                    @forelse($studentsNotCheckedOut as $attendance)
                        <div class="p-3.5 sm:p-4 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <img class="h-9 w-9 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700 shrink-0 cursor-pointer hover:ring-2 hover:ring-sky-500/50 hover:scale-105 active:scale-95 transition-all student-avatar" 
                                     src="{{ $attendance->student->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($attendance->student->name) . '&color=0284c7&background=e0f2fe' }}" 
                                     alt="{{ $attendance->student->name }}"
                                     onclick="previewStudentPhoto('{{ $attendance->student->photo_url ?? '' }}', '{{ addslashes($attendance->student->name) }}', '{{ $attendance->student->schoolClass->name ?? '' }} {{ $attendance->student->nis ? '&bull; NIS ' . $attendance->student->nis : '' }}')"
                                     title="Klik untuk memperbesar foto siswa">
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $attendance->student->name }}</h4>
                                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $attendance->student->schoolClass->name ?? 'Tanpa Kelas' }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] text-slate-400 block">Masuk</span>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-lg border border-emerald-200/60 dark:border-emerald-900/40">
                                    <span class="material-icons text-xs">login</span>
                                    {{ $attendance->attendance_time->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <!-- Compact Empty State -->
                        <div class="p-4 sm:p-5 text-center">
                            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold">
                                <span class="material-icons text-base text-sky-500">done_all</span>
                                <span>Semua siswa yang hadir telah memindai presensi pulang.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Tabel & Kartu Rekap Kehadiran Harian & Filter Bar -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-5 sm:p-6">
                
                <!-- Filter Toolbar -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-sky-500 text-lg">table_chart</span>
                                <span>Rekap Presensi Harian</span>
                            </h3>
                            <a href="{{ route('admin.reports.create') }}" class="lg:hidden text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline flex items-center gap-0.5">
                                <span>Laporan Lengkap</span>
                                <span class="material-icons text-xs">arrow_forward</span>
                            </a>
                        </div>
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

                        <x-primary-button type="submit" class="w-full sm:w-auto justify-center min-h-[44px]">
                            <span class="material-icons text-sm">filter_alt</span>
                            <span>Filter</span>
                        </x-primary-button>
                    </form>
                </div>

                {{-- TAMPILAN MOBILE: Card List Format (Bebas horizontal scroll) --}}
                <div class="block sm:hidden space-y-3">
                    @forelse ($attendances as $attendance)
                        <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-850/60 border border-slate-200/70 dark:border-slate-800 flex flex-col gap-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ substr($attendance->student->name ?? 'S', 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $attendance->student->name ?? 'Siswa Dihapus' }}</h4>
                                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ $attendance->student->schoolClass->name ?? '-' }}</p>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div>
                                    @if ($attendance->status === 'tepat_waktu')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Tepat Waktu
                                        </span>
                                    @elseif ($attendance->status === 'terlambat')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Terlambat
                                        </span>
                                    @elseif ($attendance->status === 'izin')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                            Izin
                                        </span>
                                    @elseif ($attendance->status === 'sakit')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                            Sakit
                                        </span>
                                    @elseif ($attendance->status === 'alpa')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Alpa
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Time Info Row -->
                            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200/60 dark:border-slate-800/60 text-slate-500 dark:text-slate-400">
                                <div class="flex items-center gap-1">
                                    <span class="material-icons text-xs text-emerald-500">login</span>
                                    <span>Masuk:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                        @if(in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                            -
                                        @else
                                            {{ $attendance->attendance_time->format('H:i') }}
                                        @endif
                                    </span>
                                </div>

                                <div class="flex items-center gap-1">
                                    <span class="material-icons text-xs text-sky-500">logout</span>
                                    <span>Pulang:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                        @if ($attendance->checkout_time)
                                            {{ $attendance->checkout_time->format('H:i') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center rounded-2xl bg-slate-50 dark:bg-slate-850/50 border border-slate-200/60 dark:border-slate-800 text-slate-400 text-xs italic">
                            Tidak ada data kehadiran yang sesuai dengan filter.
                        </div>
                    @endforelse
                </div>
                
                {{-- TAMPILAN DESKTOP: Modern Responsive Table --}}
                <div class="hidden sm:block overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
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
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Terlambat
                                            </span>
                                        @elseif ($attendance->status === 'izin')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                                Izin
                                            </span>
                                        @elseif ($attendance->status === 'sakit')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                Sakit
                                            </span>
                                        @elseif ($attendance->status === 'alpa')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
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

    <!-- Student Photo Preview Modal Component -->
    <x-photo-preview-modal />

    @push('scripts')
    @if($hasAttendanceData && $totalPresent > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartCanvas = document.getElementById('classAttendanceChart');
            if (!chartCanvas) return;

            const classData = @json($classAttendanceStats);
            const isDarkMode = document.documentElement.classList.contains('dark');
            const isMobile = window.innerWidth < 640;
            
            const labels = classData.map(item => item.name);
            const percentages = classData.map(item => item.percentage);
            const ratios = classData.map(item => item.ratio);
            const ctx = chartCanvas.getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Kehadiran (%)',
                        data: percentages,
                        backgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.75)' : 'rgba(2, 132, 199, 0.8)',
                        hoverBackgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.95)' : 'rgba(2, 132, 199, 0.95)',
                        borderColor: isDarkMode ? '#38bdf8' : '#0284c7',
                        borderWidth: 0,
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: isMobile ? 22 : 36
                    }]
                },
                options: {
                    indexAxis: isMobile ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        [isMobile ? 'x' : 'y']: { 
                            beginAtZero: true, 
                            max: 100, 
                            ticks: { 
                                callback: (value) => value + '%', 
                                color: isDarkMode ? '#94a3b8' : '#64748b',
                                font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 10, weight: '600' }
                            }, 
                            grid: { 
                                color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' 
                            } 
                        },
                        [isMobile ? 'y' : 'x']: { 
                            ticks: { 
                                color: isDarkMode ? '#cbd5e1' : '#475569',
                                font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: isMobile ? 11 : 12, weight: '700' }
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
                                label: function(context) {
                                    const index = context.dataIndex;
                                    const val = context.raw;
                                    return [' Kehadiran: ' + val + '%', ' ' + (ratios[index] || '')];
                                }
                            } 
                        }
                    }
                }
            });
        });
    </script>
    @endif
    @endpush
</x-app-layout>

