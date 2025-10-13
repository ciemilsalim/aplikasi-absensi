<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Riwayat Kehadiran Kelas') }} {{ $class->name }}
        </h2>
    </x-slot>

    <div 
        x-data="{
            showModal: false,
            attendanceId: '',
            studentId: '',
            studentName: '',
            date: '',
            currentStatus: '',
            openModal(attendanceId, studentId, studentName, date, status) {
                this.attendanceId = attendanceId;
                this.studentId = studentId;
                this.studentName = studentName;
                this.date = date;
                this.currentStatus = status || 'hapus';
                this.showModal = true;
            }
        }"
        class="py-12"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <form method="GET" action="{{ route('teacher.attendance.history') }}" class="flex flex-wrap items-end gap-4">
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Bulan & Tahun</label>
                                <input type="month" name="month" id="month" value="{{ $selectedDate->format('Y-m') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-600 dark:border-gray-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.572a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                Filter
                            </button>
                            <a href="{{ route('teacher.attendance.print', ['month' => $selectedDate->format('Y-m')]) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                </svg>
                                Cetak
                            </a>
                            {{-- TOMBOL BARU UNTUK EXPORT EXCEL --}}
                            <a href="{{ route('teacher.attendance.export.excel', ['month' => $selectedDate->format('Y-m')]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5l3 3m0 0l3-3m-3 3v-6m1.06-4.19l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                                Export Excel
                            </a>
                        </form>
                    </div>

                    {{-- Tabel Riwayat Kehadiran --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border-collapse">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="sticky left-0 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider z-10" style="width: 192px; min-width: 192px; max-width: 192px;">
                                        Nama Siswa
                                    </th>
                                    
                                    @foreach ($period as $date)
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $date->format('d/m') }}
                                        </th>
                                    @endforeach

                                    {{-- Kolom Rekapitulasi --}}
                                    <th scope="col" class="bg-green-100 dark:bg-green-800 px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider" title="Hadir">H</th>
                                    <th scope="col" class="bg-purple-100 dark:bg-purple-800 px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider" title="Sakit">S</th>
                                    <th scope="col" class="bg-blue-100 dark:bg-blue-800 px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider" title="Izin">I</th>
                                    <th scope="col" class="bg-red-100 dark:bg-red-800 px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider" title="Alpa">A</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($students as $student)
                                    <tr>
                                        <td class="sticky left-0 bg-white dark:bg-gray-800 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 z-10 truncate" style="width: 192px; min-width: 192px; max-width: 192px;">
                                            {{ $student->name }}
                                        </td>

                                        @foreach ($period as $date)
                                            @php
                                                $dateString = $date->format('Y-m-d');
                                                $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                                                $status = $attendanceRecord ? $attendanceRecord->status : null;
                                                $attendanceId = $attendanceRecord ? $attendanceRecord->id : '';
                                                $badgeColor = 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300';
                                                $statusText = '-';

                                                switch ($status) {
                                                    case 'tepat_waktu': $badgeColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'; $statusText = 'H'; break;
                                                    case 'terlambat': $badgeColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'; $statusText = 'T'; break;
                                                    case 'izin': $badgeColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'; $statusText = 'I'; break;
                                                    case 'sakit': $badgeColor = 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'; $statusText = 'S'; break;
                                                    case 'alpa': $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; $statusText = 'A'; break;
                                                }
                                            @endphp
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                                <button 
                                                    @click="openModal('{{ $attendanceId }}', '{{ $student->id }}', '{{ $student->name }}', '{{ $dateString }}', '{{ $status }}')"
                                                    class="w-8 h-8 flex items-center justify-center font-semibold rounded-full transition-transform transform hover:scale-110 {{ $badgeColor }}"
                                                    title="Klik untuk ubah status"
                                                >
                                                    {{ $statusText }}
                                                </button>
                                            </td>
                                        @endforeach
                                        
                                        {{-- Sel Rekapitulasi --}}
                                        @php
                                            $summary = $attendanceSummary[$student->id];
                                        @endphp
                                        <td class="bg-green-50 dark:bg-green-900/50 px-2 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-700 dark:text-gray-300">{{ $summary['hadir'] }}</td>
                                        <td class="bg-purple-50 dark:bg-purple-900/50 px-2 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-700 dark:text-gray-300">{{ $summary['sakit'] }}</td>
                                        <td class="bg-blue-50 dark:bg-blue-900/50 px-2 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-700 dark:text-gray-300">{{ $summary['izin'] }}</td>
                                        <td class="bg-red-50 dark:bg-red-900/50 px-2 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-700 dark:text-gray-300">{{ $summary['alpa'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $period->count() + 5 }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            Tidak ada data siswa di kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <!-- Modal untuk Edit Kehadiran -->
        <div x-show="showModal" style="display: none;" @keydown.escape.window="showModal = false" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('teacher.attendance.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="attendance_id" :value="attendanceId">
                        <input type="hidden" name="student_id" :value="studentId">
                        <input type="hidden" name="date" :value="date">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        Ubah Kehadiran
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Ubah status untuk <strong x-text="studentName"></strong> pada tanggal <strong x-text="new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></strong>.
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Kehadiran</label>
                                        <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="tepat_waktu" :selected="currentStatus === 'tepat_waktu'">Hadir (Tepat Waktu)</option>
                                            <option value="terlambat" :selected="currentStatus === 'terlambat'">Terlambat</option>
                                            <option value="izin" :selected="currentStatus === 'izin'">Izin</option>
                                            <option value="sakit" :selected="currentStatus === 'sakit'">Sakit</option>
                                            <option value="alpa" :selected="currentStatus === 'alpa'">Alpa</option>
                                            <option value="hapus" class="text-red-600 font-semibold">-- Kosongkan/Hapus Data --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Simpan Perubahan
                            </button>
                            <button @click="showModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-500 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

