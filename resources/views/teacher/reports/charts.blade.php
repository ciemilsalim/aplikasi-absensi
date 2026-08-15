<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Wali Kelas', 'url' => route('teacher.dashboard', ['view' => 'wali_kelas'])],
                    ['title' => 'Analitik Kelas', 'url' => route('teacher.attendance.charts')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Analitik Kehadiran - Kelas {{ $class->name }}
                </h1>
            </div>

            <a href="{{ route('teacher.attendance.history') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shrink-0">
                <span class="material-icons text-base text-slate-500">grid_on</span>
                <span>Matriks Presensi Harian</span>
            </a>
        </div>
    </x-slot>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    <div class="space-y-6" x-data="chartAnalytics()" x-init="initData()">
        
        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-icons text-indigo-500 text-lg">tune</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Parameter Analisis Kelas</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Target</label>
                    <select x-model="filters.target_type" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="class">Keseluruhan Kelas</option>
                        <option value="student">Per Siswa</option>
                    </select>
                </div>

                <div x-show="filters.target_type === 'student'" x-cloak>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Pilih Siswa</label>
                    <select x-model="filters.student_id" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">-- Pilih Siswa --</option>
                        <template x-for="student in allStudents" :key="student.id">
                            <option :value="student.id" x-text="student.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Rentang Waktu</label>
                    <select x-model="filters.period_type" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="month">Bulanan</option>
                        <option value="trimester">Triwulan (3 Bulan)</option>
                        <option value="semester">Semester (6 Bulan)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Tahun</label>
                    <select x-model="filters.year" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @for($y = date('Y') - 3; $y <= date('Y') + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Detail Periode</label>
                    <select x-model="filters.period_value" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <template x-for="opt in periodOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button @click="generateChart()" :disabled="isLoading" 
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50">
                    <span class="material-icons text-base" x-show="!isLoading">insights</span>
                    <span x-text="isLoading ? 'Memproses...' : 'Tampilkan Analitik'"></span>
                </button>
            </div>
        </div>

        <!-- Error State -->
        <div x-show="errorMsg" x-cloak 
             class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2" role="alert">
            <span class="material-icons text-base">error_outline</span>
            <span x-text="errorMsg"></span>
        </div>

        <!-- Chart Results -->
        <div x-show="hasData" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Pie Chart Summary -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6 flex flex-col items-center justify-center">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 text-center">Komposisi Total Kehadiran</h3>
                <div class="relative w-full aspect-square max-w-[280px] flex items-center justify-center">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart Timeline -->
            <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Tren Perbandingan Status</h3>
                <div class="relative w-full h-80">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.chartAnalytics = function() {
            return {
                allStudents: @json($students),
                filters: {
                    target_type: 'class',
                    student_id: '',
                    period_type: 'month',
                    year: new Date().getFullYear(),
                    period_value: new Date().getMonth() + 1
                },
                isLoading: false,
                hasData: false,
                errorMsg: '',
                pieChartInst: null,
                barChartInst: null,

                get periodOptions() {
                    if (this.filters.period_type === 'month') {
                        return [
                            {value: 1, label: 'Januari'}, {value: 2, label: 'Februari'},
                            {value: 3, label: 'Maret'}, {value: 4, label: 'April'},
                            {value: 5, label: 'Mei'}, {value: 6, label: 'Juni'},
                            {value: 7, label: 'Juli'}, {value: 8, label: 'Agustus'},
                            {value: 9, label: 'September'}, {value: 10, label: 'Oktober'},
                            {value: 11, label: 'November'}, {value: 12, label: 'Desember'}
                        ];
                    } else if (this.filters.period_type === 'trimester') {
                        return [
                            {value: 1, label: 'Triwulan 1 (Jan-Mar)'},
                            {value: 2, label: 'Triwulan 2 (Apr-Jun)'},
                            {value: 3, label: 'Triwulan 3 (Jul-Sep)'},
                            {value: 4, label: 'Triwulan 4 (Okt-Des)'}
                        ];
                    } else {
                        return [
                            {value: 1, label: 'Semester Ganjil (Jul-Des)'},
                            {value: 2, label: 'Semester Genap (Jan-Jun)'}
                        ];
                    }
                },

                initData() {
                    if (this.allStudents.length > 0) {
                        this.filters.student_id = this.allStudents[0].id;
                    }
                    
                    this.$watch('filters.period_type', (val) => {
                        this.filters.period_value = 1;
                    });
                },

                async generateChart() {
                    this.errorMsg = '';
                    
                    if (this.filters.target_type === 'student' && !this.filters.student_id) {
                        this.errorMsg = "Silakan pilih siswa terlebih dahulu.";
                        return;
                    }

                    this.isLoading = true;
                    
                    try {
                        const response = await fetch("{{ route('teacher.attendance.charts.data') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                target_type: this.filters.target_type,
                                period_type: this.filters.period_type,
                                year: this.filters.year,
                                period_value: this.filters.period_value,
                                student_id: this.filters.student_id
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Gagal memuat data dari server.');
                        }

                        const data = await response.json();
                        this.renderCharts(data);
                        this.hasData = true;

                    } catch (error) {
                        this.errorMsg = error.message;
                        this.hasData = false;
                    } finally {
                        this.isLoading = false;
                    }
                },

                renderCharts(data) {
                    const ctxPie = document.getElementById('pieChart').getContext('2d');
                    const ctxBar = document.getElementById('barChart').getContext('2d');

                    if (this.pieChartInst) this.pieChartInst.destroy();
                    if (this.barChartInst) this.barChartInst.destroy();

                    const colors = {
                        hadir: '#0284c7', // sky-600
                        sakit: '#f59e0b', // amber-500
                        izin: '#8b5cf6',  // purple-500
                        alpa: '#ef4444'   // rose-500
                    };

                    const pieData = [
                        data.summary.hadir,
                        data.summary.sakit,
                        data.summary.izin,
                        data.summary.alpa
                    ];
                    
                    this.pieChartInst = new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hadir', 'Sakit', 'Izin', 'Alpa'],
                            datasets: [{
                                data: pieData,
                                backgroundColor: Object.values(colors),
                                borderWidth: 0,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let val = context.parsed;
                                            return context.label + ': ' + (val ? val.toFixed(1) : 0) + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });

                    const barDatasets = [
                        { label: 'Hadir', data: data.monthly.map(m => m.hadir), backgroundColor: colors.hadir, borderRadius: 6 },
                        { label: 'Sakit', data: data.monthly.map(m => m.sakit), backgroundColor: colors.sakit, borderRadius: 6 },
                        { label: 'Izin', data: data.monthly.map(m => m.izin), backgroundColor: colors.izin, borderRadius: 6 },
                        { label: 'Alpa', data: data.monthly.map(m => m.alpa), backgroundColor: colors.alpa, borderRadius: 6 }
                    ];

                    this.barChartInst = new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: barDatasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: { grid: { display: false } },
                                y: { 
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: function(value) {
                                            return value + "%";
                                        }
                                    },
                                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                                }
                            },
                            plugins: {
                                legend: { position: 'top', labels: { boxWidth: 12, padding: 12 } },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>

