<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kolom Utama: Jadwal Mengajar & Grafik (8 cols) -->
    <div class="lg:col-span-8 space-y-6">
        
        <!-- Welcome Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-sky-950 text-white p-6 sm:p-7 shadow-lg border border-slate-800/80">
            <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex items-center gap-4">
                <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-white/10 bg-slate-800 shrink-0" 
                     src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=0284c7&background=e0f2fe' }}" 
                     alt="{{ Auth::user()->name }}">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-500/20 text-[10px] font-bold text-sky-300 border border-sky-400/20 mb-1">
                        <span class="material-icons text-xs">menu_book</span>
                        <span>Guru Mata Pelajaran</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-snug">
                        Selamat Bertugas, {{ $teacher->name }}!
                    </h2>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Kelola presensi per jam pelajaran dan pantau kedisiplinan siswa pada setiap sesi mengajar.
                    </p>
                </div>
            </div>
        </div>

        <!-- Jadwal Mengajar Timeline & Tabs -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden" x-data="{ activeTab: 'today' }">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-sky-500 text-lg">calendar_today</span>
                        Jadwal Jam Mengajar
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih jadwal untuk membuka sesi presensi kelas mata pelajaran</p>
                </div>
                
                <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl self-start sm:self-auto border border-slate-200/60 dark:border-slate-700/60">
                    <button @click="activeTab = 'today'" 
                            :class="activeTab === 'today' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" 
                            class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all">
                        Hari Ini
                    </button>
                    <button @click="activeTab = 'all'" 
                            :class="activeTab === 'all' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" 
                            class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all">
                        Semua Jadwal
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <!-- Tab Jadwal Hari Ini -->
                <div x-show="activeTab === 'today'" class="space-y-4">
                    @forelse($schedulesToday as $schedule)
                        @php
                            $isOngoing = now()->between(Carbon\Carbon::parse($schedule->start_time), Carbon\Carbon::parse($schedule->end_time));
                        @endphp
                        <div class="p-4 sm:p-5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $isOngoing ? 'bg-sky-50/70 dark:bg-sky-950/30 border-sky-300 dark:border-sky-800 shadow-xs' : 'bg-slate-50 dark:bg-slate-850/60 border-slate-200/80 dark:border-slate-800' }}">
                            <div class="flex items-start gap-3.5">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $isOngoing ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                    <span class="material-icons text-2xl">school</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold {{ $isOngoing ? 'bg-sky-200 dark:bg-sky-900 text-sky-800 dark:text-sky-200' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                        </span>
                                        @if($isOngoing)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Sedang Berlangsung
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white mt-1.5">
                                        {{ $schedule->teachingAssignment->subject->name }}
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        Kelas: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $schedule->teachingAssignment->schoolClass->name }}</span>
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/25 transition-all shrink-0">
                                <span class="material-icons text-base">qr_code_scanner</span>
                                <span>Buka Sesi Presensi</span>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-10 px-6">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                <span class="material-icons text-2xl">event_busy</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Tidak ada jadwal mengajar untuk Anda hari ini.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Tab Semua Jadwal -->
                <div x-show="activeTab === 'all'" class="space-y-3" style="display: none;">
                    @php
                        $dayNames = [
                            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
                        ];
                    @endphp
                    @forelse($allSchedules as $schedule)
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center shrink-0">
                                    <span class="material-icons text-xl">event_note</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-800 dark:text-sky-300">
                                            {{ $dayNames[$schedule->day_of_week] ?? 'Hari Lain' }}
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                        </span>
                                    </div>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-1">
                                        {{ $schedule->teachingAssignment->subject->name }} — <span class="font-normal text-slate-500 dark:text-slate-400">{{ $schedule->teachingAssignment->schoolClass->name }}</span>
                                    </h4>
                                </div>
                            </div>

                            <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 text-xs font-bold transition-all shrink-0">
                                <span class="material-icons text-sm text-sky-500">qr_code_2</span>
                                <span>Buka Presensi</span>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-10 px-6">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                <span class="material-icons text-2xl">event_busy</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Anda belum memiliki jadwal mengajar terdaftar.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Performa Kehadiran per Kelas -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                <span class="material-icons text-sky-500 text-lg">bar_chart</span>
                Performa Kehadiran per Kelas
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rata-rata persentase kehadiran mapel Anda dalam 30 hari terakhir</p>
            <div class="h-64 w-full">
                <canvas id="classPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Kolom Samping (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        
        <!-- Aksi Cepat Guru (Absen Datang/Pulang, Scan Izin Keluar, Jurnal Mengajar) -->
        <div class="space-y-3">
            <a href="{{ route('scanner') }}" 
               class="group p-3.5 sm:p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-sky-300 dark:hover:border-sky-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                    <span class="material-icons text-xl">qr_code_scanner</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Absen Datang/Pulang</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Scan Masuk & Pulang Siswa</p>
                </div>
                <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
            </a>

            <a href="{{ route('permit.scanner') }}" 
               class="group p-3.5 sm:p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                    <span class="material-icons text-xl">assignment</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Scan Izin Keluar</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Izin Tinggalkan Sekolah</p>
                </div>
                <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
            </a>

            <a href="{{ route('teacher.journals.index') }}" 
               class="group relative p-3.5 sm:p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                    <span class="material-icons text-xl">auto_stories</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Jurnal Mengajar</h4>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold uppercase bg-emerald-500 text-white shadow-2xs">Baru</span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Log & Rekap TP Fase D</p>
                </div>
                <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
            </a>
        </div>

        <!-- Catatan Pribadi -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <form id="note-form" action="{{ route('teacher.notes.update') }}" method="POST">
                @csrf
                <div class="p-5">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">edit_note</span>
                            Catatan Pribadi Guru
                        </h3>
                        <div id="note-status" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 opacity-0 transition-opacity">
                            Tersimpan
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-3">Hanya terlihat oleh Anda (tersimpan otomatis)</p>
                    
                    <textarea id="teacher-note-content" name="content" rows="5" 
                              class="w-full border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 rounded-2xl text-xs p-3 transition-all" 
                              placeholder="Tulis pengingat tugas, materi, atau catatan kelas di sini...">{{ $teacherNote->content ?? '' }}</textarea>
                </div>
            </form>
        </div>

        <!-- Ringkasan Absensi Terakhir -->
        @if(isset($lastAttendanceSummary) && $lastAttendanceSummary)
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-icons text-sky-500 text-lg">history</span>
                    Presensi Sesi Terakhir
                </h3>
            </div>
            <div class="p-5 space-y-3">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Mata Pelajaran & Kelas</span>
                    <h4 class="font-bold text-sm text-sky-600 dark:text-sky-400">{{ $lastAttendanceSummary['schedule']->teachingAssignment->subject->name }}</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Kelas {{ $lastAttendanceSummary['schedule']->teachingAssignment->schoolClass->name }}</p>
                </div>
                
                <div class="grid grid-cols-5 gap-1.5 pt-2 text-center">
                    <div class="bg-emerald-50 dark:bg-emerald-950/40 p-2 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                        <span class="font-extrabold text-xs text-emerald-700 dark:text-emerald-300 block">{{ $lastAttendanceSummary['hadir'] }}</span>
                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">Hadir</span>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-950/40 p-2 rounded-xl border border-amber-100 dark:border-amber-900/40">
                        <span class="font-extrabold text-xs text-amber-700 dark:text-amber-300 block">{{ $lastAttendanceSummary['sakit'] }}</span>
                        <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400">Sakit</span>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-950/40 p-2 rounded-xl border border-purple-100 dark:border-purple-900/40">
                        <span class="font-extrabold text-xs text-purple-700 dark:text-purple-300 block">{{ $lastAttendanceSummary['izin'] }}</span>
                        <span class="text-[9px] font-bold text-purple-600 dark:text-purple-400">Izin</span>
                    </div>
                    <div class="bg-red-50 dark:bg-red-950/40 p-2 rounded-xl border border-red-100 dark:border-red-900/40">
                        <span class="font-extrabold text-xs text-red-700 dark:text-red-300 block">{{ $lastAttendanceSummary['alpa'] }}</span>
                        <span class="text-[9px] font-bold text-red-600 dark:text-red-400">Alpa</span>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-950/40 p-2 rounded-xl border border-orange-100 dark:border-orange-900/40">
                        <span class="font-extrabold text-xs text-orange-700 dark:text-orange-300 block">{{ $lastAttendanceSummary['bolos'] }}</span>
                        <span class="text-[9px] font-bold text-orange-600 dark:text-orange-400">Bolos</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Siswa Butuh Perhatian (Mapel) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-icons text-rose-500 text-lg">priority_high</span>
                    Siswa Butuh Perhatian
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Berdasarkan ketidakhadiran di mapel Anda semester ini</p>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800/80 max-h-60 overflow-y-auto no-scrollbar">
                @forelse($studentsForAttentionMapel as $data)
                    @if($data->student)
                    <div class="p-3.5 flex justify-between items-center hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                        <div>
                            <p class="font-bold text-xs text-slate-800 dark:text-white">{{ $data->student->name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $data->student->schoolClass->name ?? '' }}</p>
                        </div>
                        <div class="text-right flex items-center gap-1.5 shrink-0">
                            @if($data->alpa_count > 0)
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300">
                                    Alpa: {{ $data->alpa_count }}
                                </span>
                            @endif
                            @if($data->bolos_count > 0)
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                    Bolos: {{ $data->bolos_count }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif
                @empty
                    <div class="p-6 text-center text-xs text-slate-400 italic">
                        Tidak ada siswa yang perlu perhatian khusus.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isDarkMode = document.documentElement.classList.contains('dark');
        const ctx = document.getElementById('classPerformanceChart').getContext('2d');
        const performanceData = @json($classPerformanceData ?? []);
        const labels = performanceData.map(d => d.label);
        const data = performanceData.map(d => d.percentage);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Kehadiran Rata-rata (%)',
                    data: data,
                    backgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.7)' : 'rgba(2, 132, 199, 0.75)',
                    hoverBackgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.95)' : 'rgba(2, 132, 199, 0.95)',
                    borderWidth: 0,
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { 
                            callback: (v) => v + '%',
                            color: isDarkMode ? '#94a3b8' : '#64748b',
                            font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                        },
                        grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }
                    },
                    y: {
                         ticks: { 
                             color: isDarkMode ? '#94a3b8' : '#64748b',
                             font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                         },
                         grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDarkMode ? '#1e293b' : '#0f172a',
                        titleFont: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 12, weight: 'bold' },
                        bodyFont: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 12 },
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            label: (context) => ' Kehadiran Rata-rata: ' + context.parsed.x + '%'
                        }
                    }
                }
            }
        });

        // Auto-save teacher notes logic
        const noteForm = document.getElementById('note-form');
        const noteContent = document.getElementById('teacher-note-content');
        const noteStatus = document.getElementById('note-status');
        let saveTimeout;

        if (noteContent) {
            noteContent.addEventListener('input', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    saveNote();
                }, 1500);
            });
        }

        function saveNote() {
            const formData = new FormData(noteForm);

            fetch(noteForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    noteStatus.textContent = 'Tersimpan!';
                    noteStatus.classList.remove('text-rose-600');
                    noteStatus.classList.add('text-emerald-600');
                    noteStatus.style.opacity = '1';
                    setTimeout(() => {
                        noteStatus.style.opacity = '0';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                noteStatus.textContent = 'Gagal menyimpan.';
                noteStatus.classList.remove('text-emerald-600');
                noteStatus.classList.add('text-rose-600');
                noteStatus.style.opacity = '1';
            });
        }
    });
</script>
@endpush

