<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse($students as $student)
                <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $student->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelas: {{ $student->schoolClass->name ?? 'Belum ada kelas' }}</p>
                        
                        <div class="mt-4">
                            <h4 class="font-medium text-gray-700 dark:text-gray-300">5 Riwayat Kehadiran Terakhir:</h4>
                            <ul class="mt-2 space-y-2">
                                @forelse($student->attendances as $attendance)
                                    <li class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700 rounded-md">
                                         <div>
                                            {{-- PERBAIKAN: Menggunakan translatedFormat untuk menampilkan tanggal dalam Bahasa Indonesia --}}
                                            <p class="font-semibold">{{ $attendance->attendance_time->translatedFormat('l, d F Y') }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                Masuk: {{ $attendance->attendance_time->format('H:i') }} | Pulang: {{ $attendance->checkout_time ? $attendance->checkout_time->format('H:i') : '-' }}
                                            </p>
                                        </div>
                                        @if($attendance->status == 'terlambat')
                                            <span class="text-xs font-medium bg-red-100 text-red-800 px-2 py-1 rounded-full">Terlambat</span>
                                        @else
                                             <span class="text-xs font-medium bg-green-100 text-green-800 px-2 py-1 rounded-full">Tepat Waktu</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="p-3 text-sm text-gray-500">Belum ada data kehadiran.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @empty
                 <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                     <p>Belum ada data siswa yang terhubung dengan akun Anda.</p>
                 </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
