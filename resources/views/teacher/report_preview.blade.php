<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => isset($cocurricularInfo) && $cocurricularInfo ? 'fasilitator_kokurikuler' : 'guru_mapel'])],
                    ['title' => 'Laporan Mengajar', 'url' => route('teacher.subject.attendance.report')],
                    ['title' => 'Preview Rekap', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Rekap Presensi
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold {{ isset($cocurricularInfo) && $cocurricularInfo ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800' }} shadow-2xs">
                        {{ isset($cocurricularInfo) && $cocurricularInfo ? $cocurricularInfo->title : ($subjectInfo?->name ?? 'Mata Pelajaran') }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        Kelas {{ $classInfo->name }}
                    </span>
                </div>
            </div>

            <!-- Action Bar: Contextual Filter & Secondary Export Button -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <a href="{{ route('teacher.subject.attendance.report', $requestInputs) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors shadow-2xs">
                    <span class="material-icons text-sm text-sky-500">tune</span>
                    <span>Filter: Kelas {{ $classInfo->name }} &bull; {{ $startDate->translatedFormat('d M') }} - {{ $endDate->translatedFormat('d M Y') }}</span>
                </a>
                
                <a href="{{ route('teacher.subject.attendance.print', $requestInputs) }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-2xs transition-all active:scale-95">
                    <span class="material-icons text-sm text-rose-500">picture_as_pdf</span>
                    <span>Export PDF</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{
        viewMode: (new URLSearchParams(window.location.search).get('tab')) || localStorage.getItem('teacher_preview_tab') || 'matrix',
        showModal: false,
        studentId: '',
        studentName: '',
        studentNis: '',
        studentPhoto: '',
        date: '',
        currentStatus: '',
        switchTab(mode) {
            this.viewMode = mode;
            try { localStorage.setItem('teacher_preview_tab', mode); } catch (e) {}
        },
        openModal(id, name, nis, photo, dateVal, status) {
            this.studentId = id;
            this.studentName = name;
            this.studentNis = nis || '-';
            this.studentPhoto = photo || '';
            this.date = dateVal;
            this.currentStatus = status || 'hapus';
            this.showModal = true;
        }
    }" class="space-y-5 sm:space-y-6 pb-32 sm:pb-12">

        <!-- Top 4 KPI Summary Cards (Focal Point: Rata-rata Kehadiran) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- KPI 1: Rata-rata Kehadiran (Focal Point) -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Kehadiran</span>
                    <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-base">pie_chart</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $classAvgPercent }}%
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-sky-500 to-indigo-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $classAvgPercent)) }}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                        {{ $totalHadir }} kehadiran dari {{ count($students) * $totalEffDays }} kapasitas sesi
                    </p>
                </div>
            </div>

            <!-- KPI 2: Siswa & Pertemuan -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Siswa & Sesi</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-base">groups</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ count($students) }} <span class="text-xs font-bold text-slate-500">Siswa</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        <strong class="text-slate-700 dark:text-slate-200">{{ $totalEffDays }}</strong> Pertemuan Efektif
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
                        {{ $totalSakit + $totalIzin }} <span class="text-xs font-bold text-slate-500">kejadian</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Sakit <strong class="text-slate-700 dark:text-slate-200">{{ $totalSakit }}</strong> &bull; Izin <strong class="text-slate-700 dark:text-slate-200">{{ $totalIzin }}</strong>
                    </p>
                </div>
            </div>

            <!-- KPI 4: Alpa & Bolos -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alpa & Bolos</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-icons text-base">person_off</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black {{ ($totalAlpa + $totalBolos) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tracking-tight">
                        {{ $totalAlpa + $totalBolos }} <span class="text-xs font-bold text-slate-500">kejadian</span>
                    </div>
                    <p class="text-[11px] {{ ($totalAlpa + $totalBolos) > 0 ? 'text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400' }} mt-1 font-medium">
                        Alpa <strong class="text-slate-700 dark:text-slate-200">{{ $totalAlpa }}</strong> &bull; Bolos <strong class="text-slate-700 dark:text-slate-200">{{ $totalBolos }}</strong>
                    </p>
                </div>
            </div>

        </div>

        <!-- Mode Tampilan Switcher & Period Info Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-2xl self-start sm:self-auto">
                <button @click="switchTab('cards')" 
                        :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium'"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all">
                    <span class="material-icons text-sm">view_agenda</span>
                    <span>Ringkasan</span>
                </button>
                <button @click="switchTab('matrix')" 
                        :class="viewMode === 'matrix' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium'"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all">
                    <span class="material-icons text-sm">grid_on</span>
                    <span>Matriks</span>
                </button>
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-icons text-sky-500 text-sm">date_range</span>
                <span>Periode: <strong class="text-slate-700 dark:text-slate-300">{{ $startDate->isoFormat('D MMMM Y') }} &ndash; {{ $endDate->isoFormat('D MMMM Y') }}</strong></span>
            </div>
        </div>

        <!-- ================= MODE 1: RINGKASAN KARTU SISWA (DEFAULT MOBILE VIEW) ================= -->
        <div x-show="viewMode === 'cards'" class="space-y-3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            @forelse ($students as $student)
                @php
                    $summary = $attendanceSummary[$student->id] ?? ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0, 'persen' => 0];
                    $studentPercent = $summary['persen'];
                    $badgePercentColor = $studentPercent >= 90 
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' 
                        : ($studentPercent >= 75 
                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800' 
                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800');
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-sky-300 dark:hover:border-sky-700 transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-300 font-bold flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-900/40 text-sm overflow-hidden">
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
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    NIS: {{ $student->nis ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- Individual Attendance Percentage Badge -->
                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black border shrink-0 {{ $badgePercentColor }}">
                            {{ $studentPercent }}%
                        </span>
                    </div>

                    <!-- 5 Summary Counter Pills (Hadir, Sakit, Izin, Alpa, Bolos) -->
                    <div class="grid grid-cols-5 gap-1.5 sm:gap-2 mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                        <div class="p-2 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/50 dark:border-emerald-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase">Hadir</div>
                            <div class="text-xs sm:text-sm font-black text-emerald-700 dark:text-emerald-300">{{ $summary['hadir'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/50 dark:border-amber-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase">Sakit</div>
                            <div class="text-xs sm:text-sm font-black text-amber-700 dark:text-amber-300">{{ $summary['sakit'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/50 dark:border-purple-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-purple-800 dark:text-purple-300 uppercase">Izin</div>
                            <div class="text-xs sm:text-sm font-black text-purple-700 dark:text-purple-300">{{ $summary['izin'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/50 dark:border-rose-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-rose-800 dark:text-rose-300 uppercase">Alpa</div>
                            <div class="text-xs sm:text-sm font-black text-rose-700 dark:text-rose-300">{{ $summary['alpa'] }}</div>
                        </div>
                        <div class="p-2 rounded-2xl bg-orange-50/70 dark:bg-orange-950/30 border border-orange-200/50 dark:border-orange-900/30">
                            <div class="text-[9px] sm:text-[10px] font-bold text-orange-800 dark:text-orange-300 uppercase">Bolos</div>
                            <div class="text-xs sm:text-sm font-black text-orange-700 dark:text-orange-300">{{ $summary['bolos'] }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800">
                    Tidak ada data siswa di kelas ini.
                </div>
            @endforelse
        </div>

        <!-- ================= MODE 2: TABEL MATRIKS PRESENSI DENGAN HORIZONTAL SCROLL AFFORDANCE ================= -->
        <div x-show="viewMode === 'matrix'" 
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 overflow-hidden" 
             style="display: none;">
            
            <!-- Compact Matrix Header & Status Legend -->
            <div class="p-3.5 sm:p-4 bg-slate-50/80 dark:bg-slate-850/60 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sky-500 text-base">calendar_view_month</span>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                        Matriks Presensi
                    </h3>
                </div>

                <!-- Compact Legend Pills -->
                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">H Hadir</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800">S Sakit</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800">I Izin</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800">A Alpa</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-orange-50 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 border border-orange-200/60 dark:border-orange-800">B Bolos</span>
                </div>
            </div>

            <!-- Horizontal Scroll Affordance Hint (Mobile only) -->
            <div class="sm:hidden px-3.5 py-1.5 bg-sky-50/50 dark:bg-sky-950/20 border-b border-sky-100/50 dark:border-sky-900/20 flex items-center justify-between text-[10px] text-sky-700 dark:text-sky-300 font-semibold">
                <span class="flex items-center gap-1">
                    <span class="material-icons text-xs">swipe</span>
                    <span>Geser tabel ke kanan untuk melihat tanggal berikutnya</span>
                </span>
                <span class="text-[9px] text-slate-400">Ketuk sel untuk koreksi</span>
            </div>

            <!-- Attendance Matrix Table with Sticky Column & Smooth Horizontal Scroll -->
            <div class="relative overflow-x-auto overflow-y-hidden scroll-smooth">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300 border-collapse">
                    <thead class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-850 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <!-- Sticky First Column: Siswa -->
                            <th scope="col" class="sticky left-0 bg-slate-50 dark:bg-slate-850 px-3.5 sm:px-4 py-3 z-20 w-36 min-w-36 max-w-36 sm:w-52 sm:min-w-52 sm:max-w-52 shadow-[3px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[3px_0_10px_-2px_rgba(0,0,0,0.4)] border-r border-slate-200/60 dark:border-slate-750">
                                Siswa
                            </th>
                            
                            <!-- Date Columns with Numerics & Day Names -->
                            @if(isset($period))
                                @foreach ($period as $date)
                                    <th scope="col" class="px-2 py-2.5 text-center min-w-10 sm:min-w-12 border-r border-slate-100 dark:border-slate-800">
                                        <div class="font-extrabold text-[11px] text-slate-800 dark:text-slate-200">{{ $date->format('d') }}</div>
                                        <div class="text-[8px] text-slate-400 font-normal uppercase mt-0.5">{{ $date->translatedFormat('D') }}</div>
                                    </th>
                                @endforeach
                            @endif

                            {{-- Clean Neutral Summary Columns --}}
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8 border-l border-slate-200/60 dark:border-slate-700" title="Hadir">H</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Sakit">S</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Izin">I</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Alpa">A</th>
                            <th scope="col" class="bg-slate-100/70 dark:bg-slate-800/80 px-2 py-3 text-center text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase min-w-8" title="Bolos">B</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($students as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                <!-- Sticky Student Name Cell -->
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-3.5 sm:px-4 py-2.5 font-bold text-slate-900 dark:text-white z-10 w-36 min-w-36 max-w-36 sm:w-52 sm:min-w-52 sm:max-w-52 shadow-[3px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[3px_0_10px_-2px_rgba(0,0,0,0.4)] border-r border-slate-200/60 dark:border-slate-750">
                                    <div class="truncate text-xs leading-tight" title="{{ $student->name }}">
                                        {{ $student->name }}
                                    </div>
                                    <div class="text-[9px] text-slate-400 font-normal truncate mt-0.5">
                                        NIS: {{ $student->nis ?? '-' }}
                                    </div>
                                </td>

                                <!-- Attendance Status Badges per Date -->
                                @if(isset($period))
                                    @foreach ($period as $date)
                                        @php
                                            $dateString = $date->format('Y-m-d');
                                            $status = $attendanceData[$student->id][$dateString] ?? null;
                                            
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
                                                case 'bolos': 
                                                    $badgeClass = 'bg-orange-500 text-white font-bold shadow-xs'; 
                                                    $statusText = 'B'; 
                                                    break;
                                            }
                                        @endphp
                                        <td class="px-1 py-2 text-center whitespace-nowrap border-r border-slate-100 dark:border-slate-800">
                                            <button 
                                                @click="openModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ $student->nis }}', '{{ $student->photo_url }}', '{{ $dateString }}', '{{ $status }}')"
                                                class="w-7 h-7 sm:w-7 sm:h-7 mx-auto inline-flex items-center justify-center font-bold text-[10px] rounded-lg transition-transform transform hover:scale-110 active:scale-90 cursor-pointer {{ $badgeClass }}"
                                                title="Klik untuk koreksi presensi tanggal {{ $date->translatedFormat('d M Y') }}"
                                            >
                                                {{ $statusText }}
                                            </button>
                                        </td>
                                    @endforeach
                                @endif

                                {{-- Neutral Summary Columns --}}
                                @php
                                    $summary = $attendanceSummary[$student->id] ?? ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
                                @endphp
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200 border-l border-slate-200/60 dark:border-slate-700">{{ $summary['hadir'] }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $summary['sakit'] }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $summary['izin'] }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $summary['alpa'] }}</td>
                                <td class="bg-slate-50/60 dark:bg-slate-850/40 px-2 py-2.5 text-center font-bold text-slate-800 dark:text-slate-200">{{ $summary['bolos'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($period) ? $period->count() + 6 : 6 }}" class="px-6 py-8 text-center text-xs text-slate-400 italic">
                                    Tidak ada data siswa di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= MODAL KOREKSI PRESENSI (MODERN BOTTOM SHEET / DIALOG) ================= -->
        <template x-teleport="body">
            <div x-show="showModal" 
                 x-cloak
                 style="display: none;" 
                 @keydown.escape.window="showModal = false" 
                 class="fixed inset-0 z-[99999] overflow-y-auto flex items-end sm:items-center justify-center p-0 sm:p-4" 
                 aria-labelledby="modal-title" role="dialog" aria-modal="true">
                
                <!-- Backdrop Overlay -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" 
                     @click="showModal = false"></div>

                <!-- Modal Dialog / Bottom Sheet Box -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" 
                     class="relative bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl text-left overflow-hidden shadow-2xl border border-slate-200/80 dark:border-slate-800 w-full max-w-md p-5 sm:p-6 z-10 max-h-[90vh] overflow-y-auto">
                    
                    <!-- Mobile Handle Pill -->
                    <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-700 mx-auto mb-4 sm:hidden"></div>

                    <form action="{{ route('teacher.subject.attendance.update_report') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_id" :value="studentId">
                        <input type="hidden" name="date" :value="date">
                        <input type="hidden" name="school_class_id" value="{{ $classInfo->id }}">
                        @if(isset($cocurricularInfo) && $cocurricularInfo)
                            <input type="hidden" name="cocurricular_id" value="{{ $cocurricularInfo->id }}">
                        @elseif(isset($subjectInfo) && $subjectInfo)
                            <input type="hidden" name="subject_id" value="{{ $subjectInfo->id }}">
                        @endif
                        
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                                <span class="material-icons text-xl">edit_calendar</span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight" id="modal-title">
                                    Koreksi Presensi
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                    <span x-text="studentName" class="font-bold text-slate-800 dark:text-slate-200"></span> &bull; <span x-text="'NIS: ' + studentNis"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Date & Subject Info Card -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-200/60 dark:border-slate-800 mb-4 space-y-1">
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="material-icons text-xs text-sky-500">event</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200" 
                                      x-text="new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="material-icons text-xs text-indigo-500">class</span>
                                <span>{{ isset($cocurricularInfo) && $cocurricularInfo ? $cocurricularInfo->title : ($subjectInfo?->name ?? 'Mata Pelajaran') }} &bull; Kelas {{ $classInfo->name }}</span>
                            </div>
                        </div>

                        <!-- Status Options Grid (1-Click Selector) -->
                        <div class="space-y-2 mb-5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Pilih Status Presensi:
                            </label>
                            
                            <div class="grid grid-cols-3 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="hadir" :checked="currentStatus === 'hadir'" class="sr-only peer">
                                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-950/60 dark:peer-checked:text-emerald-300 transition-all">
                                        <div class="w-6 h-6 rounded-md bg-emerald-500 text-white font-bold text-xs flex items-center justify-center mx-auto mb-1">H</div>
                                        <span class="text-[11px] font-bold block">Hadir</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="sakit" :checked="currentStatus === 'sakit'" class="sr-only peer">
                                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-amber-50 peer-checked:border-amber-500 peer-checked:text-amber-700 dark:peer-checked:bg-amber-950/60 dark:peer-checked:text-amber-300 transition-all">
                                        <div class="w-6 h-6 rounded-md bg-amber-500 text-white font-bold text-xs flex items-center justify-center mx-auto mb-1">S</div>
                                        <span class="text-[11px] font-bold block">Sakit</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="izin" :checked="currentStatus === 'izin'" class="sr-only peer">
                                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 dark:peer-checked:bg-purple-950/60 dark:peer-checked:text-purple-300 transition-all">
                                        <div class="w-6 h-6 rounded-md bg-purple-500 text-white font-bold text-xs flex items-center justify-center mx-auto mb-1">I</div>
                                        <span class="text-[11px] font-bold block">Izin</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="alpa" :checked="currentStatus === 'alpa'" class="sr-only peer">
                                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 dark:peer-checked:bg-rose-950/60 dark:peer-checked:text-rose-300 transition-all">
                                        <div class="w-6 h-6 rounded-md bg-rose-500 text-white font-bold text-xs flex items-center justify-center mx-auto mb-1">A</div>
                                        <span class="text-[11px] font-bold block">Alpa</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="bolos" :checked="currentStatus === 'bolos'" class="sr-only peer">
                                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-orange-50 peer-checked:border-orange-500 peer-checked:text-orange-700 dark:peer-checked:bg-orange-950/60 dark:peer-checked:text-orange-300 transition-all">
                                        <div class="w-6 h-6 rounded-md bg-orange-500 text-white font-bold text-xs flex items-center justify-center mx-auto mb-1">B</div>
                                        <span class="text-[11px] font-bold block">Bolos</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="hapus" :checked="currentStatus === 'hapus'" class="sr-only peer">
                                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-slate-100 peer-checked:border-slate-400 peer-checked:text-slate-700 dark:peer-checked:bg-slate-800 dark:peer-checked:text-slate-300 transition-all">
                                        <div class="w-6 h-6 rounded-md bg-slate-400 text-white font-bold text-xs flex items-center justify-center mx-auto mb-1">&ndash;</div>
                                        <span class="text-[11px] font-bold block">Hapus</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
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
    </div>
</x-app-layout>
