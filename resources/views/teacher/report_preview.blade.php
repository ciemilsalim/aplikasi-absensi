<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Laporan Mengajar', 'url' => route('teacher.subject.attendance.report')],
                    ['title' => 'Preview Rekap', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Preview Rekap Presensi
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 shadow-2xs">
                        {{ $subjectInfo->name }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                        Kelas {{ $classInfo->name }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.subject.attendance.report') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm text-slate-500">tune</span>
                    <span>Ubah Filter</span>
                </a>
                
                <a href="{{ route('teacher.subject.attendance.print', $requestInputs) }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all active:scale-95">
                    <span class="material-icons text-sm">picture_as_pdf</span>
                    <span>Cetak PDF</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{
        viewMode: 'matrix',
        showModal: false,
        studentId: '',
        studentName: '',
        date: '',
        currentStatus: '',
        openModal(studentId, studentName, date, status) {
            this.studentId = studentId;
            this.studentName = studentName;
            this.date = date;
            this.currentStatus = status || 'hapus';
            this.showModal = true;
        }
    }" class="space-y-5 sm:space-y-6">

        <!-- Top 4 KPI Summary Cards (2x2 di Mobile, 4-grid di Desktop) -->
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
                        {{ count($students) }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $totalEffDays }} Pertemuan Sesuai Jadwal
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
                        {{ $totalSakit + $totalIzin }} <span class="text-xs font-semibold text-slate-500">Kasus</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        S: {{ $totalSakit }} • I: {{ $totalIzin }}
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
                        {{ $totalAlpa + $totalBolos }} <span class="text-xs font-semibold text-slate-500">Kasus</span>
                    </div>
                    <p class="text-[11px] {{ ($totalAlpa + $totalBolos) > 0 ? 'text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400' }} mt-0.5">
                        A: {{ $totalAlpa }} • B: {{ $totalBolos }}
                    </p>
                </div>
            </div>

        </div>

        <!-- Mode Tampilan Switcher & Period Info Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl self-start sm:self-auto">
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

            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="material-icons text-sky-500 text-sm">calendar_month</span>
                <span>Periode: <strong class="text-slate-700 dark:text-slate-300">{{ $startDate->isoFormat('D MMMM Y') }} - {{ $endDate->isoFormat('D MMMM Y') }}</strong></span>
            </div>
        </div>

        <!-- ================= MODE 1: RINGKASAN KARTU SISWA (SANGAT NYAMAN DI HP) ================= -->
        <div x-show="viewMode === 'cards'" class="space-y-3" style="display: none;">
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
                            <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-300 font-bold flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-900/40 text-sm">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
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

                    <!-- 5 Summary Counter Pills (H, S, I, A, B) -->
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

        <!-- ================= MODE 2: TABEL MATRIKS LENGKAP ================= -->
        <div x-show="viewMode === 'matrix'" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            
            <!-- Summary Header & Status Legend -->
            <div class="p-4 sm:p-5 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="material-icons text-sky-500 text-lg">calendar_view_month</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Matriks Presensi Pembelajaran
                    </h3>
                </div>

                <!-- Status Legend -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-[10px] font-bold">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">H: Hadir</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">S: Sakit</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300">I: Izin</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">A: Alpa</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300">B: Bolos</span>
                </div>
            </div>

            <!-- Petunjuk Geser Khusus Mobile -->
            <div class="sm:hidden px-4 py-2 bg-sky-50/60 dark:bg-sky-950/30 border-b border-sky-100/60 dark:border-sky-900/30 flex items-center justify-between text-[11px] text-sky-700 dark:text-sky-300 font-semibold">
                <span class="flex items-center gap-1">
                    <span class="material-icons text-xs animate-bounce">arrow_forward</span>
                    <span>Geser tabel ke kanan untuk melihat seluruh tanggal</span>
                </span>
                <span class="text-[10px] text-slate-400">Tap status untuk ubah</span>
            </div>

            <!-- Attendance Matrix Table -->
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300 border-collapse">
                    <thead class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-850 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="sticky left-0 bg-slate-50 dark:bg-slate-850 px-3.5 sm:px-5 py-3 z-10 w-36 min-w-36 max-w-36 sm:w-52 sm:min-w-52 sm:max-w-52 shadow-[1px_0_0_0_rgba(226,232,240,1)] dark:shadow-[1px_0_0_0_rgba(51,65,85,1)]">
                                Nama Siswa
                            </th>
                            @if(isset($period))
                                @foreach ($period as $date)
                                    <th scope="col" class="px-2 py-2.5 text-center min-w-10">
                                        <div class="font-extrabold text-[11px]">{{ $date->format('d') }}</div>
                                        <div class="text-[8px] text-slate-400 font-normal">{{ $date->translatedFormat('D') }}</div>
                                    </th>
                                @endforeach
                            @endif

                            {{-- Kolom Rekapitulasi --}}
                            <th scope="col" class="bg-emerald-50 dark:bg-emerald-950/60 px-2 py-3 text-center text-[10px] font-extrabold text-emerald-800 dark:text-emerald-200 uppercase min-w-9" title="Hadir">H</th>
                            <th scope="col" class="bg-amber-50 dark:bg-amber-950/60 px-2 py-3 text-center text-[10px] font-extrabold text-amber-800 dark:text-amber-200 uppercase min-w-9" title="Sakit">S</th>
                            <th scope="col" class="bg-purple-50 dark:bg-purple-950/60 px-2 py-3 text-center text-[10px] font-extrabold text-purple-800 dark:text-purple-200 uppercase min-w-9" title="Izin">I</th>
                            <th scope="col" class="bg-rose-50 dark:bg-rose-950/60 px-2 py-3 text-center text-[10px] font-extrabold text-rose-800 dark:text-rose-200 uppercase min-w-9" title="Alpa">A</th>
                            <th scope="col" class="bg-orange-50 dark:bg-orange-950/60 px-2 py-3 text-center text-[10px] font-extrabold text-orange-800 dark:text-orange-200 uppercase min-w-9" title="Bolos">B</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($students as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-3.5 sm:px-5 py-3 font-bold text-slate-900 dark:text-white z-10 w-36 min-w-36 max-w-36 sm:w-52 sm:min-w-52 sm:max-w-52 shadow-[1px_0_0_0_rgba(226,232,240,1)] dark:shadow-[1px_0_0_0_rgba(51,65,85,1)]">
                                    <div class="truncate text-xs" title="{{ $student->name }}">
                                        {{ $student->name }}
                                    </div>
                                </td>
                                @if(isset($period))
                                    @foreach ($period as $date)
                                        @php
                                            $dateString = $date->format('Y-m-d');
                                            $status = $attendanceData[$student->id][$dateString] ?? null;
                                            
                                            $badgeClass = 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500';
                                            $statusText = '-';

                                            switch ($status) {
                                                case 'hadir': 
                                                    $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 font-bold'; 
                                                    $statusText = 'H'; 
                                                    break;
                                                case 'sakit': 
                                                    $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 font-bold'; 
                                                    $statusText = 'S'; 
                                                    break;
                                                case 'izin': 
                                                    $badgeClass = 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 font-bold'; 
                                                    $statusText = 'I'; 
                                                    break;
                                                case 'alpa': 
                                                    $badgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 font-bold'; 
                                                    $statusText = 'A'; 
                                                    break;
                                                case 'bolos': 
                                                    $badgeClass = 'bg-orange-100 text-orange-800 dark:bg-orange-950/70 dark:text-orange-300 font-bold'; 
                                                    $statusText = 'B'; 
                                                    break;
                                            }
                                        @endphp
                                        <td class="px-1 sm:px-1.5 py-2 text-center whitespace-nowrap">
                                            <button 
                                                @click="openModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ $dateString }}', '{{ $status }}')"
                                                class="w-8 h-8 sm:w-7 sm:h-7 mx-auto inline-flex items-center justify-center font-bold text-[11px] rounded-xl transition-transform transform hover:scale-110 active:scale-90 cursor-pointer {{ $badgeClass }}"
                                                title="Klik untuk ubah status presensi"
                                            >
                                                {{ $statusText }}
                                            </button>
                                        </td>
                                    @endforeach
                                @endif

                                {{-- Sel Rekapitulasi Counter --}}
                                @php
                                    $summary = $attendanceSummary[$student->id] ?? ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
                                @endphp
                                <td class="bg-emerald-50/50 dark:bg-emerald-950/30 px-2 py-3 text-center font-bold text-emerald-800 dark:text-emerald-300">{{ $summary['hadir'] }}</td>
                                <td class="bg-amber-50/50 dark:bg-amber-950/30 px-2 py-3 text-center font-bold text-amber-800 dark:text-amber-300">{{ $summary['sakit'] }}</td>
                                <td class="bg-purple-50/50 dark:bg-purple-950/30 px-2 py-3 text-center font-bold text-purple-800 dark:text-purple-300">{{ $summary['izin'] }}</td>
                                <td class="bg-rose-50/50 dark:bg-rose-950/30 px-2 py-3 text-center font-bold text-rose-800 dark:text-rose-300">{{ $summary['alpa'] }}</td>
                                <td class="bg-orange-50/50 dark:bg-orange-950/30 px-2 py-3 text-center font-bold text-orange-800 dark:text-orange-300">{{ $summary['bolos'] }}</td>
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
                     class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" 
                     @click="showModal = false"></div>

                <!-- Modal Dialog Box -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" 
                     class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl border border-slate-200/80 dark:border-slate-800 w-full max-w-md p-6 sm:p-7 z-10">
                    
                    <form action="{{ route('teacher.subject.attendance.update_report') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_id" :value="studentId">
                        <input type="hidden" name="date" :value="date">
                        <input type="hidden" name="school_class_id" value="{{ $classInfo->id }}">
                        <input type="hidden" name="subject_id" value="{{ $subjectInfo->id }}">
                        
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                                <span class="material-icons text-xl">edit_calendar</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-title">
                                    Koreksi Presensi Mapel
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Siswa: <strong x-text="studentName" class="text-slate-800 dark:text-slate-200"></strong>
                                </p>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-200/60 dark:border-slate-800 mb-4">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-0.5">Tanggal Pertemuan:</span>
                            <p class="text-xs font-bold text-slate-900 dark:text-white" 
                               x-text="new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></p>
                        </div>

                        <div class="space-y-2 mb-6">
                            <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Pilih Status Baru
                            </label>
                            <select id="status" name="status" 
                                    class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all">
                                <option value="hadir" :selected="currentStatus === 'hadir'">Hadir (H)</option>
                                <option value="sakit" :selected="currentStatus === 'sakit'">Sakit (S)</option>
                                <option value="izin" :selected="currentStatus === 'izin'">Izin (I)</option>
                                <option value="alpa" :selected="currentStatus === 'alpa'">Alpa (A)</option>
                                <option value="bolos" :selected="currentStatus === 'bolos'">Bolos (B)</option>
                                <option value="hapus" class="text-rose-600 font-bold">-- Kosongkan / Hapus Record --</option>
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
    </div>
</x-app-layout>
