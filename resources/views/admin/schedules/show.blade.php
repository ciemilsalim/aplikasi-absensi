<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Administrasi', 'url' => '#'],
            ['title' => 'Jadwal Pelajaran', 'url' => route('admin.schedules.index')],
            ['title' => $schoolClass->name, 'url' => '#']
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Jadwal Pelajaran Kelas: {{ $schoolClass->name }}
        </h2>
    </x-slot>

    <div x-data="{ showModal: false, showConfirmModal: false, deleteUrl: '' }" class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p>{{ session('success') }}</p></div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p>{{ session('error') }}</p></div>
            @endif

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Jadwal Mingguan</h3>
                        <x-primary-button @click="showModal = true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Tambah Jadwal
                        </x-primary-button>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg border border-gray-200 dark:border-slate-700">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    @foreach ($days as $day)
                                        <th scope="col" class="px-6 py-3 text-center">{{ $day }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white dark:bg-slate-800 align-top">
                                    @foreach ($days as $dayNumber => $dayName)
                                        <td class="p-2 border-t border-gray-200 dark:border-slate-700">
                                            <div class="space-y-2">
                                                @if(isset($schedules[$dayNumber]))
                                                    @foreach ($schedules[$dayNumber]->sortBy('start_time') as $scheduleGroup)
                                                        @foreach($scheduleGroup as $schedule)
                                                        <div class="p-2 rounded-lg bg-sky-50 dark:bg-slate-700/50 border border-sky-200 dark:border-slate-600">
                                                            <p class="font-semibold text-sky-800 dark:text-sky-300">{{ $schedule->teachingAssignment->subject->name }}</p>
                                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $schedule->teachingAssignment->teacher->name }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ date('H:i', strtotime($schedule->start_time)) }} - {{ date('H:i', strtotime($schedule->end_time)) }}</p>
                                                            <div class="mt-2 text-right">
                                                                <button @click="showConfirmModal = true; deleteUrl = '{{ route('admin.schedules.destroy', $schedule) }}'" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    @endforeach
                                                @else
                                                    <div class="text-center text-xs text-gray-400 py-4">-</div>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Jadwal -->
        <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" style="display: none;">
            <div @click.away="showModal = false" class="w-full max-w-lg p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
                <form action="{{ route('admin.schedules.store', $schoolClass) }}" method="POST">
                    @csrf
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tambah Jadwal Baru</h3>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="teaching_assignment_id" value="Guru & Mata Pelajaran" />
                            <select id="teaching_assignment_id" name="teaching_assignment_id" class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm" required>
                                <option value="">-- Pilih --</option>
                                @foreach($assignments as $assignment)
                                    <option value="{{ $assignment->id }}">{{ $assignment->subject->name }} - {{ $assignment->teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="day_of_week" value="Hari" />
                            <select id="day_of_week" name="day_of_week" class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm" required>
                                @foreach($days as $dayNumber => $dayName)
                                    <option value="{{ $dayNumber }}">{{ $dayName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="start_time" value="Jam Mulai" />
                                <x-text-input type="time" id="start_time" name="start_time" class="block mt-1 w-full" required />
                            </div>
                            <div>
                                <x-input-label for="end_time" value="Jam Selesai" />
                                <x-text-input type="time" id="end_time" name="end_time" class="block mt-1 w-full" required />
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-4">
                        <x-secondary-button @click="showModal = false">Batal</x-secondary-button>
                        <x-primary-button type="submit">Simpan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div x-show="showConfirmModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" style="display: none;">
            <div @click.away="showConfirmModal = false" class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
                <div class="text-center">
                    <h3 class="mt-5 text-lg font-medium text-gray-900 dark:text-white">Hapus Jadwal?</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Anda yakin ingin menghapus jadwal ini?</p>
                </div>
                <div class="mt-6 flex justify-center gap-4">
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">Ya, Hapus</x-danger-button>
                    </form>
                    <x-secondary-button @click="showConfirmModal = false">Batal</x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
