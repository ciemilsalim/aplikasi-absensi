<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')],
                    ['title' => 'Laporan & Rekap', 'url' => route('teacher.subject.attendance.report')],
                    ['title' => 'Analitik Grafis', 'url' => route('teacher.subject.attendance.charts')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Analitik Visual Kehadiran Sesi Pembelajaran & Kokurikuler
                </h1>
            </div>

            <a href="{{ route('teacher.subject.attendance.report') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shrink-0">
                <span class="material-icons text-base text-slate-500">table_chart</span>
                <span>Matriks Rekap Tabel</span>
            </a>
        </div>
    </x-slot>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    <div class="space-y-6" x-data="subjectChartAnalytics()" x-init="initData()">
        
        <!-- Filter Controls Container -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-icons text-indigo-500 text-lg">tune</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Parameter Analisis</h3>
            </div>

            <!-- Tipe Kegiatan Switcher Tabs -->
            <div class="mb-4 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl flex items-center gap-1 max-w-md">
                <button type="button" 
                        @click="filters.activity_type = 'regular'; updateStudents()" 
                        :class="filters.activity_type === 'regular' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="flex-1 py-2 px-3 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm">menu_book</span>
                    <span>Mata Pelajaran</span>
                </button>
                <button type="button" 
                        @click="filters.activity_type = 'cocurricular'; updateStudents()" 
                        :class="filters.activity_type === 'cocurricular' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="flex-1 py-2 px-3 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm">psychology</span>
                    <span>Proyek Kokurikuler</span>
                </button>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">
                
                <!-- Pilihan Kelas -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Kelas</label>
                    <select x-model="filters.school_class_id" @change="updateStudents()" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">-- Pilih Kelas --</option>
                        <template x-if="filters.activity_type === 'regular'">
                            <template x-for="(name, id) in regularClasses" :key="id">
                                <option :value="id" x-text="name"></option>
                            </template>
                        </template>
                        <template x-if="filters.activity_type === 'cocurricular'">
                            <template x-for="(name, id) in cocurricularClasses" :key="id">
                                <option :value="id" x-text="name"></option>
                            </template>
                        </template>
                    </select>
                </div>
                
                <!-- Pilihan Mapel (Regular) -->
                <div x-show="filters.activity_type === 'regular'">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Mata Pelajaran</label>
                    <select x-model="filters.subject_id" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">-- Pilih Mapel --</option>
                        @foreach($subjects as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pilihan Proyek Kokurikuler -->
                <div x-show="filters.activity_type === 'cocurricular'" style="display: none;">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Proyek Kokurikuler</label>
                    <select x-model="filters.cocurricular_id" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500/15">
                        <option value="">-- Pilih Proyek --</option>
                        @foreach($cocurricularProjects as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tampilan Tren -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Agregasi</label>
                    <select x-model="filters.period" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="weekly">Mingguan</option>
                        <option value="monthly">Bulanan</option>
                    </select>
                </div>

                <!-- Pilihan Rentang Waktu -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Dari Tanggal</label>
                    <input type="date" x-model="filters.start_date" 
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Sampai Tanggal</label>
                    <input type="date" x-model="filters.end_date" 
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                </div>

                <!-- Target Evaluasi -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Target</label>
                    <select x-model="filters.target_type" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="class">Seluruh Kelas</option>
                        <option value="student">Siswa Tertentu</option>
                    </select>
                </div>
            </div>

            <!-- Pilih Siswa (Conditional) -->
            <div x-show="filters.target_type === 'student'" x-cloak class="mt-3.5 pt-3.5 border-t border-slate-100 dark:border-slate-800">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Pilih Siswa Spesifik</label>
                <select x-model="filters.student_id" 
                        class="w-full sm:w-1/2 md:w-1/3 text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                    <option value="all">-- Pilih Siswa --</option>
                    <template x-for="student in currentStudents" :key="student.id">
                        <option :value="student.id" x-text="student.name"></option>
                    </template>
                </select>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button @click="generateChart()" :disabled="isLoading" 
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50">
                    <span class="material-icons text-base" x-show="!isLoading">insights</span>
                    <span x-text="isLoading ? 'Memproses Data...' : 'Tampilkan Analitik'"></span>
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
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1 text-center">Komposisi Kehadiran Total</h3>
                <p class="text-[11px] text-slate-400 mb-4 text-center">Rata-rata persentase periode terpilih</p>
                <div class="relative w-full aspect-square max-w-[280px] flex items-center justify-center">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart Timeline -->
            <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Tren Perbandingan Status</h3>
                <p class="text-[11px] text-slate-400 mb-4">Visualisasi tren (Tampilan: <span class="font-bold text-slate-700 dark:text-slate-300" x-text="filters.period === 'weekly' ? 'Mingguan' : 'Bulanan'"></span>)</p>
                <div class="relative w-full h-80">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.subjectChartAnalytics = function() {
            return {
                regularClasses: @json($classes),
                cocurricularClasses: @json($cocurricularClasses ?? []),
                studentsMap: @json($studentsMap),
                currentStudents: [],
                filters: {
                    activity_type: 'regular',
                    school_class_id: '',
                    subject_id: '',
                    cocurricular_id: '',
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
                    const classKeys = Object.keys(this.regularClasses);
                    if (classKeys.length > 0) {
                        this.filters.school_class_id = classKeys[0];
                        this.updateStudents();
                    }
                    const subjectSelect = document.querySelector('select[x-model="filters.subject_id"]');
                    if (subjectSelect && subjectSelect.options.length > 1) {
                        this.filters.subject_id = subjectSelect.options[1].value;
                    }
                    const cocurricularSelect = document.querySelector('select[x-model="filters.cocurricular_id"]');
                    if (cocurricularSelect && cocurricularSelect.options.length > 1) {
                        this.filters.cocurricular_id = cocurricularSelect.options[1].value;
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
                    
                    if (this.filters.activity_type === 'regular' && (!this.filters.school_class_id || !this.filters.subject_id)) {
                        this.errorMsg = "Silakan pilih kelas dan mata pelajaran.";
                        return;
                    }

                    if (this.filters.activity_type === 'cocurricular' && (!this.filters.school_class_id || !this.filters.cocurricular_id)) {
                        this.errorMsg = "Silakan pilih kelas dan proyek kokurikuler.";
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
                    if (this.pieChartInst) this.pieChartInst.destroy();
                    if (this.barChartInst) this.barChartInst.destroy();

                    const colors = {
                        hadir: '#0284c7', // sky-600
                        sakit: '#f59e0b', // amber-500
                        izin: '#8b5cf6',  // purple-500
                        alpa: '#ef4444'   // rose-500
                    };

                    // Pie Chart
                    const ctxPie = document.getElementById('pieChart').getContext('2d');
                    this.pieChartInst = new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hadir', 'Sakit', 'Izin', 'Alpa / Tanpa Ket.'],
                            datasets: [{
                                data: [
                                    data.summary.hadir,
                                    data.summary.sakit,
                                    data.summary.izin,
                                    data.summary.alpa
                                ],
                                backgroundColor: Object.values(colors),
                                borderWidth: 0,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 12, padding: 14 }
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
                                    backgroundColor: colors.hadir,
                                    borderRadius: 4
                                },
                                {
                                    label: 'Sakit (%)',
                                    data: data.trendData.sakit,
                                    backgroundColor: colors.sakit,
                                    borderRadius: 4
                                },
                                {
                                    label: 'Izin (%)',
                                    data: data.trendData.izin,
                                    backgroundColor: colors.izin,
                                    borderRadius: 4
                                },
                                {
                                    label: 'Alpa (%)',
                                    data: data.trendData.alpa,
                                    backgroundColor: colors.alpa,
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
                                    ticks: { callback: (val) => val + '%' },
                                    grid: { color: 'rgba(148, 163, 184, 0.1)' }
                                },
                                x: {
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { boxWidth: 12, padding: 12 }
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
