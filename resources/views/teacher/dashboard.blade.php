<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Wali Kelas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-xl font-semibold">Selamat Datang, {{ $teacher->name }}!</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Anda adalah wali kelas untuk kelas <span class="font-bold text-sky-600 dark:text-sky-400">{{ $class->name }}</span>. Selamat bertugas.</p>
                </div>
            </div>

            <!-- REKAPITULASI HARIAN -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Rekapitulasi Harian - {{ now()->translatedFormat('d F Y') }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-green-100 dark:bg-green-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $onTimeCount }}</p>
                        <p class="text-sm font-medium text-green-800 dark:text-green-300">Tepat Waktu</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $lateCount }}</p>
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">Terlambat</p>
                    </div>
                    <div class="bg-amber-100 dark:bg-amber-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $sickCount }}</p>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Sakit</p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $permitCount }}</p>
                        <p class="text-sm font-medium text-purple-800 dark:text-purple-300">Izin</p>
                    </div>
                    <div class="bg-red-200 dark:bg-red-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-red-700 dark:text-red-400">{{ $alphaCount }}</p>
                        <p class="text-sm font-medium text-red-900 dark:text-red-300">Alpa</p>
                    </div>
                    <div class="bg-gray-100 dark:bg-slate-700 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ $noRecordCount }}</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Tanpa Kabar</p>
                    </div>
                </div>
            </div>

            <!-- GRAFIK TREN KEHADIRAN MINGGUAN -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium">Grafik Tren Kehadiran Kelas (7 Hari Terakhir)</h3>
                    <div class="h-80 mt-4">
                        <canvas id="weeklyAttendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Daftar Siswa -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-6">Kelola Kehadiran Siswa Hari Ini</h3>
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                    @if (session('error'))
                         <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div>
                    @endif
                    
                    <div class="space-y-3">
                        @forelse($studentsInClass as $student)
                            <div class="bg-slate-50 dark:bg-slate-700/50 p-4 rounded-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex items-center w-full sm:w-auto">
                                    <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600">
                                        <svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </span>
                                    <div class="ml-4">
                                        <p class="font-semibold text-slate-800 dark:text-white">{{ $student->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">NIS: {{ $student->nis }}</p>
                                    </div>
                                </div>
                                <div class="w-full sm:w-auto flex-shrink-0">
                                    @php
                                        $attendance = $attendancesToday->get($student->id);
                                    @endphp
                                    @if($attendance)
                                        <div class="flex items-center justify-end">
                                            @if ($attendance->status === 'tepat_waktu')<span class="text-sm font-semibold text-green-600 dark:text-green-400">Hadir (Tepat Waktu)</span>@elseif ($attendance->status === 'terlambat')<span class="text-sm font-semibold text-red-600 dark:text-red-400">Hadir (Terlambat)</span>@elseif ($attendance->status === 'izin')<span class="text-sm font-semibold text-purple-600 dark:text-purple-400">Izin</span>@elseif ($attendance->status === 'sakit')<span class="text-sm font-semibold text-amber-600 dark:text-amber-400">Sakit</span>@elseif ($attendance->status === 'alpa')<span class="text-sm font-semibold text-red-600 dark:text-red-400">Alpa</span>@endif
                                        </div>
                                    @else
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="sakit"><button type="submit" class="px-3 py-1 text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-full dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800 transition">Sakit</button></form>
                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="izin"><button type="submit" class="px-3 py-1 text-xs font-medium text-purple-800 bg-purple-100 hover:bg-purple-200 rounded-full dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800 transition">Izin</button></form>
                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="alpa"><button type="submit" class="px-3 py-1 text-xs font-medium text-red-800 bg-red-100 hover:bg-red-200 rounded-full dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 transition">Alpa</button></form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 italic py-4">Tidak ada siswa di kelas ini.</p>
                        @endforelse
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
            const isDarkMode = document.documentElement.classList.contains('dark');
            const ctx = document.getElementById('weeklyAttendanceChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Kehadiran (%)',
                        data: @json($chartData),
                        borderColor: '#0ea5e9', // Warna sky-500
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4, // Membuat garis lebih melengkung
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
                                callback: (value) => value + '%',
                                color: isDarkMode ? '#94a3b8' : '#64748b',
                            },
                            grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' }
                        },
                        x: {
                             ticks: { color: isDarkMode ? '#94a3b8' : '#64748b' },
                             grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: { label: (context) => ' Kehadiran: ' + context.parsed.y + '%' }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
