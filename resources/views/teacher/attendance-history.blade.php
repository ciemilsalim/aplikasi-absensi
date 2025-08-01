<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Riwayat Kehadiran Kelas') }} {{ $class->name }}
        </h2>
    </x-slot>

    {{-- AlpineJS component untuk mengelola modal --}}
    <div 
        x-data="{
            showModal: false,
            studentId: '',
            studentName: '',
            date: '',
            currentStatus: '',
            openModal(studentId, studentName, date, status) {
                this.studentId = studentId;
                this.studentName = studentName;
                this.date = date;
                this.currentStatus = status || 'hapus'; // Default ke 'hapus' jika belum ada status
                this.showModal = true;
            }
        }"
        class="py-12"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    {{-- Form Filter Tanggal --}}
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <form method="GET" action="{{ route('teacher.attendance.history') }}" class="flex flex-col sm:flex-row sm:items-end sm:space-x-4 space-y-4 sm:space-y-0">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-600 dark:border-gray-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Selesai</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-600 dark:border-gray-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Filter
                            </button>
                        </form>
                    </div>

                    {{-- Tabel Riwayat Kehadiran --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="sticky left-0 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider z-10">
                                        Nama Siswa
                                    </th>
                                    @foreach ($period as $date)
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $date->format('d/m') }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($students as $student)
                                    <tr>
                                        <td class="sticky left-0 bg-white dark:bg-gray-800 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 z-10">
                                            {{ $student->name }}
                                        </td>
                                        @foreach ($period as $date)
                                            @php
                                                $dateString = $date->format('Y-m-d');
                                                $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                                                $status = $attendanceRecord ? $attendanceRecord->status : null;
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
                                                    @click="openModal('{{ $student->id }}', '{{ $student->name }}', '{{ $dateString }}', '{{ $status }}')"
                                                    class="w-8 h-8 flex items-center justify-center font-semibold rounded-full transition-transform transform hover:scale-110 {{ $badgeColor }}"
                                                    title="Klik untuk ubah status"
                                                >
                                                    {{ $statusText }}
                                                </button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $period->count() + 1 }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
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
                <!-- Latar belakang gelap -->
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false" aria-hidden="true"></div>

                <!-- Konten Modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="'{{ route('teacher.attendance.update') }}'" method="POST">
                        @csrf
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
