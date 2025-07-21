<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Orang Tua', 'url' => route('parent.dashboard')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Welcome & Notifikasi -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Selamat Datang, {{ Auth::user()->name }}!</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pantau kehadiran dan ajukan izin untuk putra/putri Anda di sini.</p>
                </div>

                {{-- Notifikasi Internal --}}
                @if($unreadNotifications->isNotEmpty())
                    @foreach($unreadNotifications as $notification)
                    <div x-data="{ show: true }" x-show="show" x-transition class="bg-yellow-100 dark:bg-yellow-900/50 border-l-4 border-yellow-500 text-yellow-800 dark:text-yellow-200 p-4 rounded-r-lg" role="alert">
                        <div class="flex">
                            <div class="py-1"><svg class="h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg></div>
                            <div class="flex-grow">
                                <p class="font-bold">{{ $notification->title }}</p>
                                <p class="text-sm">{{ $notification->message }}</p>
                            </div>
                            {{-- PERBAIKAN: Menggunakan nama rute yang benar --}}
                            <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                @csrf
                                <button @click="show = false" type="submit" class="ml-4 text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-200" title="Tandai sudah dibaca">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <!-- Tombol Aksi Utama -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm">
                <a href="{{ route('parent.leave-requests.create') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Ajukan Izin atau Sakit
                </a>
            </div>

            <!-- Kartu Data Siswa -->
            @forelse($students as $student)
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Kelas: {{ $student->schoolClass->name ?? 'Belum ada kelas' }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 border-t border-gray-200 dark:border-slate-700 pt-6">
                            <h4 class="text-base font-medium text-gray-700 dark:text-gray-300">Riwayat Kehadiran (5 Hari Terakhir):</h4>
                            <ul class="mt-4 space-y-3">
                                @forelse($student->attendances as $attendance)
                                    <li class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-md">
                                        <div>
                                            <p class="font-semibold">{{ $attendance->attendance_time->translatedFormat('l, d F Y') }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                @if(!in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                                    Masuk: {{ $attendance->attendance_time->format('H:i') }} | Pulang: {{ $attendance->checkout_time ? $attendance->checkout_time->format('H:i') : '-' }}
                                                @else
                                                    Keterangan: <span class="capitalize">{{ $attendance->status }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        @if ($attendance->status === 'tepat_waktu')
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Hadir</span>
                                        @elseif ($attendance->status === 'terlambat')
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Terlambat</span>
                                        @elseif ($attendance->status === 'izin')
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Izin</span>
                                        @elseif ($attendance->status === 'sakit')
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">Sakit</span>
                                        @elseif ($attendance->status === 'alpa')
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-300">Alpa</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="p-3 text-sm text-gray-500 italic">Belum ada data kehadiran dalam 5 hari terakhir.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @empty
                 <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-600 dark:text-gray-400">
                        <p>Belum ada data siswa yang terhubung dengan akun Anda.</p>
                        <p class="text-sm mt-2">Silakan hubungi admin sekolah untuk menghubungkan data anak Anda.</p>
                    </div>
                 </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
