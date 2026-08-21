<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    @if($currentView === 'pembina_ekskul')
                        Dasbor Pembina Ekskul
                    @elseif($currentView === 'wali_kelas')
                        Dasbor Wali Kelas
                    @elseif($currentView === 'guru_mapel')
                        Dasbor Guru Mata Pelajaran
                    @elseif($currentView === 'fasilitator_kokurikuler')
                        Dasbor Fasilitator Kokurikuler
                    @else
                        Dasbor & Presensi Guru
                    @endif
                </h1>
            </div>
            
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300 shadow-2xs">
                    <span class="material-icons text-sky-500 text-base">person</span>
                    <span>{{ Auth::user()->name }}</span>
                </span>
            </div>
        </div>
    </x-slot>

    @push('styles')
    <style>
        body > footer, body > .back-to-top-button { display: none !important; }
        footer.mobile-footer { display: block !important; }
    </style>
    @endpush

    <div class="space-y-6">

        {{-- === SWITCHER TAMPILAN PERAN GURU === --}}
        @php
            $roleCount = ($isHomeroomTeacher ? 1 : 0) + ($isSubjectTeacher ? 1 : 0) + ($isCocurricularFacilitator ? 1 : 0) + ($isExtracurricularCoach ? 1 : 0);
        @endphp

        @if($roleCount > 1)
            <div class="bg-white dark:bg-slate-900 shadow-sm border border-slate-200/80 dark:border-slate-800 rounded-2xl p-1.5 overflow-x-auto no-scrollbar">
                <div class="flex items-center space-x-1.5 min-w-max" role="tablist">
                    @if($isHomeroomTeacher)
                    <a href="{{ route('teacher.dashboard', ['view' => 'wali_kelas']) }}"
                       class="flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition-all duration-200
                              {{ $currentView === 'wali_kelas' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="material-icons text-base">groups</span>
                        <span>Wali Kelas</span>
                    </a>
                    @endif

                    @if($isSubjectTeacher)
                    <a href="{{ route('teacher.dashboard', ['view' => 'guru_mapel']) }}"
                       class="flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition-all duration-200
                              {{ $currentView === 'guru_mapel' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="material-icons text-base">menu_book</span>
                        <span>Guru Mapel</span>
                    </a>
                    @endif

                    @if($isCocurricularFacilitator)
                    <a href="{{ route('teacher.dashboard', ['view' => 'fasilitator_kokurikuler']) }}"
                       class="flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition-all duration-200
                              {{ $currentView === 'fasilitator_kokurikuler' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="material-icons text-base">psychology</span>
                        <span>Kokurikuler</span>
                    </a>
                    @endif

                    @if($isExtracurricularCoach)
                    <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}"
                       class="flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 text-xs font-bold rounded-xl transition-all duration-200
                              {{ $currentView === 'pembina_ekskul' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="material-icons text-base">military_tech</span>
                        <span>Ekskul</span>
                    </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- === PENGUMUMAN === --}}
        @if(isset($announcements) && $announcements->isNotEmpty())
            <div class="space-y-3">
                @foreach($announcements as $announcement)
                    <div class="bg-sky-50/90 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/60 p-4 rounded-2xl shadow-2xs">
                        <div class="flex items-start gap-3.5">
                            <div class="p-2 bg-sky-500 text-white rounded-xl shadow-xs shrink-0">
                                <span class="material-icons text-lg">campaign</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-sky-950 dark:text-sky-100">{{ $announcement->title }}</h4>
                                <p class="text-xs text-sky-800/90 dark:text-sky-300/90 mt-0.5 leading-relaxed">{{ $announcement->content }}</p>
                                <span class="text-[10px] text-sky-600 dark:text-sky-400 mt-1.5 inline-block font-semibold">
                                    {{ $announcement->published_at->translatedFormat('d F Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- === PERINGATAN HARI EFEKTIF === --}}
        @if(isset($isEffectiveDaysSet) && !$isEffectiveDaysSet)
            <div class="bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300/80 dark:border-amber-800/60 p-4 rounded-2xl shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="p-2 bg-amber-500 text-white rounded-xl shadow-xs shrink-0">
                        <span class="material-icons text-lg">warning_amber</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-amber-950 dark:text-amber-100">Perhatian: Hari Efektif Belajar Belum Diatur</h4>
                        <p class="text-xs text-amber-800/90 dark:text-amber-300/90 mt-0.5 leading-relaxed">
                            Jumlah hari efektif sekolah untuk bulan ini belum diisi oleh Administrator. Kalkulasi persentase kehadiran menggunakan nilai estimasi.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- === PERINGATAN SISWA BELUM ABSEN (WALI KELAS) === --}}
        @if($currentView === 'wali_kelas' && isset($totalBelumAbsen) && $totalBelumAbsen > 0 && isset($isEffectiveSchoolDay) && $isEffectiveSchoolDay)
            <div x-data="{ showAbsentModal: false }" class="bg-rose-50/90 dark:bg-rose-950/40 border border-rose-300/80 dark:border-rose-800/60 p-4 rounded-2xl shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="p-2 bg-rose-500 text-white rounded-xl shadow-xs shrink-0">
                        <span class="material-icons text-lg">group_off</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-rose-950 dark:text-rose-100">Ada {{ $totalBelumAbsen }} Siswa Belum Melakukan Absensi Hari Ini</h4>
                                <p class="text-xs text-rose-800/90 dark:text-rose-300/90 mt-0.5 leading-relaxed">
                                    Mohon ingatkan siswa-siswa ini atau tandai status kehadiran manual jika berhalangan hadir.
                                </p>
                            </div>
                            <button @click="showAbsentModal = true" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-xs transition-all shrink-0">
                                <span>Lihat Daftar Siswa</span>
                                <span class="material-icons text-sm">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Modal Daftar Siswa Belum Absen --}}
                <div x-show="showAbsentModal" 
                     style="display: none;"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                     
                    <div @click.away="showAbsentModal = false" 
                         class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden transform transition-all">
                         
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-850/50">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-rose-500 text-lg">group_off</span>
                                Daftar Siswa Belum Absen
                            </h3>
                            <button @click="showAbsentModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                                <span class="material-icons text-xl">close</span>
                            </button>
                        </div>
                        
                        <div class="px-6 py-4 max-h-96 overflow-y-auto no-scrollbar divide-y divide-slate-100 dark:divide-slate-800/80">
                            @if(isset($absentStudents) && $absentStudents->count() > 0)
                                @foreach($absentStudents as $student)
                                    <div class="py-3 flex items-center gap-3">
                                        <img class="h-9 w-9 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700 shrink-0"
                                             src="{{ $student->photo_url }}" 
                                             alt="{{ $student->name }}"
                                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                                                {{ $student->name }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                                NIS: {{ $student->nis ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-6 flex flex-col items-center">
                                    <span class="material-icons text-emerald-500 text-4xl mb-2">check_circle</span>
                                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Semua siswa sudah absen hari ini.</p>
                                </div>
                            @endif
                        </div>
                        
                        <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-850/50 flex justify-end border-t border-slate-100 dark:border-slate-800">
                            <button @click="showAbsentModal = false" type="button" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- === KONTEN DINAMIS === --}}
        <div>
            @if($currentView === 'wali_kelas' && $isHomeroomTeacher)
                @include('teacher.partials._dashboard-wali-kelas')
            @elseif($currentView === 'guru_mapel' && $isSubjectTeacher)
                @include('teacher.partials._dashboard-guru-mapel')
            @elseif($currentView === 'fasilitator_kokurikuler' && $isCocurricularFacilitator)
                @include('teacher.partials._dashboard-fasilitator-kokurikuler')
            @elseif($currentView === 'pembina_ekskul' && $isExtracurricularCoach)
                @include('teacher.partials._dashboard-pembina-ekskul')
            @endif
        </div>

    </div>

    @push('scripts')
        @if((!empty($chartLabels)) || (!empty($classPerformanceData)))
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        @endif

        @if($currentView === 'wali_kelas' && !empty($chartLabels))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const isDarkMode = document.documentElement.classList.contains('dark');
                const chartConfig = {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels ?? []),
                        datasets: [{
                            label: 'Kehadiran (%)',
                            data: @json($chartData ?? []),
                            borderColor: isDarkMode ? '#38bdf8' : '#0284c7',
                            backgroundColor: isDarkMode ? 'rgba(56, 189, 248, 0.15)' : 'rgba(2, 132, 199, 0.1)',
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: isDarkMode ? '#38bdf8' : '#0284c7',
                            pointBorderColor: '#ffffff',
                            pointHoverRadius: 6,
                            pointRadius: 4,
                            borderWidth: 2.5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true, max: 100,
                                ticks: { 
                                    callback: (value) => value + '%', 
                                    color: isDarkMode ? '#94a3b8' : '#64748b',
                                    font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                                },
                                grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
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
                                callbacks: { label: (context) => ' Kehadiran: ' + context.parsed.y + '%' } 
                            }
                        }
                    }
                };

                const desktopCanvas = document.getElementById('weeklyAttendanceChart');
                if (desktopCanvas) {
                    new Chart(desktopCanvas.getContext('2d'), JSON.parse(JSON.stringify(chartConfig)));
                }

                const mobileCanvas = document.getElementById('weeklyAttendanceChartMobile');
                if (mobileCanvas) {
                    new Chart(mobileCanvas.getContext('2d'), JSON.parse(JSON.stringify(chartConfig)));
                }
            });
        </script>
        @endif
    @endpush
</x-app-layout>

