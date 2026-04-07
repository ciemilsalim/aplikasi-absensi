<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Laporan Analitik Kehadiran (Chart) - Kelas ') }} {{ $class->name }}
        </h2>
    </x-slot>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    <div class="py-12" x-data="chartAnalytics()" x-init="initData()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Pengaturan Filter</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <div>
                            <x-input-label value="Target Evaluasi" />
                            <select x-model="filters.target_type" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="class">Keseluruhan Kelas</option>
                                <option value="student">Per Siswa</option>
                            </select>
                        </div>

                        <div x-show="filters.target_type === 'student'" x-cloak>
                            <x-input-label value="Siswa" />
                            <select x-model="filters.student_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="">-- Pilih Siswa --</option>
                                <template x-for="student in allStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <x-input-label value="Periode Waktu" />
                            <select x-model="filters.period_type" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="month">Bulanan</option>
                                <option value="trimester">Triwulan (3 Bulan)</option>
                                <option value="semester">Semester (6 Bulan)</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label value="Tahun" />
                            <select x-model="filters.year" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                @for($y = date('Y') - 3; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <x-input-label value="Pilih Detail Periode" />
                            <select x-model="filters.period_value" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <template x-if="filters.period_type === 'month'">
                                    <optgroup label="Bulan">
                                        <option value="1">Januari</option>
                                        <option value="2">Februari</option>
                                        <option value="3">Maret</option>
                                        <option value="4">April</option>
                                        <option value="5">Mei</option>
                                        <option value="6">Juni</option>
                                        <option value="7">Juli</option>
                                        <option value="8">Agustus</option>
                                        <option value="9">September</option>
                                        <option value="10">Oktober</option>
                                        <option value="11">November</option>
                                        <option value="12">Desember</option>
                                    </optgroup>
                                </template>
                                <template x-if="filters.period_type === 'trimester'">
                                    <optgroup label="Triwulan">
                                        <option value="1">Triwulan 1 (Jan-Mar)</option>
                                        <option value="2">Triwulan 2 (Apr-Jun)</option>
                                        <option value="3">Triwulan 3 (Jul-Sep)</option>
                                        <option value="4">Triwulan 4 (Okt-Des)</option>
                                    </optgroup>
                                </template>
                                <template x-if="filters.period_type === 'semester'">
                                    <optgroup label="Semester">
                                        <option value="1">Semester Ganjil (Jul-Des)</option>
                                        <option value="2">Semester Genap (Jan-Jun)</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button @click="generateChart()" :disabled="isLoading" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                            <span x-show="!isLoading">Tampilkan Analitik</span>
                            <span x-show="isLoading">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="errorMsg" x-cloak class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline" x-text="errorMsg"></span>
            </div>

            <!-- Chart Results -->
            <div x-show="hasData" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Pie Chart Summary -->
                <div class="col-span-1 bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 text-center">Komposisi Total Kehadiran</h3>
                    <div class="relative w-full" style="max-width: 300px;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>

                <!-- Bar Chart Timeline -->
                <div class="col-span-1 lg:col-span-2 bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tren Perbandingan Status</h3>
                    <div class="relative w-full h-72">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chartAnalytics', () => ({
                allStudents: @json($students), // Semua murid yg akan difilter local
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

                initData() {
                    // pre-select first student if available
                    if (this.allStudents.length > 0) {
                        this.filters.student_id = this.allStudents[0].id;
                    }
                    
                    // Reset period value when period type changes
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
                        const response = await fetch("{{ route('attendance.charts.data') }}", {
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

                    // Colors setup
                    const colors = {
                        hadir: 'rgba(59, 130, 246, 0.8)', // blue
                        sakit: 'rgba(234, 179, 8, 0.8)', // yellow
                        izin: 'rgba(168, 85, 247, 0.8)', // purple
                        alpa: 'rgba(239, 68, 68, 0.8)' // red
                    };

                    // Render Pie
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
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });

                    // Render Bar
                    const barDatasets = [
                        { label: 'Hadir', data: data.monthly.map(m => m.hadir), backgroundColor: colors.hadir },
                        { label: 'Sakit', data: data.monthly.map(m => m.sakit), backgroundColor: colors.sakit },
                        { label: 'Izin', data: data.monthly.map(m => m.izin), backgroundColor: colors.izin },
                        { label: 'Alpa', data: data.monthly.map(m => m.alpa), backgroundColor: colors.alpa }
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
                                x: { stacked: false },
                                y: { stacked: false, beginAtZero: true }
                            }
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
