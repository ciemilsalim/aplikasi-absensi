<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('teacher.dashboard')],
                    ['title' => 'Riwayat Kehadiran', 'url' => route('teacher.attendance.history')]
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Riwayat & Rekap Kehadiran
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 shadow-2xs">
                        Kelas {{ $class->name }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 shadow-2xs">
                    <span class="material-icons text-xs text-sky-500">calendar_month</span>
                    <span>Periode: {{ $selectedDate->isoFormat('MMMM Y') }}</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div 
        x-data="{
            viewMode: 'matrix',
            showModal: false,
            showTrimesterModal: false,
            attendanceId: '',
            studentId: '',
            studentName: '',
            date: '',
            currentStatus: '',
            openModal(attendanceId, studentId, studentName, date, status) {
                this.attendanceId = attendanceId;
                this.studentId = studentId;
                this.studentName = studentName;
                this.date = date;
                this.currentStatus = status || 'hapus';
                this.showModal = true;
            }
        }"
        class="space-y-5 sm:space-y-6"
    >
        <!-- Top 4 KPI Metrics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- KPI 1: Rata-rata Kehadiran Kelas -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Kehadiran</span>
                    <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-base">pie_chart</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $classAvgPercent }}%
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-sky-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $classAvgPercent)) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Total Siswa & Hari Efektif -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Siswa & Efektif</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-base">groups</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ count($students) }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $totalEffectiveWorkdays }} Hari Belajar Efektif
                    </p>
                </div>
            </div>

            <!-- KPI 3: Total Sakit & Izin -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sakit & Izin</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-icons text-base">medical_services</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight">
                        {{ $totalClassSakit + $totalClassIzin }} <span class="text-xs font-semibold text-slate-500">Kasus</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        S: {{ $totalClassSakit }} • I: {{ $totalClassIzin }}
                    </p>
                </div>
            </div>

            <!-- KPI 4: Total Alpa -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alpa / Tanpa Ket.</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-icons text-base">person_off</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black {{ $totalClassAlpa > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tracking-tight">
                        {{ $totalClassAlpa }} <span class="text-xs font-semibold text-slate-500">Kasus</span>
                    </div>
                    <p class="text-[11px] {{ $totalClassAlpa > 0 ? 'text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400' }} mt-0.5">
                        {{ $totalClassAlpa > 0 ? 'Perlu perhatian wali' : 'Tingkat disiplin terjaga' }}
                    </p>
                </div>
            </div>

        </div>

        <!-- Filter & Action Controls Card (Responsive Mobile & Desktop) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5">
            <div class="flex flex-col lg:flex-row gap-3.5 sm:gap-4 justify-between items-stretch lg:items-center">
                
                <!-- Left: Month Filter Form -->
                <form method="GET" action="{{ route('teacher.attendance.history') }}" class="flex items-center gap-2 w-full lg:w-auto">
                    <div class="relative flex-1 lg:flex-initial">
                        <input type="month" name="month" id="month" value="{{ $selectedDate->format('Y-m') }}" 
                               class="w-full lg:w-auto text-xs font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-2.5 sm:py-2.5 sm:px-3.5 focus:ring-4 focus:ring-sky-500/15 focus:border-sky-500 transition-all">
                    </div>
                    <button type="submit" 
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all active:scale-95 shrink-0">
                        <span class="material-icons text-sm">filter_alt</span>
                        <span>Filter</span>
                    </button>
                </form>

                <!-- Right: Action Buttons Group (Print Monthly, Print Trimester, Excel) -->
                <div class="grid grid-cols-3 sm:flex sm:items-center gap-2 w-full lg:w-auto pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800">
                    
                    <!-- Cetak Bulanan Dropdown (A4 / Folio) -->
                    <div x-data="{ openPrintMonthly: false }" class="relative col-span-1">
                        <button @click="openPrintMonthly = !openPrintMonthly" type="button" 
                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                            <span class="material-icons text-sm text-sky-600 dark:text-sky-400">print</span>
                            <span class="truncate">Cetak Bulan</span>
                            <span class="material-icons text-xs text-slate-400">expand_more</span>
                        </button>
                        <div x-show="openPrintMonthly" @click.outside="openPrintMonthly = false" x-transition 
                             class="absolute left-0 sm:right-0 sm:left-auto mt-2 w-56 rounded-2xl bg-white dark:bg-slate-850 shadow-2xl border border-slate-200 dark:border-slate-700 py-2 z-30" style="display: none;">
                            <div class="px-3 py-1 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Pilih Ukuran Kertas</div>
                            <a href="{{ route('teacher.attendance.print', ['month' => $selectedDate->format('Y-m'), 'paper_size' => 'a4']) }}" target="_blank" 
                               class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-sky-50 dark:hover:bg-sky-950/40 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">
                                <span class="material-icons text-sm text-sky-500">description</span>
                                <div>
                                    <div class="font-bold">Kertas A4</div>
                                    <div class="text-[10px] text-slate-400 font-normal">Landscape (297 x 210 mm)</div>
                                </div>
                            </a>
                            <a href="{{ route('teacher.attendance.print', ['month' => $selectedDate->format('Y-m'), 'paper_size' => 'folio']) }}" target="_blank" 
                               class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <span class="material-icons text-sm text-indigo-500">article</span>
                                <div>
                                    <div class="font-bold">Kertas Folio / F4</div>
                                    <div class="text-[10px] text-slate-400 font-normal">Landscape (330 x 215 mm)</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Cetak Triwulan Modal Trigger -->
                    <button @click="showTrimesterModal = true" type="button" 
                            class="col-span-1 inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2.5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-900/40 font-bold text-xs transition-colors shadow-2xs">
                        <span class="material-icons text-sm text-indigo-600 dark:text-indigo-400">picture_as_pdf</span>
                        <span class="truncate">Cetak TW</span>
                    </button>

                    <!-- Export Excel -->
                    <a href="{{ route('teacher.attendance.export.excel', ['month' => $selectedDate->format('Y-m')]) }}" 
                       class="col-span-1 inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900/40 font-bold text-xs transition-colors shadow-2xs">
                        <span class="material-icons text-sm text-emerald-600 dark:text-emerald-400">table_view</span>
                        <span class="truncate">Excel</span>
                    </a>

                </div>
            </div>
        </div>

        <!-- Mode Tampilan Switcher (Mobile & Tablet) -->
        <div class="flex items-center justify-between gap-3 bg-white dark:bg-slate-900 p-2 sm:p-2.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl">
                <button @click="viewMode = 'matrix'" 
                        :class="viewMode === 'matrix' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-all">
                    <span class="material-icons text-sm">grid_on</span>
                    <span>Tabel Matriks</span>
                </button>
                <button @click="viewMode = 'cards'" 
                        :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-all">
                    <span class="material-icons text-sm">view_agenda</span>
                    <span>Ringkasan Siswa</span>
                </button>
            </div>

            <!-- Petunjuk Geser di Mobile (Hanya tampil saat mode matriks di mobile) -->
            <div x-show="viewMode === 'matrix'" class="text-[11px] text-slate-400 dark:text-slate-500 hidden sm:flex items-center gap-1">
                <span class="material-icons text-xs">swipe</span>
                <span>Klik tanda status untuk mengoreksi absensi</span>
            </div>
        </div>

        <!-- ================= MODE 1: RINGKASAN KARTU SISWA (SANGAT COCOK DI HP) ================= -->
        <div x-show="viewMode === 'cards'" class="space-y-3" style="display: none;">
            @forelse ($students as $student)
                @php
                    $summary = $attendanceSummary[$student->id];
                    $totEffective = $totalEffectiveWorkdays > 0 ? $totalEffectiveWorkdays : 1;
                    $studentPercent = round(($summary['hadir'] / $totEffective) * 100, 0);
                    $badgePercentColor = $studentPercent >= 90 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : ($studentPercent >= 75 ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800');
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-sky-300 dark:hover:border-sky-700 transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-300 font-bold flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-900/40 text-sm">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm leading-tight">
                                    {{ $student->name }}
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    NIS: {{ $student->nis ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- Percentage Badge -->
                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black border {{ $badgePercentColor }}">
                            {{ $studentPercent }}%
                        </span>
                    </div>

                    <!-- 4 Summary Counter Pills -->
                    <div class="grid grid-cols-4 gap-2 mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                        <div class="p-2 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/50 dark:border-emerald-900/30">
                            <div class="text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase">Hadir</div>
                            <div class="text-sm font-black text-emerald-700 dark:text-emerald-300">{{ $summary['hadir'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/50 dark:border-purple-900/30">
                            <div class="text-[10px] font-bold text-purple-800 dark:text-purple-300 uppercase">Sakit</div>
                            <div class="text-sm font-black text-purple-700 dark:text-purple-300">{{ $summary['sakit'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-sky-50/70 dark:bg-sky-950/30 border border-sky-200/50 dark:border-sky-900/30">
                            <div class="text-[10px] font-bold text-sky-800 dark:text-sky-300 uppercase">Izin</div>
                            <div class="text-sm font-black text-sky-700 dark:text-sky-300">{{ $summary['izin'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/50 dark:border-rose-900/30">
                            <div class="text-[10px] font-bold text-rose-800 dark:text-rose-300 uppercase">Alpa</div>
                            <div class="text-sm font-black text-rose-700 dark:text-rose-300">{{ $summary['alpa'] }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800">
                    Tidak ada siswa yang terdaftar di kelas binaan ini.
                </div>
            @endforelse
        </div>

        <!-- ================= MODE 2: TABEL MATRIKS LENGKAP ================= -->
        <div x-show="viewMode === 'matrix'" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            
            <!-- Matrix Header & Legends -->
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sky-500 text-lg">calendar_view_month</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Matriks Kehadiran Harian</h3>
                </div>

                <!-- Legenda Status (Responsive Wrap) -->
                <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap text-[11px] font-bold text-slate-500">
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> H (Hadir)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> T (Terlambat)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> S (Sakit)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-sky-500"></span> I (Izin)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> A (Alpa)</span>
                </div>
            </div>

            <!-- Petunjuk Geser Khusus Mobile -->
            <div class="sm:hidden px-4 py-2 bg-sky-50/60 dark:bg-sky-950/30 border-b border-sky-100/60 dark:border-sky-900/30 flex items-center justify-between text-[11px] text-sky-700 dark:text-sky-300 font-semibold">
                <span class="flex items-center gap-1">
                    <span class="material-icons text-xs animate-bounce">arrow_forward</span>
                    <span>Geser tabel ke kanan untuk melihat tanggal</span>
                </span>
                <span class="text-[10px] text-slate-400">Tap status untuk koreksi</span>
            </div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="min-w-full text-xs border-collapse">
                    <thead class="bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="sticky left-0 bg-slate-50 dark:bg-slate-850 px-3.5 sm:px-5 py-3 text-left text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300 z-10 w-36 min-w-36 max-w-36 sm:w-52 sm:min-w-52 sm:max-w-52 shadow-[1px_0_0_0_rgba(226,232,240,1)] dark:shadow-[1px_0_0_0_rgba(51,65,85,1)]">
                                Nama Siswa
                            </th>
                            
                            @foreach ($period as $date)
                                <th scope="col" class="px-2 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-10 {{ $date->isSunday() ? 'bg-rose-50/50 dark:bg-rose-950/20 text-rose-500' : '' }}">
                                    {{ $date->format('d') }}<br>
                                    <span class="text-[8px] font-normal text-slate-400">{{ $date->translatedFormat('D') }}</span>
                                </th>
                            @endforeach

                            {{-- Kolom Rekapitulasi --}}
                            <th scope="col" class="bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-emerald-800 dark:text-emerald-200 uppercase min-w-10" title="Hadir">H</th>
                            <th scope="col" class="bg-purple-50 dark:bg-purple-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-purple-800 dark:text-purple-200 uppercase min-w-10" title="Sakit">S</th>
                            <th scope="col" class="bg-sky-50 dark:bg-sky-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-sky-800 dark:text-sky-200 uppercase min-w-10" title="Izin">I</th>
                            <th scope="col" class="bg-rose-50 dark:bg-rose-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-rose-800 dark:text-rose-200 uppercase min-w-10" title="Alpa">A</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($students as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-3.5 sm:px-5 py-3 font-bold text-slate-900 dark:text-slate-100 z-10 truncate w-36 min-w-36 max-w-36 sm:w-52 sm:min-w-52 sm:max-w-52 shadow-[1px_0_0_0_rgba(226,232,240,1)] dark:shadow-[1px_0_0_0_rgba(51,65,85,1)]">
                                    <div class="truncate text-xs" title="{{ $student->name }}">{{ $student->name }}</div>
                                </td>

                                @foreach ($period as $date)
                                    @php
                                        $dateString = $date->format('Y-m-d');
                                        $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                                        $attendanceId = $attendanceRecord ? $attendanceRecord->id : '';
                                        $badgeColor = 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500';
                                        $statusText = '-';
                                        
                                        $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($date, $selfStudyDays);

                                        if ($isSelfStudy) {
                                            $badgeColor = 'bg-sky-500 text-white shadow-2xs font-bold';
                                            $statusText = 'BM';
                                        } else {
                                            switch ($status) {
                                                case 'tepat_waktu': $badgeColor = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold'; $statusText = 'H'; break;
                                                case 'terlambat': $badgeColor = 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold'; $statusText = 'T'; break;
                                                case 'izin': $badgeColor = 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 font-bold'; $statusText = 'I'; break;
                                                case 'sakit': $badgeColor = 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold'; $statusText = 'S'; break;
                                                case 'alpa': $badgeColor = 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-bold'; $statusText = 'A'; break;
                                            }
                                        }
                                    @endphp
                                    <td class="px-1.5 sm:px-2 py-2 text-center whitespace-nowrap {{ $date->isSunday() ? 'bg-rose-50/25 dark:bg-rose-950/10' : '' }}">
                                        <button 
                                            @click="openModal('{{ $attendanceId }}', '{{ $student->id }}', '{{ $student->name }}', '{{ $dateString }}', '{{ $status }}')"
                                            class="w-8 h-8 sm:w-7 sm:h-7 inline-flex items-center justify-center text-[11px] rounded-xl transition-transform hover:scale-110 active:scale-90 {{ $badgeColor }}"
                                            title="Klik untuk mengubah catatan absensi">
                                            {{ $statusText }}
                                        </button>
                                    </td>
                                @endforeach
                                
                                {{-- Sel Rekapitulasi --}}
                                @php
                                    $summary = $attendanceSummary[$student->id];
                                @endphp
                                <td class="bg-emerald-50/50 dark:bg-emerald-950/30 px-2 py-3 text-center font-bold text-emerald-800 dark:text-emerald-300">{{ $summary['hadir'] }}</td>
                                <td class="bg-purple-50/50 dark:bg-purple-950/30 px-2 py-3 text-center font-bold text-purple-800 dark:text-purple-300">{{ $summary['sakit'] }}</td>
                                <td class="bg-sky-50/50 dark:bg-sky-950/30 px-2 py-3 text-center font-bold text-sky-800 dark:text-sky-300">{{ $summary['izin'] }}</td>
                                <td class="bg-rose-50/50 dark:bg-rose-950/30 px-2 py-3 text-center font-bold text-rose-800 dark:text-rose-300">{{ $summary['alpa'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $period->count() + 5 }}" class="px-6 py-10 text-center text-xs text-slate-400 italic">
                                    Tidak ada data siswa di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= MODAL KOREKSI KEHADIRAN (TELEPORTED TO BODY) ================= -->
        <template x-teleport="body">
            <div x-show="showModal" 
                 x-cloak
                 style="display: none;" 
                 @keydown.escape.window="showModal = false" 
                 class="fixed inset-0 z-[99999] overflow-y-auto flex items-center justify-center p-4 sm:p-6" 
                 aria-labelledby="modal-title" role="dialog" aria-modal="true">
                
                <!-- Backdrop Overlay -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
                
                <!-- Modal Card -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" 
                     class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transform transition-all p-6 sm:p-7 z-10">
                    
                    <form action="{{ route('teacher.attendance.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="attendance_id" :value="attendanceId">
                        <input type="hidden" name="student_id" :value="studentId">
                        <input type="hidden" name="date" :value="date">
                        
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                                <span class="material-icons text-xl">edit</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-title">
                                    Koreksi Kehadiran Siswa
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Siswa: <strong x-text="studentName" class="text-slate-800 dark:text-slate-200"></strong>
                                </p>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-200/60 dark:border-slate-800 mb-4">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-0.5">Tanggal Presensi:</span>
                            <p class="text-xs font-bold text-slate-900 dark:text-white" 
                               x-text="new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></p>
                        </div>

                        <div class="space-y-2 mb-6">
                            <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Pilih Status Baru
                            </label>
                            <select id="status" name="status" 
                                    class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all">
                                <option value="tepat_waktu" :selected="currentStatus === 'tepat_waktu'">Hadir (Tepat Waktu)</option>
                                <option value="terlambat" :selected="currentStatus === 'terlambat'">Terlambat</option>
                                <option value="izin" :selected="currentStatus === 'izin'">Izin</option>
                                <option value="sakit" :selected="currentStatus === 'sakit'">Sakit</option>
                                <option value="alpa" :selected="currentStatus === 'alpa'">Alpa</option>
                                <option value="hapus" class="text-rose-600 font-bold">-- Kosongkan / Hapus Data --</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <button @click="showModal = false" type="button" 
                                    class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-5 py-2.5 text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 rounded-xl shadow-md shadow-sky-600/20 transition-all active:scale-95">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- ================= MODAL CETAK TRIWULAN (TELEPORTED TO BODY) ================= -->
        <template x-teleport="body">
            <div x-show="showTrimesterModal" 
                 x-cloak
                 style="display: none;" 
                 @keydown.escape.window="showTrimesterModal = false" 
                 class="fixed inset-0 z-[99999] overflow-y-auto flex items-center justify-center p-4 sm:p-6" 
                 role="dialog" aria-modal="true">
                
                <!-- Backdrop Overlay -->
                <div x-show="showTrimesterModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" @click="showTrimesterModal = false"></div>
                
                <!-- Modal Content -->
                <div x-show="showTrimesterModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" 
                     class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transform transition-all p-6 sm:p-7 z-10">
                    
                    <form method="GET" action="{{ route('teacher.attendance.print_trimester') }}" target="_blank" @submit="showTrimesterModal = false">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <span class="material-icons text-xl">picture_as_pdf</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                                    Cetak Rekap Triwulan
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Kelas {{ $class->name }} • Format PDF Eksekutif
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div>
                                <label for="trimester_select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Pilih Periode Triwulan
                                </label>
                                <select name="trimester" id="trimester_select" 
                                        class="w-full text-xs font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-3 focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500">
                                    <option value="1">Triwulan 1 (Januari - Maret)</option>
                                    <option value="2">Triwulan 2 (April - Juni)</option>
                                    <option value="3">Triwulan 3 (Juli - September)</option>
                                    <option value="4">Triwulan 4 (Oktober - Desember)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="year_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Tahun
                                    </label>
                                    <input type="number" name="year" id="year_input" value="{{ date('Y') }}" 
                                           class="w-full text-xs font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-3 focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500" required>
                                </div>
                                <div>
                                    <label for="paper_size_select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Ukuran Kertas
                                    </label>
                                    <select name="paper_size" id="paper_size_select" 
                                            class="w-full text-xs font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-3 focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500">
                                        <option value="a4">A4 Landscape</option>
                                        <option value="folio">Folio / F4 Landscape</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <button @click="showTrimesterModal = false" type="button" 
                                    class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-600/20 transition-all active:scale-95 inline-flex items-center gap-1.5">
                                <span class="material-icons text-sm">print</span>
                                <span>Buka Dokumen PDF</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
