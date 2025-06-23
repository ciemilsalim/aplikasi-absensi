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
                    <!-- Tepat Waktu -->
                    <div class="bg-green-100 dark:bg-green-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $onTimeCount }}</p>
                        <p class="text-sm font-medium text-green-800 dark:text-green-300">Tepat Waktu</p>
                    </div>
                    <!-- Terlambat -->
                    <div class="bg-red-100 dark:bg-red-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $lateCount }}</p>
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">Terlambat</p>
                    </div>
                    <!-- Sakit -->
                    <div class="bg-amber-100 dark:bg-amber-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $sickCount }}</p>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Sakit</p>
                    </div>
                    <!-- Izin -->
                    <div class="bg-purple-100 dark:bg-purple-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $permitCount }}</p>
                        <p class="text-sm font-medium text-purple-800 dark:text-purple-300">Izin</p>
                    </div>
                    <!-- Alpa -->
                    <div class="bg-red-200 dark:bg-red-900/50 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-red-700 dark:text-red-400">{{ $alphaCount }}</p>
                        <p class="text-sm font-medium text-red-900 dark:text-red-300">Alpa</p>
                    </div>
                     <!-- Belum Ada Keterangan -->
                    <div class="bg-gray-100 dark:bg-slate-700 p-4 rounded-lg text-center">
                        <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ $noRecordCount }}</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Tanpa Kabar</p>
                    </div>
                </div>
            </div>

            <!-- PERBAIKAN: Daftar Siswa diubah menjadi Tabel -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-medium">Kelola Kehadiran Siswa Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tandai status untuk siswa yang belum memiliki keterangan.</p>
                        </div>
                    </div>
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                    @if (session('error'))
                         <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div>
                    @endif
                    
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                    <th scope="col" class="px-6 py-3">NIS</th>
                                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentsInClass as $student)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $student->name }}
                                        </th>
                                        <td class="px-6 py-4">{{ $student->nis }}</td>
                                        <td class="px-6 py-4 text-center">
                                            @php $attendance = $attendancesToday->get($student->id); @endphp
                                            @if($attendance)
                                                @if ($attendance->status === 'tepat_waktu')<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Tepat Waktu</span>
                                                @elseif ($attendance->status === 'terlambat')<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Terlambat</span>
                                                @elseif ($attendance->status === 'izin')<span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Izin</span>
                                                @elseif ($attendance->status === 'sakit')<span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Sakit</span>
                                                @elseif ($attendance->status === 'alpa')<span class="bg-red-200 text-red-900 text-xs font-semibold px-2.5 py-0.5 rounded-full">Alpa</span>
                                                @endif
                                            @else
                                                <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Belum Hadir</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if(!$attendance)
                                                <div class="flex items-center justify-center gap-2">
                                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="sakit"><button type="submit" class="px-3 py-1 text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-full">Sakit</button></form>
                                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="izin"><button type="submit" class="px-3 py-1 text-xs font-medium text-purple-800 bg-purple-100 hover:bg-purple-200 rounded-full">Izin</button></form>
                                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="alpa"><button type="submit" class="px-3 py-1 text-xs font-medium text-red-800 bg-red-100 hover:bg-red-200 rounded-full">Alpa</button></form>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                        <td colspan="4" class="px-6 py-4 text-center">Tidak ada siswa di kelas ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
