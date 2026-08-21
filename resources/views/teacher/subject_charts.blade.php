<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')],
                    ['title' => 'Rekap Presensi', 'url' => route('teacher.subject.attendance.report')],
                    ['title' => 'Analitik Kehadiran', 'url' => route('teacher.subject.attendance.charts')]
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Analitik Kehadiran
                    </h1>
                </div>
            </div>

            <!-- Header Quick Action to Rekap Presensi -->
            <a href="{{ route('teacher.subject.attendance.report') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors shadow-2xs shrink-0">
                <span class="material-icons text-sm text-sky-500">table_chart</span>
                <span>Rekap Presensi</span>
            </a>
        </div>
    </x-slot>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    <div class="space-y-5 sm:space-y-6 pb-32 sm:pb-12" x-data="subjectChartAnalytics()" x-init="initData()">
        
        <!-- Segment Switcher: Mata Pelajaran vs Kokurikuler -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" 
                        @click="switchActivityType('regular')" 
                        :class="filters.activity_type === 'regular' ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 font-semibold border-transparent'" 
                        class="py-2.5 px-3 rounded-2xl text-xs transition-all flex items-center justify-center gap-2 border">
                    <span class="material-icons text-base text-sky-500">menu_book</span>
                    <span>Mata Pelajaran</span>
                </button>
                <button type="button" 
                        @click="switchActivityType('cocurricular')" 
                        :class="filters.activity_type === 'cocurricular' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 font-semibold border-transparent'" 
                        class="py-2.5 px-3 rounded-2xl text-xs transition-all flex items-center justify-center gap-2 border">
                    <span class="material-icons text-base text-indigo-500">psychology</span>
                    <span>Kokurikuler</span>
                </button>
            </div>
        </div>

        <!-- Contextual Filter Parameters Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-4 sm:p-6">
            <div class="flex items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sky-500 text-lg">tune</span>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                        <span x-text="filters.activity_type === 'regular' ? 'Parameter Analitik Mata Pelajaran' : 'Parameter Analitik Kokurikuler'"></span>
                    </h3>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Sesuaikan cakupan data</span>
            </div>

            <!-- Form Fields Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4">
                
                <!-- Field 1: Kelas / Rombel -->
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
                
                <!-- Field 2A: Mata Pelajaran (Khusus Regular) -->
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

                <!-- Field 2B: Proyek / Kegiatan (Khusus Kokurikuler) -->
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

                <!-- Field 3: Periode Tanggal (Dari & Sampai) -->
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

                <!-- Field 4: Agregasi & Target -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Agregasi</label>
                        <select x-model="filters.period" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Target</label>
                        <select x-model="filters.target_percentage" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                            <option value="75">&ge; 75%</option>
                            <option value="80" selected>&ge; 80%</option>
                            <option value="85">&ge; 85%</option>
                            <option value="90">&ge; 90%</option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Opsi Target Evaluasi: Seluruh Kelas vs Siswa Tertentu -->
            <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="radio" x-model="filters.target_type" value="class" class="text-sky-600 focus:ring-sky-500">
                        <span>Seluruh Rombel</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="radio" x-model="filters.target_type" value="student" class="text-sky-600 focus:ring-sky-500">
                        <span>Siswa Spesifik</span>
                    </label>
                </div>

                <!-- Dropdown Siswa jika Siswa Spesifik dipilih -->
                <div x-show="filters.target_type === 'student'" x-cloak class="w-full sm:w-64">
                    <select x-model="filters.student_id" 
                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="all">-- Pilih Siswa --</option>
                        <template x-for="student in currentStudents" :key="student.id">
                            <option :value="student.id" x-text="student.name"></option>
                        </template>
                    </select>
                </div>

                <button @click="generateChart()" :disabled="isLoading" 
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 self-end sm:self-auto w-full sm:w-auto">
                    <span class="material-icons text-base" x-show="!isLoading">insights</span>
                    <span class="material-icons text-base animate-spin" x-show="isLoading">sync</span>
                    <span x-text="isLoading ? 'Menganalisis Data...' : 'Tampilkan Analitik'"></span>
                </button>
            </div>
        </div>

        <!-- ================= EMPTY STATE (Clean, no error on fresh page load) ================= -->
        <div x-show="emptyStateMsg" x-cloak 
             class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <span class="material-icons text-2xl">event_busy</span>
            </div>
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">
                Belum Ada Sesi Pertemuan
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto" x-text="emptyStateMsg"></p>
        </div>

        <!-- Error State -->
        <div x-show="errorMsg" x-cloak 
             class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2" role="alert">
            <span class="material-icons text-base">error_outline</span>
            <span x-text="errorMsg"></span>
        </div>

        <!-- ================= INSIGHT-FIRST ANALYTICS RESULTS ================= -->
        <div x-show="hasData" x-cloak class="space-y-5 sm:space-y-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
            
            <!-- 4 Top KPI Analytics Insight Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                
                <!-- KPI 1: Rata-rata Kehadiran vs Target (Focal Point) -->
                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Kehadiran</span>
                        <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <span class="material-icons text-base">pie_chart</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight" x-text="metrics.class_avg_percent + '%'"></span>
                            
                            <!-- Target Indicator Badge -->
                            <template x-if="metrics.class_avg_percent >= metrics.target_percent">
                                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    &ge; Target (<span x-text="metrics.target_percent + '%'"></span>)
                                </span>
                            </template>
                            <template x-if="metrics.class_avg_percent < metrics.target_percent">
                                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                    &darr; <span x-text="(metrics.target_percent - metrics.class_avg_percent).toFixed(1) + '%'"></span>
                                </span>
                            </template>
                        </div>
                        
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-sky-500 to-indigo-500 h-full rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, Math.max(0, metrics.class_avg_percent)) + '%'"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                            <span x-text="metrics.total_hadir"></span> kehadiran tercatat
                        </p>
                    </div>
                </div>

                <!-- KPI 2: Siswa & Sesi -->
                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Siswa & Sesi</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <span class="material-icons text-base">groups</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            <span x-text="metrics.total_students"></span> <span class="text-xs font-bold text-slate-500">Siswa</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                            <strong class="text-slate-700 dark:text-slate-200" x-text="metrics.total_sessions"></strong> Sesi Pertemuan Terjadwal
                        </p>
                    </div>
                </div>

                <!-- KPI 3: Sakit & Izin -->
                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sakit & Izin</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <span class="material-icons text-base">medical_services</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight">
                            <span x-text="metrics.total_sakit + metrics.total_izin"></span> <span class="text-xs font-bold text-slate-500">kejadian</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                            Sakit <strong class="text-slate-700 dark:text-slate-200" x-text="metrics.total_sakit"></strong> &bull; Izin <strong class="text-slate-700 dark:text-slate-200" x-text="metrics.total_izin"></strong>
                        </p>
                    </div>
                </div>

                <!-- KPI 4: Alpa & Bolos -->
                <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alpa & Bolos</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <span class="material-icons text-base">person_off</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="text-xl sm:text-2xl font-black" :class="metrics.total_alpa > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">
                            <span x-text="metrics.total_alpa"></span> <span class="text-xs font-bold text-slate-500">kejadian</span>
                        </div>
                        <p class="text-[11px] mt-1 font-medium" :class="metrics.total_alpa > 0 ? 'text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400'">
                            Perlu pemantauan wali kelas
                        </p>
                    </div>
                </div>

            </div>

            <!-- Actionable Section: Siswa Perlu Perhatian (< Target Kehadiran) -->
            <template x-if="studentsBelowTarget && studentsBelowTarget.length > 0">
                <div class="bg-amber-50/70 dark:bg-amber-950/30 rounded-3xl border border-amber-200/70 dark:border-amber-900/40 p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-amber-600 dark:text-amber-400 text-lg">warning_amber</span>
                            <h4 class="text-xs sm:text-sm font-bold text-amber-900 dark:text-amber-200">
                                <span x-text="studentsBelowTarget.length"></span> Siswa di Bawah Target Kehadiran (&lt; <span x-text="metrics.target_percent + '%'"></span>)
                            </h4>
                        </div>
                        <span class="text-[10px] font-bold text-amber-700 dark:text-amber-300">Perlu Tindak Lanjut</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        <template x-for="st in studentsBelowTarget" :key="st.id">
                            <div class="p-3 bg-white dark:bg-slate-900 rounded-2xl border border-amber-200/50 dark:border-amber-900/30 flex items-center justify-between gap-2 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-900 dark:text-white truncate" x-text="st.name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'NIS: ' + st.nis + ' • Hadir ' + st.hadir + ' sesi'"></div>
                                </div>
                                <span class="px-2 py-1 rounded-xl text-xs font-black bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shrink-0" x-text="st.percent + '%'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Chart Visualizations (Doughnut Composition + Temporal Bar Trend) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">
                <!-- Doughnut Chart: Komposisi Kehadiran Total -->
                <div class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 flex flex-col items-center justify-center">
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mb-0.5 text-center">Komposisi Kehadiran</h3>
                    <p class="text-[10px] text-slate-400 mb-4 text-center">Persentase status pada periode terpilih</p>
                    <div class="relative w-full aspect-square max-w-[260px] flex items-center justify-center">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>

                <!-- Bar Chart: Tren Waktu -->
                <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-0.5">
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Tren Kehadiran Temporal</h3>
                            <span class="text-[10px] font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/60 px-2 py-0.5 rounded-lg border border-sky-100 dark:border-sky-900" x-text="filters.period === 'weekly' ? 'Agregasi Mingguan' : 'Agregasi Bulanan'"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mb-4">Grafik perkembangan tingkat kehadiran antar-sesi</p>
                    </div>
                    <div class="relative w-full h-72">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Direct Navigation to Detailed Rekap Presensi Matrix -->
            <div class="bg-gradient-to-r from-sky-50 to-indigo-50 dark:from-sky-950/30 dark:to-indigo-950/30 rounded-3xl border border-sky-100 dark:border-sky-900/40 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                        Ingin Memeriksa Data Presensi Harian / Siswa?
                    </h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Lihat ringkasan per siswa atau matriks presensi lengkap tanggal per tanggal.
                    </p>
                </div>

                <a :href="getRekapUrl()" 
                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition-all shrink-0">
                    <span>Lihat Rekap Presensi</span>
                    <span class="material-icons text-sm">arrow_forward</span>
                </a>
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
                    activity_type: '{{ request('type') === 'cocurricular' ? 'cocurricular' : 'regular' }}',
                    school_class_id: '',
                    subject_id: '',
                    cocurricular_id: '',
                    target_type: 'class',
                    student_id: 'all',
                    period: 'weekly',
                    target_percentage: '80',
                    start_date: '{{ now()->startOfMonth()->format('Y-m-d') }}',
                    end_date: '{{ now()->endOfMonth()->format('Y-m-d') }}'
                },
                isLoading: false,
                hasData: false,
                emptyStateMsg: '',
                errorMsg: '',
                metrics: {
                    total_hadir: 0,
                    total_sakit: 0,
                    total_izin: 0,
                    total_alpa: 0,
                    total_students: 0,
                    total_sessions: 0,
                    class_avg_percent: 0,
                    target_percent: 80
                },
                studentsBelowTarget: [],
                pieChartInst: null,
                barChartInst: null,

                initData() {
                    this.setupInitialSelections();
                    // Jalankan analitik otomatis saat halaman dimuat
                    this.generateChart();
                },

                switchActivityType(type) {
                    this.filters.activity_type = type;
                    this.setupInitialSelections();
                    this.generateChart();
                },

                setupInitialSelections() {
                    if (this.filters.activity_type === 'regular') {
                        const classKeys = Object.keys(this.regularClasses);
                        if (classKeys.length > 0 && !this.regularClasses[this.filters.school_class_id]) {
                            this.filters.school_class_id = classKeys[0];
                        }
                        const subjectSelect = document.querySelector('select[x-model="filters.subject_id"]');
                        if (subjectSelect && subjectSelect.options.length > 1 && !this.filters.subject_id) {
                            this.filters.subject_id = subjectSelect.options[1].value;
                        }
                    } else {
                        const cocurricularClassKeys = Object.keys(this.cocurricularClasses);
                        if (cocurricularClassKeys.length > 0 && !this.cocurricularClasses[this.filters.school_class_id]) {
                            this.filters.school_class_id = cocurricularClassKeys[0];
                        }
                        const cocurricularSelect = document.querySelector('select[x-model="filters.cocurricular_id"]');
                        if (cocurricularSelect && cocurricularSelect.options.length > 1 && !this.filters.cocurricular_id) {
                            this.filters.cocurricular_id = cocurricularSelect.options[1].value;
                        }
                    }
                    this.updateStudents();
                },

                updateStudents() {
                    if (this.filters.school_class_id && this.studentsMap[this.filters.school_class_id]) {
                        this.currentStudents = this.studentsMap[this.filters.school_class_id];
                    } else {
                        this.currentStudents = [];
                    }
                    this.filters.student_id = 'all';
                },

                getRekapUrl() {
                    const params = new URLSearchParams({
                        activity_type: this.filters.activity_type,
                        school_class_id: this.filters.school_class_id,
                        start_date: this.filters.start_date,
                        end_date: this.filters.end_date
                    });
                    if (this.filters.activity_type === 'regular') {
                        params.append('subject_id', this.filters.subject_id);
                    } else {
                        params.append('cocurricular_id', this.filters.cocurricular_id);
                    }
                    return `{{ route('teacher.subject.attendance.preview') }}?${params.toString()}`;
                },

                async generateChart() {
                    this.errorMsg = '';
                    this.emptyStateMsg = '';
                    
                    if (this.filters.activity_type === 'regular' && (!this.filters.school_class_id || !this.filters.subject_id)) {
                        this.emptyStateMsg = "Silakan pilih kelas dan mata pelajaran untuk menampilkan data analitik.";
                        this.hasData = false;
                        return;
                    }

                    if (this.filters.activity_type === 'cocurricular' && (!this.filters.school_class_id || !this.filters.cocurricular_id)) {
                        this.emptyStateMsg = "Silakan pilih kelas dan proyek kokurikuler untuk menampilkan data analitik.";
                        this.hasData = false;
                        return;
                    }

                    if (this.filters.target_type === 'student' && this.filters.student_id === 'all') {
                        this.errorMsg = "Silakan pilih siswa spesifik.";
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

                        const data = await response.json();

                        if (data.emptyState || data.hasData === false) {
                            this.emptyStateMsg = data.message || 'Tidak ditemukan catatan sesi kehadiran untuk filter yang dipilih.';
                            this.hasData = false;
                            return;
                        }

                        if (!response.ok || data.success === false) {
                            throw new Error(data.message || data.error || 'Gagal memproses analitik dari server.');
                        }

                        this.metrics = data.metrics || this.metrics;
                        this.studentsBelowTarget = data.studentsBelowTarget || [];
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

                    // Pie/Doughnut Chart
                    const canvasPie = document.getElementById('pieChart');
                    if (canvasPie) {
                        const ctxPie = canvasPie.getContext('2d');
                        this.pieChartInst = new Chart(ctxPie, {
                            type: 'doughnut',
                            data: {
                                labels: ['Hadir', 'Sakit', 'Izin', 'Alpa / Bolos'],
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
                                        labels: { boxWidth: 10, padding: 12, font: { size: 11, weight: 'bold' } }
                                    },
                                    tooltip: {
                                        callbacks: { label: (context) => ' ' + context.label + ': ' + context.parsed + '%' }
                                    }
                                }
                            }
                        });
                    }

                    // Bar Chart
                    const canvasBar = document.getElementById('barChart');
                    if (canvasBar) {
                        const ctxBar = canvasBar.getContext('2d');
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
                                        ticks: { callback: (val) => val + '%', font: { size: 10 } },
                                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { size: 10, weight: 'bold' } }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: { boxWidth: 10, padding: 10, font: { size: 11, weight: 'bold' } }
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
        }
    </script>
    @endpush
</x-app-layout>
