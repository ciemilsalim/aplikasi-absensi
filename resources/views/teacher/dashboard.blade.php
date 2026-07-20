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
