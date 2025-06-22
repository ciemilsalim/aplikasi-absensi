<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor', 'url' => route('admin.dashboard')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Kehadiran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- KARTU STATISTIK --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Kartu Tepat Waktu -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center">
                    <div class="flex-shrink-0 bg-green-100 dark:bg-green-500/20 rounded-md p-4">
                        <svg class="h-8 w-8 text-green-500 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Siswa Tepat Waktu</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $overallOnTimePercentage }}%</p>
                    </div>
                </div>
                <!-- Kartu Terlambat -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center">
                    <div class="flex-shrink-0 bg-red-100 dark:bg-red-500/20 rounded-md p-4">
                        <svg class="h-8 w-8 text-red-500 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Siswa Terlambat</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $overallLatenessPercentage }}%</p>
                    </div>
                </div>
                <!-- Kartu Izin -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 dark:bg-purple-500/20 rounded-md p-4">
                        <svg class="h-8 w-8 text-purple-500 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08H4.125A2.25 2.25 0 0 0 1.875 6.108v11.785c0 1.24.962 2.231 2.125 2.249H5.125m9.375-4.5H18m-6.375-3.75h.008v.008h-.008v-.008Zm0 3.75h.008v.008h-.008v-.008Zm0 3.75h.008v.008h-.008v-.008Z" /></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Siswa Izin</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $overallIzinPercentage }}%</p>
                    </div>
                </div>
                <!-- Kartu Sakit -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center">
                    <div class="flex-shrink-0 bg-amber-100 dark:bg-amber-500/20 rounded-md p-4">
                        <svg class="h-8 w-8 text-amber-500 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Siswa Sakit</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $overallSakitPercentage }}%</p>
                    </div>
                </div>
            </div>

            {{-- GRAFIK KEHADIRAN PER KELAS --}}
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium">Grafik Persentase Kehadiran per Kelas (Hadir Efektif)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Menampilkan data untuk tanggal: {{ $selectedDate->translatedFormat('l, d F Y') }}</p>
                    <div class="h-80 md:h-96">
                        <canvas id="classAttendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Form Filter & Pencarian -->
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium">Rekap Kehadiran Harian</h3>
                        </div>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2">
                            <x-text-input type="text" name="search" placeholder="Cari nama siswa..." value="{{ request('search') }}" class="w-full sm:w-auto"/>
                            <select name="school_class_id" class="w-full sm:w-auto border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                <option value="">Semua Kelas</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                            <x-text-input type="date" name="tanggal" id="tanggal" value="{{ $selectedDate->format('Y-m-d') }}" />
                            <x-primary-button type="submit">Filter</x-primary-button>
                        </form>
                    </div>
                    
                    <!-- Tabel Kehadiran -->
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                    <th scope="col" class="px-6 py-3">Kelas</th>
                                    <th scope="col" class="px-6 py-3">Jam Masuk</th>
                                    <th scope="col" class="px-6 py-3">Jam Pulang</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ $attendance->student->name ?? 'Siswa Dihapus' }}</th>
                                        <td class="px-6 py-4">{{ $attendance->student->schoolClass->name ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            @if(in_array($attendance->status, ['izin', 'sakit']))<span class="text-gray-400 dark:text-gray-500">-</span>@else<span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">{{ $attendance->attendance_time->format('H:i:s') }}</span>@endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance->checkout_time)<span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">{{ $attendance->checkout_time->format('H:i:s') }}</span>@else<span class="bg-gray-100 text-gray-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-slate-700 dark:text-gray-300">-</span>@endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance->status === 'tepat_waktu')<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Tepat Waktu</span>@elseif ($attendance->status === 'terlambat')<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">Terlambat</span>@elseif ($attendance->status === 'izin')<span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">Izin</span>@elseif ($attendance->status === 'sakit')<span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-amber-900 dark:text-amber-300">Sakit</span>@else<span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-700 dark:text-gray-300">-</span>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700"><td colspan="5" class="px-6 py-4 text-center">Tidak ada data kehadiran untuk filter yang dipilih.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $attendances->appends(request()->query())->links() }}</div>
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
                        backgroundColor: 'rgba(14, 165, 233, 0.6)',
                        borderColor: 'rgba(14, 165, 233, 1)',
                        borderWidth: 1,
                        borderRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100, ticks: { callback: (value) => value + '%', color: isDarkMode ? '#94a3b8' : '#64748b' }, grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' } },
                        x: { ticks: { color: isDarkMode ? '#94a3b8' : '#64748b' }, grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (context) => ' Kehadiran: ' + context.parsed.y + '%' } }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
