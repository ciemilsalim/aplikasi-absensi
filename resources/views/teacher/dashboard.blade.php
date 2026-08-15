{{--
================================================================================================
| File    : resources/views/teacher/dashboard.blade.php
| Deskripsi : Tampilan dasbor guru yang disederhanakan tanpa topbar dan dengan perbaikan grafik.
| Perubahan Terakhir:
|   -   Menghapus bottom navigation bar dari file ini untuk dipindahkan ke layout utama.
|   -   Menyesuaikan padding bawah konten.
================================================================================================
--}}

<x-app-layout>
    {{-- Hapus header bawaan dari layout utama --}}
    <x-slot name="header">
        {{-- Dibiarkan kosong --}}
    </x-slot>

    {{-- Menambahkan dependensi & style custom --}}
    @push('styles')
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body > footer, body > .back-to-top-button { display: none !important; }
        footer.mobile-footer { display: block !important; }
    </style>
    @endpush

    <div class="bg-gray-100 dark:bg-gray-900 flex flex-col font-sans">

        <!-- ===== KONTEN UTAMA ===== -->
        {{-- PERBAIKAN: Padding bawah disesuaikan karena nav bar dipindah --}}
        <main class="flex-grow pt-6 pb-6 px-4 space-y-6">

            {{-- === SWITCHER TAMPILAN === --}}
            @php
                $roleCount = ($isHomeroomTeacher ? 1 : 0) + ($isSubjectTeacher ? 1 : 0) + ($isExtracurricularCoach ? 1 : 0);
            @endphp

            @if($roleCount > 1)
                <div class="bg-white dark:bg-slate-800 shadow-sm rounded-lg p-1.5 overflow-x-auto no-scrollbar">
                    <div class="flex items-center justify-start space-x-2 min-w-max" role="tablist">
                        @if($isHomeroomTeacher)
                        <a href="{{ route('teacher.dashboard', ['view' => 'wali_kelas']) }}"
                           class="flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'wali_kelas' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <span class="material-icons text-base mr-2">groups</span>
                            Wali Kelas
                        </a>
                        @endif

                        @if($isSubjectTeacher)
                        <a href="{{ route('teacher.dashboard', ['view' => 'guru_mapel']) }}"
                           class="flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'guru_mapel' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <span class="material-icons text-base mr-2">menu_book</span>
                            Guru Mapel
                        </a>
                        @endif

                        @if($isExtracurricularCoach)
                        <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}"
                           class="flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'pembina_ekskul' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <span class="material-icons text-base mr-2">military_tech</span>
                            Ekskul
                        </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- === PENGUMUMAN === --}}
            @if(isset($announcements) && $announcements->isNotEmpty())
                <div class="space-y-4">
                    @foreach($announcements as $announcement)
                        <div class="bg-sky-50 dark:bg-sky-900/30 border-l-4 border-sky-500 p-4 rounded-r-xl shadow-sm">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0">
                                    <span class="material-icons text-sky-500">campaign</span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-white">{{ $announcement->title }}</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ $announcement->content }}</p>
                                    <p class="text-[10px] text-sky-600 dark:text-sky-400 mt-2 font-medium">
                                        {{ $announcement->published_at->translatedFormat('d F Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- === PERINGATAN HARI EFEKTIF === --}}
            @if(isset($isEffectiveDaysSet) && !$isEffectiveDaysSet)
                <div class="bg-amber-50 dark:bg-amber-900/30 border-l-4 border-amber-500 p-4 rounded-r-xl shadow-sm">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <span class="material-icons text-amber-500">warning</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-800 dark:text-amber-200">Perhatian: Hari Efektif Belajar Belum Diatur</h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300/80 mt-1">
                                Jumlah hari efektif sekolah untuk bulan ini belum diisi oleh Administrator. Kalkulasi persentase kehadiran pada grafik mungkin menggunakan nilai estimasi.
                            </p>
                            <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-2 font-medium">
                                Silakan hubungi Administrator untuk mengatur Hari Efektif di aplikasi SIPADA (Pangkalan Data).
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- === PERINGATAN SISWA BELUM ABSEN === --}}
            @if($currentView === 'wali_kelas' && isset($totalBelumAbsen) && $totalBelumAbsen > 0 && isset($isEffectiveSchoolDay) && $isEffectiveSchoolDay)
                <div x-data="{ showAbsentModal: false }" class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <span class="material-icons text-red-500">group_off</span>
                        </div>
                        <div class="flex-grow">
                            <h4 class="text-sm font-bold text-red-800 dark:text-red-200">Ada {{ $totalBelumAbsen }} Siswa Belum Absen</h4>
                            <p class="text-xs text-red-700 dark:text-red-300/80 mt-1">
                                Mohon ingatkan siswa-siswa ini untuk segera melakukan presensi hari ini.
                            </p>
                            <button @click="showAbsentModal = true" class="mt-3 text-xs font-semibold text-red-700 hover:text-red-900 dark:text-red-300 dark:hover:text-red-100 underline decoration-red-500/30 underline-offset-4 focus:outline-none transition-colors">
                                Lihat Daftar Siswa &rarr;
                            </button>
                        </div>
                    </div>

                    {{-- Modal Daftar Siswa Belum Absen --}}
                    <div x-show="showAbsentModal" 
                         style="display: none;"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 transition-opacity"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                         
                        <div @click.away="showAbsentModal = false" 
                             class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all"
                             x-transition:enter="ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                             
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <span class="material-icons text-red-500">group_off</span>
                                    Daftar Siswa Belum Absen
                                </h3>
                                <button @click="showAbsentModal = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none transition-colors">
                                    <span class="material-icons">close</span>
                                </button>
                            </div>
                            <div class="px-6 py-4 max-h-96 overflow-y-auto no-scrollbar">
                                @if(isset($absentStudents) && $absentStudents->count() > 0)
                                    <ul class="divide-y divide-gray-100 dark:divide-slate-700/50">
                                        @foreach($absentStudents as $student)
                                            <li class="py-3 flex items-center gap-3">
                                                <img class="flex-shrink-0 h-9 w-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shadow-sm"
                                                     src="{{ $student->photo_url }}" 
                                                     alt="{{ $student->name }}"
                                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=7F9CF5&background=EBF4FF';">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">
                                                        {{ $student->name }}
                                                    </p>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                                        NIS: {{ $student->nis }}
                                                    </p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-center py-6 flex flex-col items-center">
                                        <span class="material-icons text-green-500 text-4xl mb-2">check_circle</span>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Semua siswa sudah absen hari ini.</p>
                                    </div>
                                @endif
                            </div>
                            <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 flex justify-end border-t border-gray-200 dark:border-slate-700">
                                <button @click="showAbsentModal = false" type="button" class="inline-flex justify-center rounded-lg border border-gray-300 dark:border-slate-600 px-4 py-2 bg-white dark:bg-slate-700 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- === KONTEN DINAMIS === --}}
            <div class="space-y-6">
                 @if($currentView === 'wali_kelas' && $isHomeroomTeacher)
                    @include('teacher.partials._dashboard-wali-kelas')
                @elseif($currentView === 'guru_mapel' && $isSubjectTeacher)
                    @include('teacher.partials._dashboard-guru-mapel')
                @elseif($currentView === 'pembina_ekskul' && $isExtracurricularCoach)
                    @include('teacher.partials._dashboard-pembina-ekskul')
                @endif
            </div>

             <!-- ===== FOOTER KONTEN ===== -->
            {{-- <footer class="text-center text-sm text-gray-500 dark:text-gray-400 py-4 mobile-footer lg:hidden">
                © {{ date('Y') }} SIASEK v1.0.0. Dikembangkan oleh zahra.dev.
            </footer> --}}

        </main>
        


    </div>

    @push('scripts')
        {{-- Memuat Chart.js jika salah satu data grafik ada --}}
        @if((!empty($chartLabels)) || (!empty($classPerformanceData)))
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        @endif

        @if($currentView === 'wali_kelas' && !empty($chartLabels))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (document.getElementById('weeklyAttendanceChart')) {
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const ctx = document.getElementById('weeklyAttendanceChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($chartLabels ?? []),
                            datasets: [{
                                label: 'Kehadiran (%)',
                                data: @json($chartData ?? []),
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                fill: true,
                                tension: 0.4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true, max: 100,
                                    ticks: { callback: (value) => value + '%', color: isDarkMode ? '#94a3b8' : '#64748b' },
                                    grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' }
                                },
                                x: {
                                     ticks: { color: isDarkMode ? '#94a3b8' : '#64748b' },
                                     grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (context) => ' Kehadiran: ' + context.parsed.y + '%' } }
                            }
                        }
                    });
                }
            });
        </script>
        @endif
    @endpush
</x-app-layout>
