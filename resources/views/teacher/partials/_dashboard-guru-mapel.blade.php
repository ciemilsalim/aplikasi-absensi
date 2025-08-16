<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Kolom Utama: Jadwal Mengajar -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Welcome Section -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 flex items-center gap-6">
                <div class="flex-shrink-0">
                    <span class="inline-block h-16 w-16 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700">
                        <svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Selamat Datang, {{ $teacher->name }}!</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Berikut adalah jadwal mengajar Anda untuk hari ini, {{ now()->translatedFormat('l, d F Y') }}.</p>
                </div>
            </div>
        </div>

        <!-- Jadwal Mengajar Hari Ini -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Jadwal Mengajar Hari Ini</h3>
                <div class="space-y-4">
                    @forelse($schedulesToday as $schedule)
                        <div class="border-l-4 {{ now()->between(Carbon\Carbon::parse($schedule->start_time), Carbon\Carbon::parse($schedule->end_time)) ? 'border-green-500' : 'border-sky-500' }} bg-gray-50 dark:bg-slate-900/50 p-4 rounded-r-lg flex items-center justify-between">
                            <div>
                                {{-- PERBAIKAN: Mengakses relasi melalui teachingAssignment --}}
                                <p class="font-bold text-gray-800 dark:text-gray-200">{{ $schedule->teachingAssignment->subject->name }} - <span class="text-sky-600 dark:text-sky-400">{{ $schedule->teachingAssignment->schoolClass->name }}</span></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </p>
                            </div>
                            <div>
                                {{-- PERBAIKAN: Mengarahkan tombol ke rute pemindai --}}
                                <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" class="px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm">
                                    <i class="fas fa-qrcode mr-2"></i>
                                    Ambil Absensi
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 px-6 bg-gray-50 dark:bg-slate-900/50 rounded-lg">
                            <i class="fas fa-calendar-check fa-3x text-gray-400"></i>
                            <p class="mt-4 text-gray-600 dark:text-gray-300">Tidak ada jadwal mengajar untuk Anda hari ini.</p>
                            <p class="text-sm text-gray-400">Saatnya bersantai atau mempersiapkan materi untuk esok hari.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Samping: Akses Cepat & Notifikasi -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Panel Akses Cepat -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Akses Cepat</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('scanner') }}" target="_blank" class="flex flex-col items-center justify-center p-4 bg-sky-50 dark:bg-sky-900/50 hover:bg-sky-100 dark:hover:bg-sky-900 rounded-lg transition-colors duration-200">
                    <i class="fas fa-id-card h-8 w-8 text-sky-600 dark:text-sky-400 mb-2"></i>
                    <span class="text-sm font-medium text-center text-sky-800 dark:text-sky-300">Absen Harian</span>
                </a>
                <a href="{{ route('teacher.leave_requests.index') }}" class="flex flex-col items-center justify-center p-4 bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900 rounded-lg transition-colors duration-200">
                    <i class="fas fa-file-alt h-8 w-8 text-indigo-600 dark:text-indigo-400 mb-2"></i>
                    <span class="text-sm font-medium text-center text-indigo-800 dark:text-indigo-300">Persetujuan Izin</span>
                </a>
            </div>
        </div>
        
        <!-- Panel Pengumuman (jika ada) -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Pengumuman Sekolah</h3>
            {{-- Loop pengumuman di sini --}}
            <p class="text-sm text-center text-gray-400 italic py-4">Belum ada pengumuman baru.</p>
        </div>
    </div>
</div>
