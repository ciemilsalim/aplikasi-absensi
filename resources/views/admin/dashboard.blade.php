@php
// Definisikan data untuk breadcrumb halaman ini
$breadcrumbs = [
    ['title' => 'Dasbor', 'url' => route('admin.dashboard')]
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="$breadcrumbs" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Kehadiran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- KARTU STATISTIK --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- Kartu Siswa Tepat Waktu (BARU) -->
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
                
                <!-- Kartu Keterlambatan Siswa -->
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

                <!-- Kartu Kehadiran Per Kelas -->
                @foreach($classAttendanceStats as $stat)
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Kehadiran {{ $stat->name }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stat->percentage }}%</p>
                        </div>
                        <div class="flex-shrink-0 bg-sky-100 dark:bg-sky-500/20 rounded-md p-3">
                             <svg class="h-6 w-6 text-sky-500 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">{{ $stat->ratio }}</p>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-slate-700">
                            <div class="bg-sky-600 h-2 rounded-full" style="width: {{ $stat->percentage }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
{{-- GRAFIK KEHADIRAN PER KELAS (BARU) --}}
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium">Grafik Persentase Kehadiran per Kelas</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Menampilkan data untuk tanggal: {{ $selectedDate->translatedFormat('l, d F Y') }}</p>
                    <div class="h-80 md:h-96">
                        <canvas id="classAttendanceChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- TABEL KEHADIRAN --}}
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Form Filter & Pencarian -->
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium">Rekap Kehadiran</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Menampilkan data untuk tanggal: {{ $selectedDate->translatedFormat('l, d F Y') }}</p>
                        </div>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                            {{-- Dropdown Filter Kelas BARU --}}
                            <select name="school_class_id" class="w-full sm:w-auto border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                <option value="">Semua Kelas</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-text-input type="text" name="search" placeholder="Cari nama siswa..." value="{{ request('search') }}" class="w-48"/>
                            <x-text-input type="date" name="tanggal" id="tanggal" value="{{ $selectedDate->format('Y-m-d') }}" />
                            <x-primary-button type="submit">Cari</x-primary-button>
                        </form>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                    <th scope="col" class="px-6 py-3">Kelas</th> <!-- KOLOM BARU -->
                                    <th scope="col" class="px-6 py-3">Jam Masuk</th>
                                    <th scope="col" class="px-6 py-3">Jam Pulang</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $attendance->student->name ?? 'Siswa Dihapus' }}
                                        </th>
                                        <td class="px-6 py-4">
                                            {{-- Menampilkan nama kelas siswa --}}
                                            {{ $attendance->student->schoolClass->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                                {{ $attendance->attendance_time->format('H:i:s') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance->checkout_time)
                                                <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                                    {{ $attendance->checkout_time->format('H:i:s') }}
                                                </span>
                                            @else
                                                <span class="bg-gray-100 text-gray-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-slate-700 dark:text-gray-300">
                                                    -
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance->status === 'tepat_waktu')
                                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Tepat Waktu</span>
                                            @elseif ($attendance->status === 'terlambat')
                                                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">Terlambat</span>
                                            @else
                                                <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-700 dark:text-gray-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                        <td colspan="5" class="px-6 py-4 text-center">
                                            Tidak ada data kehadiran untuk tanggal dan pencarian yang dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $attendances->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

     @push('scripts')
    {{-- Memuat library Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ambil data statistik dari variabel PHP
            const classData = @json($classAttendanceStats);
            const isDarkMode = document.documentElement.classList.contains('dark');

            // Siapkan data untuk grafik
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
                        backgroundColor: 'rgba(14, 165, 233, 0.6)', // sky-500 dengan transparansi
                        borderColor: 'rgba(14, 165, 233, 1)', // sky-500 solid
                        borderWidth: 1,
                        borderRadius: 5,
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
                                callback: function(value) {
                                    return value + '%';
                                },
                                color: isDarkMode ? '#94a3b8' : '#64748b', // Warna teks sumbu Y
                            },
                            grid: {
                                color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                            }
                        },
                        x: {
                             ticks: {
                                color: isDarkMode ? '#94a3b8' : '#64748b', // Warna teks sumbu X
                            },
                             grid: {
                                display: false, // Sembunyikan garis grid vertikal
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false // Sembunyikan legenda karena sudah jelas dari judul
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' Kehadiran: ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
