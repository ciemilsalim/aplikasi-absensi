<x-app-layout>
    <x-slot name="header">
        {{-- Breadcrumb untuk navigasi --}}
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{-- Judul dinamis berdasarkan view --}}
            @if($currentView === 'wali_kelas')
                {{ __('Dasbor Wali Kelas') }}
            @else
                {{ __('Dasbor Guru Mata Pelajaran') }}
            @endif
        </h2>
    </x-slot>

    <div class="py-6 md:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- === SWITCHER TAMPILAN === --}}
            @if($isHomeroomTeacher && $isSubjectTeacher)
                <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-2">
                    <div class="flex items-center justify-center space-x-2" role="tablist">
                        {{-- Tombol View Wali Kelas --}}
                        <a href="{{ route('teacher.dashboard', ['view' => 'wali_kelas']) }}"
                           class="flex-1 text-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'wali_kelas' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <i class="fas fa-users mr-2"></i> <!-- Font Awesome icon -->
                            Wali Kelas
                        </a>
                        
                        {{-- Tombol View Guru Mapel --}}
                        <a href="{{ route('teacher.dashboard', ['view' => 'guru_mapel']) }}"
                           class="flex-1 text-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                  {{ $currentView === 'guru_mapel' ? 'bg-sky-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }}">
                            <i class="fas fa-book-open mr-2"></i> <!-- Font Awesome icon -->
                            Guru Mapel
                        </a>
                    </div>
                </div>
            @endif

            {{-- === KONTEN DINAMIS === --}}
            @if($currentView === 'wali_kelas')
                {{-- Memanggil view khusus untuk Wali Kelas --}}
                @include('teacher.partials._dashboard-wali-kelas')
            @else
                {{-- Memanggil view khusus untuk Guru Mapel --}}
                @include('teacher.partials._dashboard-guru-mapel')
            @endif

        </div>
    </div>

    {{-- Script Chart.js hanya di-load jika di view wali kelas --}}
    @if($currentView === 'wali_kelas')
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
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
            });
        </script>
        @endpush
    @endif
</x-app-layout>
