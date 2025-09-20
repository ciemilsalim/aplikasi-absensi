<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Kolom Utama: Jadwal Mengajar & Grafik -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Welcome Section -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 flex items-center gap-6">
                <div class="flex-shrink-0">
                    <img class="h-16 w-16 rounded-full object-cover" src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=7F9CF5&background=EBF4FF' }}" alt="{{ Auth::user()->name }}">
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Selamat Datang, {{ $teacher->name }}!</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Berikut adalah jadwal mengajar Anda untuk hari ini, {{ now()->translatedFormat('l, d F Y') }}.</p>
                </div>
            </div>
        </div>

        <!-- Performa Kehadiran per Kelas -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Performa Kehadiran per Kelas</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Rata-rata kehadiran dalam 30 hari terakhir.</p>
                <div class="h-64">
                    <canvas id="classPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Jadwal Mengajar Hari Ini -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Jadwal Mengajar Hari Ini</h3>
                <div class="relative border-l border-gray-200 dark:border-slate-700 ml-4">
                    @forelse($schedulesToday as $schedule)
                        <div class="mb-10 ml-8">
                            <span class="absolute flex items-center justify-center w-8 h-8 {{ now()->between(Carbon\Carbon::parse($schedule->start_time), Carbon\Carbon::parse($schedule->end_time)) ? 'bg-green-100 dark:bg-green-900' : 'bg-sky-100 dark:bg-sky-900' }} rounded-full -left-4 ring-8 ring-white dark:ring-slate-800">
                                <i class="fas fa-chalkboard-teacher {{ now()->between(Carbon\Carbon::parse($schedule->start_time), Carbon\Carbon::parse($schedule->end_time)) ? 'text-green-600 dark:text-green-400' : 'text-sky-600 dark:text-sky-400' }}"></i>
                            </span>
                            <div class="p-4 bg-gray-50 dark:bg-slate-900/50 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
                                <time class="mb-1 text-sm font-normal leading-none text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </time>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $schedule->teachingAssignment->subject->name }}
                                </h4>
                                <p class="text-base font-normal text-gray-600 dark:text-gray-300">
                                    Kelas: {{ $schedule->teachingAssignment->schoolClass->name }}
                                </p>
                                <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" class="inline-flex items-center px-4 py-2 mt-4 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 focus:ring-4 focus:outline-none focus:ring-sky-300 dark:focus:ring-sky-800">
                                    <i class="fas fa-qrcode mr-2"></i>
                                    Ambil Absensi
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="ml-4">
                             <div class="text-center py-10 px-6">
                                <i class="fas fa-calendar-check fa-3x text-gray-400 dark:text-gray-500"></i>
                                <p class="mt-4 text-gray-600 dark:text-gray-300">Tidak ada jadwal mengajar untuk Anda hari ini.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Samping -->
    <div class="lg:col-span-1 space-y-6">
        <!-- PANEL BARU: Catatan Pribadi -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <form id="note-form" action="{{ route('teacher.notes.update') }}" method="POST">
                @csrf
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Catatan Pribadi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Catatan ini hanya terlihat oleh Anda.</p>
                    <textarea id="teacher-note-content" name="content" rows="6" class="w-full border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500 rounded-md shadow-sm" placeholder="Tulis pengingat atau catatan penting di sini...">{{ $teacherNote->content ?? '' }}</textarea>
                    <div id="note-status" class="text-xs text-green-600 dark:text-green-400 mt-2 h-4 opacity-0 transition-opacity duration-300">
                        Catatan disimpan!
                    </div>
                </div>
            </form>
        </div>

        @if($lastAttendanceSummary)
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Absensi Terakhir</h3>
            </div>
            <div class="p-6 space-y-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sesi terakhir Anda:</p>
                    <p class="font-bold text-lg text-sky-600 dark:text-sky-400">{{ $lastAttendanceSummary['schedule']->teachingAssignment->subject->name }}</p>
                    <p class="text-gray-700 dark:text-gray-300">Kelas {{ $lastAttendanceSummary['schedule']->teachingAssignment->schoolClass->name }}</p>
                </div>
                <div class="border-t border-gray-200 dark:border-slate-700 pt-3">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-green-50 dark:bg-green-900/50 p-2 rounded-lg"><span class="font-bold text-lg text-green-700 dark:text-green-400">{{ $lastAttendanceSummary['hadir'] }}</span><p class="text-xs text-green-600 dark:text-green-500">Hadir</p></div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/50 p-2 rounded-lg"><span class="font-bold text-lg text-yellow-700 dark:text-yellow-400">{{ $lastAttendanceSummary['sakit'] }}</span><p class="text-xs text-yellow-600 dark:text-yellow-500">Sakit</p></div>
                        <div class="bg-blue-50 dark:bg-blue-900/50 p-2 rounded-lg"><span class="font-bold text-lg text-blue-700 dark:text-blue-400">{{ $lastAttendanceSummary['izin'] }}</span><p class="text-xs text-blue-600 dark:text-blue-500">Izin</p></div>
                        <div class="bg-red-50 dark:bg-red-900/50 p-2 rounded-lg"><span class="font-bold text-lg text-red-700 dark:text-red-400">{{ $lastAttendanceSummary['alpa'] }}</span><p class="text-xs text-red-600 dark:text-red-500">Alpa</p></div>
                        <div class="bg-orange-50 dark:bg-orange-900/50 p-2 rounded-lg"><span class="font-bold text-lg text-orange-700 dark:text-orange-400">{{ $lastAttendanceSummary['bolos'] }}</span><p class="text-xs text-orange-600 dark:text-orange-500">Bolos</p></div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Siswa Butuh Perhatian</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Berdasarkan absensi semester ini.</p>
            </div>
            <div class="p-6">
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    {{-- PERBAIKAN: Menggunakan variabel $studentsForAttentionMapel --}}
                    @forelse($studentsForAttentionMapel as $data)
                        @if($data->student)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $data->student->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data->student->schoolClass->name ?? '' }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                @if($data->alpa_count > 0)<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Alpa: {{ $data->alpa_count }}</span>@endif
                                @if($data->bolos_count > 0)<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 ml-2">Bolos: {{ $data->bolos_count }}</span>@endif
                            </div>
                        </li>
                        @endif
                    @empty
                        <li class="py-10 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-user-check text-3xl text-gray-300 dark:text-gray-600"></i><p class="mt-3">Tidak ada siswa yang perlu perhatian khusus.</p></li>
                    @endforelse
                </ul>
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
                    backgroundColor: 'rgba(14, 165, 233, 0.5)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 1
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
                        ticks: { color: isDarkMode ? '#94a3b8' : '#64748b' },
                        grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' }
                    },
                    y: {
                         ticks: { color: isDarkMode ? '#94a3b8' : '#64748b' },
                         grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => ' Kehadiran: ' + context.parsed.x + '%'
                        }
                    }
                }
            }
        });

        // --- JAVASCRIPT BARU UNTUK CATATAN PRIBADI ---
        const noteForm = document.getElementById('note-form');
        const noteContent = document.getElementById('teacher-note-content');
        const noteStatus = document.getElementById('note-status');
        let saveTimeout;

        noteContent.addEventListener('input', () => {
            // Hapus timeout yang ada jika pengguna mengetik lagi
            clearTimeout(saveTimeout);
            
            // Atur timeout baru untuk menyimpan setelah 1.5 detik tidak ada ketikan
            saveTimeout = setTimeout(() => {
                saveNote();
            }, 1500);
        });

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
                    // Tampilkan pesan "Tersimpan"
                    noteStatus.textContent = 'Catatan disimpan!';
                    noteStatus.classList.remove('text-red-600');
                    noteStatus.classList.add('text-green-600');
                    noteStatus.style.opacity = '1';
                    // Sembunyikan lagi setelah 2 detik
                    setTimeout(() => {
                        noteStatus.style.opacity = '0';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                noteStatus.textContent = 'Gagal menyimpan.';
                noteStatus.classList.remove('text-green-600');
                noteStatus.classList.add('text-red-600');
                noteStatus.style.opacity = '1';
            });
        }
    });
</script>
@endpush
