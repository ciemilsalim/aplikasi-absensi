<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'pembina_ekskul'])],
                    ['title' => 'Presensi ' . $extracurricular->name, 'url' => route('teacher.extracurricular-attendance.scanner', $extracurricular)],
                    ['title' => 'Rekapitulasi Kehadiran', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Rekapitulasi Presensi Ekstrakurikuler
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                        {{ $extracurricular->name }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        {{ $totalMembers }} Anggota
                    </span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.extracurricular-attendance.scanner', $extracurricular) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm text-slate-500">qr_code_scanner</span>
                    <span>Scanner</span>
                </a>

                <a href="{{ route('teacher.extracurricular-attendance.print', array_merge(['extracurricular' => $extracurricular->id], $requestInputs)) }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/20 active:scale-95 transition-all">
                    <span class="material-icons text-sm">picture_as_pdf</span>
                    <span>Cetak PDF</span>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $totalPossible = $totalMembers * $totalSessions;
        $attendanceRate = $totalPossible > 0 ? round(($totalHadir / $totalPossible) * 100, 1) : 0;
    @endphp

    <div class="space-y-6" x-data="{ searchQuery: '', viewMode: 'matrix' }">
        
        <!-- Filter Bar Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-5">
            <form action="{{ route('teacher.extracurricular-attendance.report', $extracurricular) }}" method="GET" class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1 max-w-xl">
                    <div>
                        <label for="start_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" 
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    </div>
                    <div>
                        <label for="end_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" 
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    </div>
                </div>

                <button type="submit" 
                        class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs shadow-sm transition-all active:scale-95">
                    <span class="material-icons text-sm">filter_alt</span>
                    <span>Terapkan Filter</span>
                </button>
            </form>
        </div>

        <!-- Top 4 KPI Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- KPI 1: Rata-rata Kehadiran -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Kehadiran</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-base">pie_chart</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $attendanceRate }}%
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $attendanceRate)) }}%"></div>
                    </div>
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
                        {{ $totalMembers }} <span class="text-xs font-semibold text-slate-500">Anggota</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $totalSessions }} Sesi Latihan Tercatat
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
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalSakit + $totalIzin }} <span class="text-xs font-semibold text-slate-500">Total</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Sakit: {{ $totalSakit }} • Izin: {{ $totalIzin }}
                    </p>
                </div>
            </div>

            <!-- KPI 4: Alpa -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanpa Keterangan</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-icons text-base">person_off</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $totalAlpa }} <span class="text-xs font-semibold text-slate-500">Alpa</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Ketidakhadiran tanpa izin
                    </p>
                </div>
            </div>
        </div>

        <!-- Matrix Attendance Table Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            
            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-amber-500 text-lg">grid_on</span>
                        Matriks Presensi Anggota
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Rincian kehadiran seluruh anggota pada periode {{ $startDate->translatedFormat('d M Y') }} s/d {{ $endDate->translatedFormat('d M Y') }}
                    </p>
                </div>

                <!-- Instant Search -->
                <div class="relative w-full sm:w-64">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama / NIS siswa..." 
                           class="w-full text-xs py-2 pl-8 pr-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-amber-500 focus:border-amber-500">
                    <span class="material-icons text-slate-400 text-sm absolute left-2.5 top-1/2 -translate-y-1/2">search</span>
                </div>
            </div>

            <!-- Table Matrix Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-850/80 text-slate-700 dark:text-slate-300 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-3 text-center w-12 sticky left-0 bg-slate-50 dark:bg-slate-850 z-10">No</th>
                            <th class="py-3 px-3 min-w-[180px] sticky left-12 bg-slate-50 dark:bg-slate-850 z-10">Nama Siswa</th>
                            <th class="py-3 px-3 text-center min-w-[70px]">Kelas</th>
                            
                            @if(count($dates) > 0)
                                @foreach($dates as $date)
                                    <th class="py-3 px-2 text-center min-w-[40px] border-l border-slate-200/60 dark:border-slate-800">
                                        {{ \Carbon\Carbon::parse($date)->format('d/m') }}
                                    </th>
                                @endforeach
                            @else
                                <th class="py-3 px-4 text-center text-slate-400">Belum Ada Sesi</th>
                            @endif

                            <th class="py-3 px-2 text-center bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border-l border-slate-200 dark:border-slate-800">H</th>
                            <th class="py-3 px-2 text-center bg-amber-50/50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300">S</th>
                            <th class="py-3 px-2 text-center bg-purple-50/50 dark:bg-purple-950/30 text-purple-700 dark:text-purple-300">I</th>
                            <th class="py-3 px-2 text-center bg-rose-50/50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300">A</th>
                            <th class="py-3 px-3 text-center bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($students as $student)
                            @php
                                $stHadir = 0; $stSakit = 0; $stIzin = 0; $stAlpa = 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/40 transition-colors"
                                x-show="searchQuery === '' || '{{ mb_strtolower($student->name) }}'.includes(searchQuery.toLowerCase()) || '{{ $student->nis }}'.includes(searchQuery)">
                                
                                <td class="py-2.5 px-3 text-center text-slate-400 font-bold sticky left-0 bg-white dark:bg-slate-900 z-10">
                                    {{ $loop->iteration }}
                                </td>
                                
                                <td class="py-2.5 px-3 sticky left-12 bg-white dark:bg-slate-900 z-10">
                                    <span class="font-bold text-slate-900 dark:text-white block truncate max-w-[200px]">{{ $student->name }}</span>
                                    <span class="text-[10px] text-slate-400">NIS: {{ $student->nis ?? '-' }}</span>
                                </td>

                                <td class="py-2.5 px-3 text-center text-slate-600 dark:text-slate-300 text-[11px] font-semibold">
                                    {{ $student->schoolClass->name ?? '-' }}
                                </td>

                                @if(count($dates) > 0)
                                    @foreach($dates as $date)
                                        @php
                                            $status = $attendanceData[$student->id][$date] ?? null;
                                            if ($status === 'hadir') $stHadir++;
                                            elseif ($status === 'sakit') $stSakit++;
                                            elseif ($status === 'izin') $stIzin++;
                                            elseif ($status === 'alpa') $stAlpa++;
                                        @endphp
                                        <td class="py-2.5 px-2 text-center border-l border-slate-100 dark:border-slate-800">
                                            @if($status === 'hadir')
                                                <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-black text-[11px] inline-flex items-center justify-center">H</span>
                                            @elseif($status === 'sakit')
                                                <span class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-black text-[11px] inline-flex items-center justify-center">S</span>
                                            @elseif($status === 'izin')
                                                <span class="w-6 h-6 rounded-lg bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-black text-[11px] inline-flex items-center justify-center">I</span>
                                            @elseif($status === 'alpa')
                                                <span class="w-6 h-6 rounded-lg bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-black text-[11px] inline-flex items-center justify-center">A</span>
                                            @else
                                                <span class="text-slate-300 dark:text-slate-600">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @else
                                    <td class="py-2.5 px-4 text-center text-slate-300">-</td>
                                @endif

                                @php
                                    $stPercent = (count($dates) > 0) ? round(($stHadir / count($dates)) * 100) : 0;
                                @endphp

                                <td class="py-2.5 px-2 text-center font-bold text-emerald-600 bg-emerald-50/30 dark:bg-emerald-950/20 border-l border-slate-100 dark:border-slate-800">{{ $stHadir }}</td>
                                <td class="py-2.5 px-2 text-center font-bold text-amber-600 bg-amber-50/30 dark:bg-amber-950/20">{{ $stSakit }}</td>
                                <td class="py-2.5 px-2 text-center font-bold text-purple-600 bg-purple-50/30 dark:bg-purple-950/20">{{ $stIzin }}</td>
                                <td class="py-2.5 px-2 text-center font-bold text-rose-600 bg-rose-50/30 dark:bg-rose-950/20">{{ $stAlpa }}</td>
                                <td class="py-2.5 px-3 text-center font-black text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-850/60">{{ $stPercent }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="py-8 text-center text-slate-400 text-xs">
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
