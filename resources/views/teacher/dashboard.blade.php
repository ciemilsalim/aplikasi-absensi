<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')]
        ]" />
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Kolom Kiri: Grafik & Daftar Siswa -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- GRAFIK TREN KEHADIRAN MINGGUAN -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="text-lg font-medium">Grafik Tren Kehadiran Kelas (7 Hari Terakhir)</h3>
                            <div class="h-80 mt-4">
                                <canvas id="weeklyAttendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Daftar Siswa untuk Dikelola -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="text-lg font-medium mb-6">Kelola Kehadiran Siswa Hari Ini</h3>
                            <div class="relative overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($studentsInClass as $student)
                                            <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ $student->name }}</th>
                                                <td class="px-6 py-4 text-center">
                                                    @php $attendance = $attendancesToday->get($student->id); @endphp
                                                    @if($attendance)
                                                        @if ($attendance->status === 'tepat_waktu')<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Hadir</span>
                                                        @elseif ($attendance->status === 'terlambat')<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">Terlambat</span>
                                                        @elseif ($attendance->status === 'izin')<span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">Izin</span>
                                                        @elseif ($attendance->status === 'sakit')<span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-amber-900 dark:text-amber-300">Sakit</span>
                                                        @elseif ($attendance->status === 'alpa')<span class="bg-red-200 text-red-900 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-red-800 dark:text-red-200">Alpa</span>
                                                        @elseif ($attendance->status === 'izin_keluar')<span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300">Izin Keluar</span>
                                                        @endif
                                                    @else<span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-600 dark:text-slate-300">Belum Hadir</span>@endif
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    @if(!$attendance)
                                                        <div class="flex items-center justify-center gap-2">
                                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="sakit"><button type="submit" class="px-3 py-1 text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-full">S</button></form>
                                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="izin"><button type="submit" class="px-3 py-1 text-xs font-medium text-purple-800 bg-purple-100 hover:bg-purple-200 rounded-full">I</button></form>
                                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="alpa"><button type="submit" class="px-3 py-1 text-xs font-medium text-red-800 bg-red-100 hover:bg-red-200 rounded-full">A</button></form>
                                                        </div>
                                                    @else<span class="text-xs text-gray-400">-</span>@endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700"><td colspan="3" class="px-6 py-4 text-center">Tidak ada siswa di kelas ini.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Siswa Perlu Perhatian & Siswa Izin Keluar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Siswa Perlu Perhatian -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Perlu Perhatian</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Berdasarkan data 30 hari terakhir.</p>
                        </div>
                        <div class="border-t border-gray-200 dark:border-slate-700">
                            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse($studentsForAttention as $student)
                                <li class="p-4 flex items-center gap-4">
                                    <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600">
                                        <svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-sm text-slate-800 dark:text-white">{{ $student->name }}</p>
                                        <div class="flex gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            @if($student->late_count > 0)<span class="font-medium text-red-600">{{ $student->late_count }}x Terlambat</span>@endif
                                            @if($student->alpha_count > 0)<span class="font-medium text-red-800">{{ $student->alpha_count }}x Alpa</span>@endif
                                        </div>
                                    </div>
                                </li>
                                @empty
                                <li class="p-4 text-center text-sm text-gray-500 italic">
                                    Tidak ada siswa yang memerlukan perhatian khusus saat ini. Kerja bagus!
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <!-- Panel Siswa Izin Keluar -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Sedang Izin Keluar</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar siswa yang keluar pada hari ini dan belum kembali.</p>
                        </div>
                        <div class="border-t border-gray-200 dark:border-slate-700">
                            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse($studentsOnPermit as $permit)
                                <li class="p-4 flex items-start gap-4">
                                    <div class="flex-shrink-0 pt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-yellow-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-sm text-slate-800 dark:text-white">{{ $permit->student->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Keluar pukul: <span class="font-medium">{{ $permit->time_out->format('H:i') }}</span>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 italic">
                                            "{{ $permit->reason }}"
                                        </p>
                                    </div>
                                </li>
                                @empty
                                <li class="p-4 text-center text-sm text-gray-500 italic">
                                    Tidak ada siswa yang sedang izin keluar.
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const ctx = document.getElementById('weeklyAttendanceChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels ?? []),
                    datasets: [{
                        label: 'Kehadiran (%)',
                        data: @json($chartData ?? []),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true, max: 100,
                            ticks: { callback: (value) => value + '%', color: isDarkMode ? '#94a3b8' : '#64748b' },
                            grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' }
                        },
                        x: {
                             ticks: { color: isDarkMode ? '#94a3b8' : '#64748b' },
                             grid: { display: false }
                        }
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
