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
            @if($isHomeroomTeacher && $isSubjectTeacher)
                <div class="bg-white dark:bg-slate-800 shadow-sm rounded-lg p-1.5">
                    <div class="flex items-center justify-center space-x-2" role="tablist">
                        <a href="{{ route('teacher.dashboard', ['view' => 'wali_kelas']) }}"
                           class="flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'wali_kelas' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <span class="material-icons text-base mr-2">groups</span>
                            Wali Kelas
                        </a>
                        <a href="{{ route('teacher.dashboard', ['view' => 'guru_mapel']) }}"
                           class="flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'guru_mapel' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <span class="material-icons text-base mr-2">menu_book</span>
                            Guru Mapel
                        </a>
                    </div>
                </div>
            @endif

            {{-- === KONTEN DINAMIS === --}}
            <div class="space-y-6">
                 @if($currentView === 'wali_kelas' && $isHomeroomTeacher)
                    @include('teacher.partials._dashboard-wali-kelas')
                @elseif($currentView === 'guru_mapel' && $isSubjectTeacher)
                    @include('teacher.partials._dashboard-guru-mapel')
                @endif
            </div>

             <!-- ===== FOOTER KONTEN ===== -->
            {{-- <footer class="text-center text-sm text-gray-500 dark:text-gray-400 py-4 mobile-footer lg:hidden">
                © {{ date('Y') }} SIASEK v1.0.0. Dikembangkan oleh zahra.dev.
            </footer> --}}

        </main>
        
        <!-- ===== FLOATING ACTION BUTTON (FAB) ===== -->
        @if($isSubjectTeacher && isset($schedulesToday) && $schedulesToday->isNotEmpty())
        <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedulesToday->first()->id]) }}" class="fixed z-40 right-6 bottom-20 lg:bottom-6 h-14 w-14 bg-sky-500 hover:bg-sky-600 rounded-full flex items-center justify-center text-white shadow-lg transition">
            <span class="material-icons">qr_code_scanner</span>
        </a>
        @endif

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
