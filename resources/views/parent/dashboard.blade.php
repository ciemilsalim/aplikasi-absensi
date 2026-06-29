<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Orang Tua', 'url' => route('parent.dashboard')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Orang Tua') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
            
            <!-- Welcome & Notifikasi -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Selamat Datang di Portal Presensi Orang Tua, {{ Auth::user()->name }}!</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pantau kehadiran real-time putra/putri Anda dan ajukan izin ketidakhadiran secara mudah dari sini.</p>
                </div>

                {{-- Notifikasi Internal untuk Siswa Alpa --}}
                @if($unreadNotifications->isNotEmpty())
                    @foreach($unreadNotifications as $notification)
                    <div x-data="{ show: true }" x-show="show" x-transition class="bg-yellow-100 dark:bg-yellow-900/50 border-l-4 border-yellow-500 text-yellow-800 dark:text-yellow-200 p-4 rounded-r-lg" role="alert">
                        <div class="flex">
                            <div class="py-1"><svg class="h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg></div>
                            <div class="flex-grow">
                                <p class="font-bold">{{ $notification->title }}</p>
                                <p class="text-sm">{{ $notification->message }}</p>
                            </div>
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

            <!-- Pengumuman dari Admin -->
            @if($announcements->isNotEmpty())
            <div class="space-y-4">
                 <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pengumuman Terbaru</h3>
                 @foreach($announcements as $announcement)
                    <div class="bg-sky-100 dark:bg-sky-900/50 border-l-4 border-sky-500 text-sky-800 dark:text-sky-200 p-4 rounded-r-lg" role="alert">
                        <div class="flex">
                            <div class="py-1">
                                <svg class="h-6 w-6 text-sky-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 0 1-4.5-4.5V7.5a4.5 4.5 0 0 1 4.5-4.5h7.5a4.5 4.5 0 0 1 4.5 4.5v1.25m-16.5 6.375c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold">{{ $announcement->title }}</p>
                                <p class="text-xs text-sky-700 dark:text-sky-300/80 mb-2">Dipublikasikan pada {{ $announcement->published_at->translatedFormat('d F Y') }}</p>
                                <div class="text-sm">
                                    {!! nl2br(e($announcement->content)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                 @endforeach
            </div>
            @endif

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

                        {{-- Riwayat Ekstrakurikuler --}}
                        <div class="mt-8 border-t border-gray-200 dark:border-slate-700 pt-6">
                            <h4 class="text-base font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                </svg>
                                Kegiatan Ekstrakurikuler:
                            </h4>
                            
                            @if($student->extracurriculars->isNotEmpty())
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($student->extracurriculars as $ekskul)
                                        <div class="bg-slate-50 dark:bg-slate-700/30 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                            <h5 class="font-bold text-slate-800 dark:text-white uppercase text-xs tracking-tight mb-3">{{ $ekskul->name }}</h5>

                                            <div class="space-y-2">
                                                @php
                                                    $ekskulAttendances = $student->extracurricularAttendances
                                                        ->where('extracurricular_id', $ekskul->id)
                                                        ->take(3);
                                                @endphp
                                                
                                                @forelse($ekskulAttendances as $att)
                                                    <div class="flex justify-between items-center text-[10px]">
                                                        <span class="text-slate-500 dark:text-slate-400 font-medium">{{ \Carbon\Carbon::parse($att->attendance_date)->translatedFormat('d M Y') }}</span>
                                                        <span class="px-2 py-0.5 rounded-full font-black uppercase text-[9px]
                                                            @if($att->status == 'hadir') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 @endif
                                                            @if($att->status == 'sakit') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 @endif
                                                            @if($att->status == 'izin') bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 @endif
                                                            @if($att->status == 'alpa') bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 @endif
                                                        ">
                                                            {{ $att->status }}
                                                        </span>
                                                    </div>
                                                @empty
                                                    <p class="text-[10px] text-slate-400 italic">Belum ada riwayat absensi.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-4 text-xs text-gray-500 italic">Anak Anda belum terdaftar di kegiatan ekstrakurikuler manapun.</p>
                            @endif
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
</x-app-layout>
