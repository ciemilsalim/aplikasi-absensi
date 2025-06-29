<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Anak', 'url' => route('parent.dashboard')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
           {{-- Bagian Pengumuman dengan Desain Alert Baru --}}
            @if($announcements->isNotEmpty())
            <div class="space-y-4">
                 <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pengumuman Terbaru</h3>
                 @foreach($announcements as $announcement)
                    <div class="bg-sky-100 dark:bg-sky-900/50 border-l-4 border-sky-500 text-sky-800 dark:text-sky-200 p-4 rounded-r-lg" role="alert">
                        <div class="flex">
                            <div class="py-1">
                                {{-- Ikon Megaphone --}}
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
            {{-- PERBAIKAN: Menambahkan Welcome Section --}}
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-xl font-semibold">Selamat Datang, {{ Auth::user()->name }}!</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Ini adalah halaman dasbor Anda. Di sini Anda dapat memantau riwayat kehadiran anak-anak Anda dan mengajukan izin jika diperlukan.
                    </p>
                </div>
            </div>

            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 sm:rounded-lg" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @forelse($students as $student)
                <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Kelas: {{ $student->schoolClass->name ?? 'Belum ada kelas' }}</p>
                            </div>
                            <div class="mt-4 sm:mt-0">
                                {{-- Tombol ini bisa dikembangkan untuk melihat laporan detail per anak --}}
                                <a href="#" class="text-sm font-medium text-sky-600 hover:text-sky-500">Lihat Laporan Detail &rarr;</a>
                            </div>
                        </div>
                        
                        <div class="mt-6 border-t border-gray-200 dark:border-slate-700">
                            <dl class="divide-y divide-gray-200 dark:divide-slate-700">
                                <div class="py-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Riwayat Kehadiran (5 Hari Terakhir)</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        <ul class="mt-2 space-y-2">
                                            @forelse($student->attendances as $attendance)
                                                <li class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-md">
                                                    <div>
                                                        <p class="font-semibold">{{ $attendance->attendance_time->translatedFormat('l, d F Y') }}</p>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                                            @if(!in_array($attendance->status, ['izin', 'sakit']))
                                                                Masuk: {{ $attendance->attendance_time->format('H:i') }} | Pulang: {{ $attendance->checkout_time ? $attendance->checkout_time->format('H:i') : '-' }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                    @if ($attendance->status === 'tepat_waktu')
                                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Tepat Waktu</span>
                                                    @elseif ($attendance->status === 'terlambat')
                                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Terlambat</span>
                                                    @elseif ($attendance->status === 'izin')
                                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Izin</span>
                                                    @elseif ($attendance->status === 'sakit')
                                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">Sakit</span>
                                                    @endif
                                                </li>
                                            @empty
                                                <li class="p-3 text-sm text-gray-500 italic">Belum ada data kehadiran dalam 5 hari terakhir.</li>
                                            @endforelse
                                        </ul>
                                    </dd>
                                </div>
                            </dl>
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
