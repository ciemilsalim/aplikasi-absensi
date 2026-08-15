<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Laporan', 'url' => route('admin.reports.create')],
                    ['title' => 'Statistik Guru', 'url' => route('admin.reports.teacher.index')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Statistik & Rekapitulasi Absensi Guru
                </h1>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span class="material-icons text-xs text-sky-500">calendar_today</span>
                    <span>Periode: {{ \Carbon\Carbon::create()->month((int)$month)->translatedFormat('F') }} {{ $year }}</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        
        <!-- Filter Controls Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-5">
            <form method="GET" action="{{ route('admin.reports.teacher.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="month" class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Bulan</label>
                    <select name="month" id="month" 
                            class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label for="year" class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Tahun</label>
                    <select name="year" id="year" 
                            class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all active:scale-95">
                        <span class="material-icons text-sm">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    
                    <button type="submit" formaction="{{ route('admin.reports.teacher.print') }}" formtarget="_blank" 
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors">
                        <span class="material-icons text-sm text-slate-500">picture_as_pdf</span>
                        <span>Cetak PDF</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- 4 KPI Summary Cards Hari Ini -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Guru -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Guru Aktif</p>
                    <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $totalTeachers }}</p>
                    <span class="text-[11px] text-slate-400">Terdaftar di sistem</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                    <span class="material-icons text-2xl">badge</span>
                </div>
            </div>

            <!-- 2. Hadir Hari Ini -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-emerald-100 dark:border-emerald-950/40 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Hadir Hari Ini</p>
                    <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $presentToday }}</p>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Tepat waktu & aktif</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                    <span class="material-icons text-2xl">check_circle</span>
                </div>
            </div>

            <!-- 3. Terlambat Hari Ini -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-amber-100 dark:border-amber-950/40 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Terlambat Hari Ini</p>
                    <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $lateToday }}</p>
                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">Masuk lewat batas jam</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center">
                    <span class="material-icons text-2xl">schedule</span>
                </div>
            </div>

            <!-- 4. Tidak Hadir Hari Ini -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-rose-100 dark:border-rose-950/40 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Tidak Hadir (A/I/S)</p>
                    <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $absentToday + $leaveToday }}</p>
                    <span class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold">Izin, sakit & alpa</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center">
                    <span class="material-icons text-2xl">person_off</span>
                </div>
            </div>
        </div>

        <!-- Visual Analytics Graphs -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Tren Kehadiran Line Chart -->
            <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-icons text-sky-500 text-lg">show_chart</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tren Kehadiran Harian Guru</h3>
                </div>
                <div class="relative w-full h-72">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <!-- Distribusi Kehadiran Doughnut Chart -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6 flex flex-col items-center justify-center">
                <div class="flex items-center gap-2 mb-4 self-start">
                    <span class="material-icons text-indigo-500 text-lg">pie_chart</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Distribusi Kehadiran Hari Ini</h3>
                </div>
                <div class="relative w-full aspect-square max-w-[260px] flex items-center justify-center">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Absensi Guru -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-sky-500 text-lg">summarize</span>
                        Rekapitulasi Kehadiran per Guru
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Rincian status kehadiran kumulatif selama bulan terpilih</p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                    <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Nama Guru</th>
                            <th scope="col" class="px-6 py-3.5">NIP</th>
                            <th scope="col" class="px-4 py-3.5 text-center text-emerald-700 dark:text-emerald-400">Hadir</th>
                            <th scope="col" class="px-4 py-3.5 text-center text-amber-700 dark:text-amber-400">Terlambat</th>
                            <th scope="col" class="px-4 py-3.5 text-center text-purple-700 dark:text-purple-400">Sakit</th>
                            <th scope="col" class="px-4 py-3.5 text-center text-sky-700 dark:text-sky-400">Izin</th>
                            <th scope="col" class="px-4 py-3.5 text-center text-rose-700 dark:text-rose-400">Alpa</th>
                            <th scope="col" class="px-6 py-3.5 text-center">% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($recap as $teacher)
                            @php
                                $totalDays = count($dates);
                                $attendancePercentage = $totalDays > 0 ? round(($teacher['hadir'] / $totalDays) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 font-bold flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($teacher['name'], 0, 1)) }}
                                        </div>
                                        <span>{{ $teacher['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                                    {{ $teacher['nip'] ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $teacher['hadir'] }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-amber-600 dark:text-amber-400">
                                    {{ $teacher['terlambat'] }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-purple-600 dark:text-purple-400">
                                    {{ $teacher['sakit'] }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-sky-600 dark:text-sky-400">
                                    {{ $teacher['izin'] }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-rose-600 dark:text-rose-400">
                                    {{ $teacher['alpa'] }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold {{ $attendancePercentage >= 85 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : ($attendancePercentage >= 70 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300') }}">
                                        {{ $attendancePercentage }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-xs text-slate-400 italic">Belum ada data guru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data from Controller
        const dates = @json($dates);
        const dataPresent = @json($dataPresent);
        const dataLate = @json($dataLate);
        const presentToday = {{ $presentToday }};
        const lateToday = {{ $lateToday }};
        const absentToday = {{ $absentToday }};
        const leaveToday = {{ $leaveToday }};

        // Attendance Trend Chart
        const ctxTrend = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Hadir',
                        data: dataPresent,
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: 'Terlambat',
                        data: dataLate,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false } },
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12 } }
                }
            }
        });

        // Distribution Chart
        const ctxDist = document.getElementById('distributionChart').getContext('2d');
        new Chart(ctxDist, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Izin/Sakit', 'Alpa'],
                datasets: [{
                    data: [presentToday, lateToday, leaveToday, absentToday],
                    backgroundColor: [
                        '#0284c7',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ef4444'
                    ],
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>

