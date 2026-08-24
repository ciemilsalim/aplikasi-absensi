<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'pembina_ekskul'])],
                    ['title' => 'Presensi Ekskul', 'url' => route('teacher.extracurricular-attendance.index')],
                    ['title' => 'Rekap Presensi', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Rekap Presensi
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                        {{ $extracurricular->name }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        {{ $totalMembers }} Anggota &bull; {{ $totalSessions }} Sesi
                    </span>
                </div>
            </div>

            <!-- Action Controls: Contextual Filter & Secondary PDF Export -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.extracurricular-attendance.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors shadow-2xs">
                    <span class="material-icons text-sm text-amber-500">sports_soccer</span>
                    <span>Kegiatan Binaan</span>
                </a>

                <a href="{{ route('teacher.extracurricular-attendance.print', array_merge(['extracurricular' => $extracurricular->id], $requestInputs)) }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-2xs transition-all active:scale-95">
                    <span class="material-icons text-sm text-rose-500">picture_as_pdf</span>
                    <span>Export PDF</span>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $potentialAttendance = $totalMembers * $totalSessions;
        $attendanceRate = $potentialAttendance > 0 ? round(($totalHadir / $potentialAttendance) * 100, 1) : 0;
    @endphp

    <div class="space-y-5 sm:space-y-6 pb-32 sm:pb-12" 
         x-data="{ 
            searchQuery: '', 
            statusFilter: 'all',
            viewMode: 'cards',
            showFilterBox: false,
            detailModal: false,
            selectedStudent: null,
            openDetail(name, nis, className, hadir, sakit, izin, alpa, totalSesi, percent) {
                this.selectedStudent = { name, nis, className, hadir, sakit, izin, alpa, totalSesi, percent };
                this.detailModal = true;
            }
         }">
        
        <!-- Filter Bar Card (Compact & Contextual) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5">
            <form action="{{ route('teacher.extracurricular-attendance.report', $extracurricular) }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap flex-1">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1">
                        <span class="material-icons text-amber-500 text-sm">calendar_month</span>
                        <span>Periode:</span>
                    </span>
                    <div class="flex items-center gap-2 flex-1 sm:flex-none">
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" 
                               class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                        <span class="text-xs text-slate-400 font-bold">&ndash;</span>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" 
                               class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                    </div>
                </div>

                <button type="submit" 
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-2xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-750 text-white font-bold text-xs shadow-xs transition-all active:scale-95 shrink-0">
                    <span class="material-icons text-sm">filter_alt</span>
                    <span>Terapkan Filter</span>
                </button>
            </form>
        </div>

        <!-- 4 KPI Metrics Card (Target Aware) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- KPI 1: Rata-rata Kehadiran (Focal Point) -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Hadir</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-base">pie_chart</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $attendanceRate }}%</span>
                        @if($attendanceRate >= 80)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                +{{ round($attendanceRate - 80, 1) }}% target
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                -{{ round(80 - $attendanceRate, 1) }}% target
                            </span>
                        @endif
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $attendanceRate)) }}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium">
                        {{ $totalHadir }} kehadiran dari {{ $potentialAttendance }} kapasitas sesi
                    </p>
                </div>
            </div>

            <!-- KPI 2: Anggota & Sesi -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Anggota & Sesi</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-base">groups</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalMembers }} <span class="text-xs font-bold text-slate-500">Anggota</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        <strong class="text-slate-700 dark:text-slate-200">{{ $totalSessions }}</strong> Sesi Latihan Terlaksana
                    </p>
                </div>
            </div>

            <!-- KPI 3: Sakit & Izin -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sakit & Izin</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-icons text-base">medical_services</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight">
                        {{ $totalSakit + $totalIzin }} <span class="text-xs font-bold text-slate-500">kasus</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Sakit <strong class="text-slate-700 dark:text-slate-200">{{ $totalSakit }}</strong> &bull; Izin <strong class="text-slate-700 dark:text-slate-200">{{ $totalIzin }}</strong>
                    </p>
                </div>
            </div>

            <!-- KPI 4: Alpa -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alpa / Tanpa Ket.</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-icons text-base">person_off</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black {{ $totalAlpa > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tracking-tight">
                        {{ $totalAlpa }} <span class="text-xs font-bold text-slate-500">Alpa</span>
                    </div>
                    <p class="text-[11px] {{ $totalAlpa > 0 ? 'text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400' }} mt-1 font-medium">
                        Ketidakhadiran tanpa izin
                    </p>
                </div>
            </div>
        </div>

        <!-- Mode Tampilan Switcher & Filter Tools Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <!-- Mode Switcher -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-2xl self-start sm:self-auto">
                    <button @click="viewMode = 'cards'" 
                            :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all">
                        <span class="material-icons text-sm">view_agenda</span>
                        <span>Ringkasan</span>
                    </button>
                    <button @click="viewMode = 'matrix'" 
                            :class="viewMode === 'matrix' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all">
                        <span class="material-icons text-sm">grid_on</span>
                        <span>Matriks</span>
                    </button>
                </div>

                <!-- Instant Search Input -->
                <div class="relative w-full sm:w-72">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama, NIS, atau kelas..." 
                           class="w-full text-xs py-2 pl-8 pr-3 bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-amber-500 focus:border-amber-500">
                    <span class="material-icons text-slate-400 text-sm absolute left-2.5 top-1/2 -translate-y-1/2">search</span>
                </div>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-1.5 flex-wrap pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-bold">
                <button type="button" @click="statusFilter = 'all'" 
                        :class="statusFilter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'" 
                        class="px-2.5 py-1 rounded-xl transition-all">
                    Semua Anggota ({{ $totalMembers }})
                </button>
                <button type="button" @click="statusFilter = 'alpa'" 
                        :class="statusFilter === 'alpa' ? 'bg-rose-600 text-white' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 hover:bg-rose-100 border border-rose-200 dark:border-rose-900'" 
                        class="px-2.5 py-1 rounded-xl transition-all">
                    Ada Alpa
                </button>
                <button type="button" @click="statusFilter = 'leave'" 
                        :class="statusFilter === 'leave' ? 'bg-amber-600 text-white' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 hover:bg-amber-100 border border-amber-200 dark:border-amber-900'" 
                        class="px-2.5 py-1 rounded-xl transition-all">
                    Ada Sakit/Izin
                </button>
                <button type="button" @click="statusFilter = 'perfect'" 
                        :class="statusFilter === 'perfect' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-900'" 
                        class="px-2.5 py-1 rounded-xl transition-all">
                    Hadir 100%
                </button>
            </div>
        </div>

        <!-- ================= MODE 1: RINGKASAN KARTU SISWA (DEFAULT MOBILE VIEW) ================= -->
        <div x-show="viewMode === 'cards'" class="space-y-3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            @forelse($students as $student)
                @php
                    $stHadir = 0; $stSakit = 0; $stIzin = 0; $stAlpa = 0;
                    if(count($dates) > 0) {
                        foreach($dates as $date) {
                            $status = $attendanceData[$student->id][$date] ?? null;
                            if ($status === 'hadir') $stHadir++;
                            elseif ($status === 'sakit') $stSakit++;
                            elseif ($status === 'izin') $stIzin++;
                            elseif ($status === 'alpa') $stAlpa++;
                        }
                    }
                    $stPercent = (count($dates) > 0) ? round(($stHadir / count($dates)) * 100) : 0;
                    $badgeColor = $stPercent >= 80 
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' 
                        : ($stPercent >= 60 
                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800' 
                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800');
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-amber-300 dark:hover:border-amber-700 transition-all"
                     x-show="(searchQuery === '' || '{{ mb_strtolower($student->name) }}'.includes(searchQuery.toLowerCase()) || '{{ $student->nis }}'.includes(searchQuery) || '{{ mb_strtolower($student->schoolClass->name ?? '') }}'.includes(searchQuery.toLowerCase())) && (statusFilter === 'all' || (statusFilter === 'alpa' && {{ $stAlpa }} > 0) || (statusFilter === 'leave' && ({{ $stSakit }} > 0 || {{ $stIzin }} > 0)) || (statusFilter === 'perfect' && {{ $stPercent }} === 100))">
                    
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-300 font-bold flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900/40 text-sm overflow-hidden">
                                @if($student->photo)
                                    <img src="{{ $student->photo_url }}" alt="{{ $student->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                @else
                                    <span>{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm leading-tight truncate">
                                    {{ $student->name }}
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">Kelas {{ $student->schoolClass->name ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>NIS: {{ $student->nis ?? '-' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Honest Attendance Ratio & Percent Badge -->
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black border {{ $badgeColor }}">
                                {{ $stPercent }}%
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">
                                {{ $stHadir }} / {{ count($dates) }} hadir
                            </span>
                        </div>
                    </div>

                    <!-- 4 Summary Counter Pills -->
                    <div class="grid grid-cols-4 gap-1.5 sm:gap-2 mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                        <div class="p-2 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/50 dark:border-emerald-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase">Hadir</div>
                            <div class="text-xs sm:text-sm font-black text-emerald-700 dark:text-emerald-300">{{ $stHadir }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/50 dark:border-amber-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase">Sakit</div>
                            <div class="text-xs sm:text-sm font-black text-amber-700 dark:text-amber-300">{{ $stSakit }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/50 dark:border-purple-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-purple-800 dark:text-purple-300 uppercase">Izin</div>
                            <div class="text-xs sm:text-sm font-black text-purple-700 dark:text-purple-300">{{ $stIzin }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/50 dark:border-rose-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-rose-800 dark:text-rose-300 uppercase">Alpa</div>
                            <div class="text-xs sm:text-sm font-black text-rose-700 dark:text-rose-300">{{ $stAlpa }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800">
                    Belum ada anggota terdaftar pada kegiatan ekstrakurikuler ini.
                </div>
            @endforelse
        </div>

        <!-- ================= MODE 2: TABEL MATRIKS PRESENSI DENGAN HORIZONTAL SCROLL ================= -->
        <div x-show="viewMode === 'matrix'" 
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 overflow-hidden" 
             style="display: none;">
            
            <!-- Compact Matrix Header & Status Legend -->
            <div class="p-3.5 sm:p-4 bg-slate-50/80 dark:bg-slate-850/60 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-amber-500 text-base">calendar_view_month</span>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                        Matriks Presensi
                    </h3>
                </div>

                <!-- Status Legend -->
                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">H Hadir</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800">S Sakit</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800">I Izin</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800">A Alpa</span>
                </div>
            </div>

            <!-- Scroll Hint for Mobile -->
            <div class="sm:hidden px-3.5 py-1.5 bg-amber-50/50 dark:bg-amber-950/20 border-b border-amber-100/50 dark:border-amber-900/20 flex items-center justify-between text-[10px] text-amber-800 dark:text-amber-300 font-semibold">
                <span class="flex items-center gap-1">
                    <span class="material-icons text-xs">swipe</span>
                    <span>Geser tabel ke kanan untuk melihat seluruh tanggal sesi</span>
                </span>
            </div>

            <!-- Attendance Matrix Table with Sticky Column -->
            <div class="relative overflow-x-auto overflow-y-hidden scroll-smooth">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300 border-collapse">
                    <thead class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-850 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <!-- Sticky Column: Nama Siswa & Kelas -->
                            <th scope="col" class="sticky left-0 bg-slate-50 dark:bg-slate-850 px-3.5 sm:px-4 py-3 z-20 w-40 min-w-40 max-w-40 sm:w-56 sm:min-w-56 sm:max-w-56 shadow-[3px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[3px_0_10px_-2px_rgba(0,0,0,0.4)] border-r border-slate-200/60 dark:border-slate-750">
                                Siswa / Kelas
                            </th>
                            
                            <!-- Date Columns -->
                            @if(count($dates) > 0)
                                @foreach($dates as $date)
                                    <th scope="col" class="px-2 py-2.5 text-center min-w-10 sm:min-w-12 border-r border-slate-100 dark:border-slate-800">
                                        <div class="font-extrabold text-[11px] text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($date)->format('d') }}</div>
                                        <div class="text-[8px] text-slate-400 font-normal uppercase mt-0.5">{{ \Carbon\Carbon::parse($date)->translatedFormat('M') }}</div>
                                    </th>
                                @endforeach
                            @else
                                <th scope="col" class="px-4 py-2.5 text-center text-slate-400 font-normal">Belum Ada Sesi</th>
                            @endif

                            {{-- Clean Neutral Summary Columns --}}
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8 border-l border-slate-200/60 dark:border-slate-700" title="Hadir">H</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Sakit">S</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Izin">I</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Alpa">A</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-3 py-3 text-center text-[10px] font-black text-slate-800 dark:text-white uppercase min-w-16">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse($students as $student)
                            @php
                                $stHadir = 0; $stSakit = 0; $stIzin = 0; $stAlpa = 0;
                                if(count($dates) > 0) {
                                    foreach($dates as $date) {
                                        $status = $attendanceData[$student->id][$date] ?? null;
                                        if ($status === 'hadir') $stHadir++;
                                        elseif ($status === 'sakit') $stSakit++;
                                        elseif ($status === 'izin') $stIzin++;
                                        elseif ($status === 'alpa') $stAlpa++;
                                    }
                                }
                                $stPercent = (count($dates) > 0) ? round(($stHadir / count($dates)) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors"
                                x-show="(searchQuery === '' || {{ json_encode(mb_strtolower($student->name)) }}.includes(searchQuery.toLowerCase()) || {{ json_encode($student->nis ?? '') }}.includes(searchQuery) || {{ json_encode(mb_strtolower($student->schoolClass->name ?? '')) }}.includes(searchQuery.toLowerCase())) && (statusFilter === 'all' || (statusFilter === 'alpa' && {{ $stAlpa }} > 0) || (statusFilter === 'leave' && ({{ $stSakit }} > 0 || {{ $stIzin }} > 0)) || (statusFilter === 'perfect' && {{ $stPercent }} === 100))">
                                
                                <!-- Sticky Student Name Cell (Explicit Identity) -->
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-3.5 sm:px-4 py-2.5 font-bold text-slate-900 dark:text-white z-10 w-40 min-w-40 max-w-40 sm:w-56 sm:min-w-56 sm:max-w-56 shadow-[3px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[3px_0_10px_-2px_rgba(0,0,0,0.4)] border-r border-slate-200/60 dark:border-slate-750">
                                    <div class="truncate text-xs leading-tight" title="{{ $student->name }}">
                                        {{ $student->name }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate mt-0.5">
                                        Kelas {{ $student->schoolClass->name ?? '-' }} &bull; NIS: {{ $student->nis ?? '-' }}
                                    </div>
                                </td>

                                <!-- Attendance Status per Date -->
                                @if(count($dates) > 0)
                                    @foreach($dates as $date)
                                        @php
                                            $status = $attendanceData[$student->id][$date] ?? null;
                                            $badgeClass = 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500';
                                            $statusText = '-';

                                            switch ($status) {
                                                case 'hadir': 
                                                    $badgeClass = 'bg-emerald-500 text-white font-bold shadow-xs'; 
                                                    $statusText = 'H'; 
                                                    break;
                                                case 'sakit': 
                                                    $badgeClass = 'bg-amber-500 text-white font-bold shadow-xs'; 
                                                    $statusText = 'S'; 
                                                    break;
                                                case 'izin': 
                                                    $badgeClass = 'bg-purple-500 text-white font-bold shadow-xs'; 
                                                    $statusText = 'I'; 
                                                    break;
                                                case 'alpa': 
                                                    $badgeClass = 'bg-rose-500 text-white font-bold shadow-xs'; 
                                                    $statusText = 'A'; 
                                                    break;
                                            }
                                        @endphp
                                        <td class="px-1 py-2 text-center whitespace-nowrap border-r border-slate-100 dark:border-slate-800">
                                            <span class="w-7 h-7 sm:w-7 sm:h-7 mx-auto inline-flex items-center justify-center font-bold text-[10px] rounded-lg {{ $badgeClass }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                    @endforeach
                                @else
                                    <td class="px-4 py-2 text-center text-slate-400 text-xs">-</td>
                                @endif

                                {{-- Neutral Summary Columns --}}
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200 border-l border-slate-200/60 dark:border-slate-700">{{ $stHadir }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $stSakit }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $stIzin }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $stAlpa }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-3 py-2.5 text-center font-black text-slate-900 dark:text-white">
                                    {{ $stPercent }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($dates) + 6 }}" class="py-8 text-center text-slate-400 text-xs">
                                    Belum ada data anggota yang terdaftar pada kegiatan ekstrakurikuler ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
