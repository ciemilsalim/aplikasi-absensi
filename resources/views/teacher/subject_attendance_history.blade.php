<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
            ['title' => 'Riwayat Absensi Mapel', 'url' => '']
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Riwayat Absensi Mata Pelajaran') }}
        </h2>
    </x-slot>

    <div class="py-6 md:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filter Tanggal -->
            <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-4">
                <form action="{{ route('teacher.subject.attendance.history') }}" method="GET">
                    <div class="flex items-center space-x-4">
                        <label for="date" class="text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Tanggal:</label>
                        <x-text-input type="date" name="date" id="date" value="{{ $selectedDate->format('Y-m-d') }}" class="w-full md:w-auto" />
                        <x-primary-button type="submit">Tampilkan</x-primary-button>
                    </div>
                </form>
            </div>

            @if($attendances->isEmpty())
                <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-10 text-center">
                    <p class="text-gray-500 dark:text-gray-400">Tidak ada data absensi untuk tanggal {{ $selectedDate->translatedFormat('d F Y') }}.</p>
                </div>
            @else
                @foreach($attendances as $scheduleId => $attendanceGroup)
                    @php
                        $firstRecord = $attendanceGroup->first();
                        $scheduleInfo = $firstRecord->schedule;
                        $assignment = $scheduleInfo->teachingAssignment;
                    @endphp
                    <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $assignment->subject->name }} - {{ $assignment->schoolClass->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Waktu: {{ \Carbon\Carbon::parse($scheduleInfo->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($scheduleInfo->end_time)->format('H:i') }}</p>
                        </div>
                        <div class="p-6">
                            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach($attendanceGroup as $record)
                                    <li class="py-3 flex items-center justify-between">
                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $record->student->name }}</span>
                                        <div class="flex items-center space-x-4">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $record->created_at->format('H:i:s') }}</span>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                @if($record->status == 'hadir') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @endif
                                                @if($record->status == 'sakit') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300 @endif
                                                @if($record->status == 'izin') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 @endif
                                                @if($record->status == 'alpa') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @endif
                                            ">{{ ucfirst($record->status) }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
