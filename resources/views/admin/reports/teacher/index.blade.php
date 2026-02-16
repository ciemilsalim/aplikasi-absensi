<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Statistik & Rekap Absensi Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filter Bulan & Tahun -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('admin.reports.teacher.index') }}" class="flex gap-4 items-end">
                    <div>
                        <x-input-label for="month" value="Bulan" />
                        <select name="month" id="month" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <x-input-label for="year" value="Tahun" />
                        <select name="year" id="year" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">Filter</x-primary-button>
                        <x-secondary-button type="submit" formaction="{{ route('admin.reports.teacher.print') }}" formtarget="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                            </svg>
                            Cetak PDF
                        </x-secondary-button>
                    </div>
                </form>
            </div>

            <!-- Kartu Statistik Hari Ini -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col items-center">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Total Guru</span>
                    <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $totalTeachers }}</span>
                </div>
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col items-center border-l-4 border-green-500">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Hadir Hari Ini</span>
                    <span class="text-3xl font-bold text-green-600">{{ $presentToday }}</span>
                </div>
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col items-center border-l-4 border-yellow-500">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Terlambat Hari Ini</span>
                    <span class="text-3xl font-bold text-yellow-600">{{ $lateToday }}</span>
                </div>
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col items-center border-l-4 border-red-500">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Tidak Hadir (Alpa/Izin/Sakit)</span>
                    <span class="text-3xl font-bold text-red-600">{{ $absentToday + $leaveToday }}</span>
                </div>
            </div>

            <!-- Grafik -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tren Kehadiran (Bulan Ini)</h3>
                    <canvas id="attendanceChart"></canvas>
                </div>
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Distribusi Kehadiran</h3>
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- Tabel Rekapitulasi -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Rekapitulasi Absensi Guru</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Guru</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIP</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Terlambat</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sakit</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Izin</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alpa</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse ($recap as $teacher)
                                    @php
                                        $totalDays = count($dates); // Approximation or count workdays
                                        $attendancePercentage = $totalDays > 0 ? round(($teacher['hadir'] / $totalDays) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $teacher['name'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $teacher['nip'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-green-600">{{ $teacher['hadir'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-yellow-600">{{ $teacher['terlambat'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-blue-600">{{ $teacher['sakit'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-indigo-600">{{ $teacher['izin'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-red-600">{{ $teacher['alpa'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">{{ $attendancePercentage }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">Belum ada data guru.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Terlambat',
                        data: dataLate,
                        borderColor: 'rgb(234, 179, 8)',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
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
                        'rgb(34, 197, 94)',
                        'rgb(234, 179, 8)',
                        'rgb(59, 130, 246)',
                        'rgb(239, 68, 68)'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
    @endpush
</x-app-layout>
