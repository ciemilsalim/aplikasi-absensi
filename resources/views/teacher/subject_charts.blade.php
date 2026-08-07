<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Analitik Grafis Kehadiran Mapel') }}
        </h2>
    </x-slot>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    <div class="py-12" x-data="subjectChartAnalytics()" x-init="initData()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Pengaturan Filter Grafik</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Pilihan Kelas & Mata Pelajaran -->
                        <div>
                            <x-input-label value="Kelas" />
                            <select x-model="filters.school_class_id" @change="updateStudents()" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <x-input-label value="Mata Pelajaran" />
                            <select x-model="filters.subject_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($subjects as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label value="Tampilan Tren" />
                            <select x-model="filters.period" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="weekly">Mingguan</option>
                                <option value="monthly">Bulanan</option>
                            </select>
                        </div>

                        <!-- Pilihan Rentang Waktu -->
                        <div>
                            <x-input-label value="Tanggal Mulai" />
                            <input type="date" x-model="filters.start_date" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                        </div>
                        
                        <div>
                            <x-input-label value="Tanggal Selesai" />
                            <input type="date" x-model="filters.end_date" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                        </div>

                        <!-- Target Evaluasi -->
                        <div>
                            <x-input-label value="Target Evaluasi" />
                            <select x-model="filters.target_type" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="class">Seluruh Kelas</option>
                                <option value="student">Siswa Tertentu</option>
                            </select>
                        </div>
                        
                        <div x-show="filters.target_type === 'student'" x-cloak class="lg:col-span-3">
                            <x-input-label value="Pilih Siswa" />
                            <select x-model="filters.student_id" class="mt-1 block w-full lg:w-1/3 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                <option value="all">-- Pilih Siswa --</option>
                                <template x-for="student in currentStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name"></option>
                                </template>
                            </select>
                        </div>

                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="generateChart()" :disabled="isLoading" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                            <span x-text="isLoading ? 'Memproses...' : 'Tampilkan Analitik'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div x-show="errorMsg" x-cloak class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Perhatian!</strong>
                <span class="block sm:inline" x-text="errorMsg"></span>
            </div>

            <!-- Chart Results -->
            <div x-show="hasData" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Pie Chart Summary -->
                <div class="col-span-1 bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 text-center">Komposisi Kehadiran Total</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 text-center">Berdasarkan rentang tanggal terpilih</p>
                    <div class="relative w-full flex justify-center" style="max-width: 300px;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>

                <!-- Bar Chart Timeline -->
                <div class="col-span-1 lg:col-span-2 bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tren Perbandingan Status</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Berdasarkan rentang tanggal terpilih (Tampilan: <span x-text="filters.period === 'weekly' ? 'Mingguan' : 'Bulanan'"></span>)</p>
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
        window.subjectChartAnalytics = function() {
            return {
                studentsMap: @json($studentsMap),
                currentStudents: [],
                filters: {
                    school_class_id: '',
                    subject_id: '',
                    target_type: 'class',
                    student_id: 'all',
                    period: 'weekly',
                    start_date: '{{ now()->startOfMonth()->format('Y-m-d') }}',
                    end_date: '{{ now()->endOfMonth()->format('Y-m-d') }}'
                },
                isLoading: false,
                hasData: false,
                errorMsg: '',
                pieChartInst: null,
                barChartInst: null,

                initData() {
                    // pre-select first class & subject if available
                    const classSelect = document.querySelector('select[x-model="filters.school_class_id"]');
                    if (classSelect && classSelect.options.length > 1) {
                        this.filters.school_class_id = classSelect.options[1].value;
                        this.updateStudents();
                    }
                    const subjectSelect = document.querySelector('select[x-model="filters.subject_id"]');
                    if (subjectSelect && subjectSelect.options.length > 1) {
                        this.filters.subject_id = subjectSelect.options[1].value;
                    }
                },

                updateStudents() {
                    if (this.filters.school_class_id && this.studentsMap[this.filters.school_class_id]) {
                        this.currentStudents = this.studentsMap[this.filters.school_class_id];
                    } else {
                        this.currentStudents = [];
                    }
                    this.filters.student_id = 'all';
                },

                async generateChart() {
                    this.errorMsg = '';
                    
                    if (!this.filters.school_class_id || !this.filters.subject_id) {
                        this.errorMsg = "Silakan pilih kelas dan mata pelajaran.";
                        return;
                    }

                    if (this.filters.target_type === 'student' && this.filters.student_id === 'all') {
                        this.errorMsg = "Silakan pilih siswa.";
                        return;
                    }

                    this.isLoading = true;
                    
                    try {
                        const response = await fetch("{{ route('teacher.subject.attendance.charts.data') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.filters)
                        });

                        if (!response.ok) {
                            const errData = await response.json();
                            throw new Error(errData.error || 'Gagal memuat data dari server.');
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
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const textColor = isDarkMode ? '#e2e8f0' : '#1e293b';
                    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

                    if (this.pieChartInst) this.pieChartInst.destroy();
                    if (this.barChartInst) this.barChartInst.destroy();

                    // Pie Chart
                    const ctxPie = document.getElementById('pieChart').getContext('2d');
                    this.pieChartInst = new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hadir', 'Sakit', 'Izin', 'Alpa/Tanpa Ket.'],
                            datasets: [{
                                data: [
                                    data.summary.hadir,
                                    data.summary.sakit,
                                    data.summary.izin,
                                    data.summary.alpa
                                ],
                                backgroundColor: [
                                    '#22c55e', '#eab308', '#3b82f6', '#ef4444'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: textColor }
                                },
                                tooltip: {
                                    callbacks: { label: (context) => ' ' + context.label + ': ' + context.parsed + '%' }
                                }
                            }
                        }
                    });

                    // Bar Chart
                    const ctxBar = document.getElementById('barChart').getContext('2d');
                    this.barChartInst = new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: data.trendLabels,
                            datasets: [
                                {
                                    label: 'Hadir (%)',
                                    data: data.trendData.hadir,
                                    backgroundColor: '#22c55e',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Sakit (%)',
                                    data: data.trendData.sakit,
                                    backgroundColor: '#eab308',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Izin (%)',
                                    data: data.trendData.izin,
                                    backgroundColor: '#3b82f6',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Alpa (%)',
                                    data: data.trendData.alpa,
                                    backgroundColor: '#ef4444',
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true, max: 100,
                                    ticks: { color: textColor, callback: (val) => val + '%' },
                                    grid: { color: gridColor }
                                },
                                x: {
                                    ticks: { color: textColor },
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: textColor }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: (context) => ' ' + context.dataset.label + ': ' + context.parsed.y + '%'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
