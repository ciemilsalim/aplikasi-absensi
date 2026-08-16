<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Laporan', 'url' => route('admin.reports.create')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Cetak & Ekspor Laporan Presensi
                </h1>
            </div>
            
            <a href="{{ route('admin.reports.charts') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs border border-indigo-200/60 dark:border-indigo-900/40 shadow-2xs transition-all transform hover:-translate-y-0.5 active:translate-y-0 shrink-0">
                <span class="material-icons text-base text-indigo-600 dark:text-indigo-400">insights</span>
                <span>Lihat Analitik Visual</span>
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            {{-- Initialize Alpine.js data context --}}
            <div x-data="{ reportType: 'class_monthly' }">
                <form action="{{ route('admin.reports.generate') }}" method="POST" target="_blank">
                    @csrf
                    <div class="p-6 sm:p-8 space-y-6">
                        
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-sky-500 text-lg">tune</span>
                                Pilih Format & Jenis Laporan
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tentukan tipe dokumen laporan kehadiran yang ingin dicetak ke format PDF</p>
                        </div>

                        {{-- Hidden input for form submission --}}
                        <input type="hidden" name="report_type" x-model="reportType">
                        
                        <!-- Pilihan Jenis Laporan (Modern Tile Cards) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                            
                            <!-- 1. Rekap Kelas Bulanan -->
                            <label @click="reportType = 'class_monthly'" 
                                   class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all" 
                                   :class="reportType === 'class_monthly' ? 'bg-sky-50/70 border-sky-500 dark:bg-sky-950/40 dark:border-sky-500 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-850/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="reportType === 'class_monthly' ? 'bg-sky-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="material-icons text-lg">calendar_view_month</span>
                                    </div>
                                    <input type="radio" name="report_type_option" value="class_monthly" x-model="reportType" class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                                </div>
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Rekap Kelas Bulanan</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Matriks presensi per kelas selama 1 bulan</span>
                            </label>

                            <!-- 2. Rekap Kelas Triwulan -->
                            <label @click="reportType = 'class_trimester'" 
                                   class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all" 
                                   :class="reportType === 'class_trimester' ? 'bg-sky-50/70 border-sky-500 dark:bg-sky-950/40 dark:border-sky-500 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-850/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="reportType === 'class_trimester' ? 'bg-sky-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="material-icons text-lg">date_range</span>
                                    </div>
                                    <input type="radio" name="report_type_option" value="class_trimester" x-model="reportType" class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                                </div>
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Rekap Triwulan</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Akumulasi presensi per 3 bulan pelajaran</span>
                            </label>

                            <!-- 3. Detail per Siswa -->
                            <label @click="reportType = 'student_detailed'" 
                                   class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all" 
                                   :class="reportType === 'student_detailed' ? 'bg-sky-50/70 border-sky-500 dark:bg-sky-950/40 dark:border-sky-500 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-850/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="reportType === 'student_detailed' ? 'bg-sky-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="material-icons text-lg">person_pin</span>
                                    </div>
                                    <input type="radio" name="report_type_option" value="student_detailed" x-model="reportType" class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                                </div>
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Detail per Siswa</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Buku riwayat lengkap kehadiran satu siswa</span>
                            </label>

                            <!-- 4. Rekap Terlambat -->
                            <label @click="reportType = 'school_lateness'" 
                                   class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all" 
                                   :class="reportType === 'school_lateness' ? 'bg-sky-50/70 border-sky-500 dark:bg-sky-950/40 dark:border-sky-500 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-850/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="reportType === 'school_lateness' ? 'bg-sky-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="material-icons text-lg">alarm</span>
                                    </div>
                                    <input type="radio" name="report_type_option" value="school_lateness" x-model="reportType" class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                                </div>
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Rekap Terlambat</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Daftar siswa yang terlambat sekolah</span>
                            </label>

                            <!-- 5. Tidak Absen Pulang -->
                            <label @click="reportType = 'school_no_checkout'" 
                                   class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all" 
                                   :class="reportType === 'school_no_checkout' ? 'bg-sky-50/70 border-sky-500 dark:bg-sky-950/40 dark:border-sky-500 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-850/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="reportType === 'school_no_checkout' ? 'bg-sky-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="material-icons text-lg">no_accounts</span>
                                    </div>
                                    <input type="radio" name="report_type_option" value="school_no_checkout" x-model="reportType" class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                                </div>
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Tanpa Absen Pulang</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Siswa hadir pagi namun tidak check-out</span>
                            </label>

                            <!-- 6. Rekap Presensi Kokurikuler -->
                            <label @click="reportType = 'cocurricular_monthly'" 
                                   class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all" 
                                   :class="reportType === 'cocurricular_monthly' ? 'bg-indigo-50/70 border-indigo-500 dark:bg-indigo-950/40 dark:border-indigo-500 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-850/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="reportType === 'cocurricular_monthly' ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="material-icons text-lg">psychology</span>
                                    </div>
                                    <input type="radio" name="report_type_option" value="cocurricular_monthly" x-model="reportType" class="h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                </div>
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white">Rekap Kokurikuler</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Rekap presensi per proyek & kelas</span>
                            </label>
                        </div>
                        
                        <!-- Dynamic Filter Configuration Panel -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-4">
                            
                            {{-- Filter untuk Rekap Kokurikuler --}}
                            <div x-show="reportType === 'cocurricular_monthly'" x-transition class="space-y-4" style="display: none;">
                                <div>
                                    <label for="cocurricular_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Pilih Proyek Kokurikuler <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="cocurricular_id" name="cocurricular_id" 
                                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15" 
                                            x-bind:required="reportType === 'cocurricular_monthly'" 
                                            x-bind:disabled="reportType !== 'cocurricular_monthly'">
                                        <option value="">-- Pilih Proyek Kokurikuler --</option>
                                        @foreach($cocurriculars ?? [] as $coc)
                                            <option value="{{ $coc->id }}">{{ $coc->title }} ({{ $coc->level?->name ?? 'Tingkat' }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="school_class_id_cocurricular" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Pilih Kelas Target <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="school_class_id_cocurricular" name="school_class_id" 
                                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15" 
                                            x-bind:required="reportType === 'cocurricular_monthly'" 
                                            x-bind:disabled="reportType !== 'cocurricular_monthly'">
                                        <option value="">-- Pilih Kelas Target --</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="month_cocurricular" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Pilih Periode Bulan <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="month_cocurricular" type="month" name="month" 
                                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15" 
                                           value="{{ date('Y-m') }}" 
                                           x-bind:required="reportType === 'cocurricular_monthly'" 
                                           x-bind:disabled="reportType !== 'cocurricular_monthly'" />
                                </div>
                            </div>
                            
                            {{-- Filter untuk Rekap Kelas Bulanan & Triwulan --}}
                            <div x-show="['class_monthly', 'class_trimester'].includes(reportType)" x-transition class="space-y-4">
                                <div>
                                    <label for="school_class_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Pilih Kelas <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="school_class_id" name="school_class_id" 
                                            class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15" 
                                            x-bind:required="['class_monthly', 'class_trimester'].includes(reportType)" 
                                            x-bind:disabled="!['class_monthly', 'class_trimester'].includes(reportType)">
                                        <option value="">-- Pilih Kelas Target --</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div x-show="reportType === 'class_monthly'">
                                    <label for="month" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Pilih Periode Bulan <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="month" type="month" name="month" 
                                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15" 
                                           value="{{ date('Y-m') }}" 
                                           x-bind:required="reportType === 'class_monthly'" 
                                           x-bind:disabled="reportType !== 'class_monthly'" />
                                </div>

                                <div x-show="reportType === 'class_trimester'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="trimester" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Pilih Triwulan <span class="text-rose-500">*</span>
                                        </label>
                                        <select id="trimester" name="trimester" 
                                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15" 
                                                x-bind:required="reportType === 'class_trimester'" 
                                                x-bind:disabled="reportType !== 'class_trimester'">
                                            <option value="">-- Pilih Triwulan --</option>
                                            <option value="1">Triwulan 1 (Januari - Maret)</option>
                                            <option value="2">Triwulan 2 (April - Juni)</option>
                                            <option value="3">Triwulan 3 (Juli - September)</option>
                                            <option value="4">Triwulan 4 (Oktober - Desember)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="year" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Tahun <span class="text-rose-500">*</span>
                                        </label>
                                        <input id="year" type="number" min="2000" name="year" 
                                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15" 
                                               value="{{ date('Y') }}" 
                                               x-bind:required="reportType === 'class_trimester'" 
                                               x-bind:disabled="reportType !== 'class_trimester'" />
                                    </div>
                                    <div>
                                        <label for="paper_size" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Ukuran Kertas <span class="text-rose-500">*</span>
                                        </label>
                                        <select id="paper_size" name="paper_size" 
                                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                                            <option value="a4" selected>A4 Landscape (297 x 210 mm)</option>
                                            <option value="folio">Folio / F4 Landscape (330 x 215 mm)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Filter untuk Detail per Siswa --}}
                            <div x-show="reportType === 'student_detailed'" x-transition class="space-y-4" style="display: none;"
                                 x-data="{
                                     search: '',
                                     open: false,
                                     selected: null,
                                     students: {{ \Illuminate\Support\Js::from($students) }},
                                     get filteredStudents() {
                                         if (this.search === '') {
                                             return this.students;
                                         }
                                         return this.students.filter(student => {
                                             return student.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                                    (student.school_class && student.school_class.name.toLowerCase().includes(this.search.toLowerCase()));
                                         });
                                     },
                                     selectStudent(student) {
                                         this.selected = student;
                                         this.search = student.name + ' (' + (student.school_class ? student.school_class.name : 'Tanpa Kelas') + ')';
                                         this.open = false;
                                     }
                                 }"
                            >
                                <div class="relative">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Cari & Pilih Siswa <span class="text-rose-500">*</span>
                                    </label>
                                    
                                    {{-- Hidden input to store the actual ID --}}
                                    <input type="hidden" name="student_id" x-bind:value="selected ? selected.id : ''" x-bind:required="reportType === 'student_detailed'" x-bind:disabled="reportType !== 'student_detailed'">
                                    
                                    {{-- Visual Search Input --}}
                                    <div class="relative">
                                        <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">search</span>
                                        <input 
                                            type="text" 
                                            x-model="search"
                                            @focus="open = true"
                                            @click.away="open = false"
                                            @keydown.escape="open = false"
                                            class="w-full text-xs font-semibold pl-10 pr-10 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15"
                                            placeholder="Ketik nama atau kelas siswa..."
                                        >
                                        
                                        {{-- Clear Button --}}
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3" x-show="search.length > 0">
                                            <button type="button" @click="search = ''; selected = null; open = true" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                                                <span class="material-icons text-sm">close</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Dropdown Results --}}
                                    <div x-show="open && filteredStudents.length > 0" 
                                         class="absolute z-20 mt-1.5 w-full bg-white dark:bg-slate-850 shadow-xl max-h-60 rounded-2xl py-1 text-xs border border-slate-200 dark:border-slate-700 overflow-auto focus:outline-none"
                                         style="display: none;">
                                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <template x-for="student in filteredStudents" :key="student.id">
                                                <li @click="selectStudent(student)" 
                                                    class="cursor-pointer select-none py-2.5 px-4 hover:bg-sky-50 dark:hover:bg-slate-800 text-slate-900 dark:text-slate-200 flex items-center justify-between">
                                                    <span class="font-bold block truncate" x-text="student.name"></span>
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-750 text-slate-600 dark:text-slate-400" x-text="student.school_class ? student.school_class.name : 'Tanpa Kelas'"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    
                                    {{-- No Results Message --}}
                                    <div x-show="open && filteredStudents.length === 0" 
                                         class="absolute z-20 mt-1.5 w-full bg-white dark:bg-slate-850 shadow-xl rounded-2xl py-3 px-4 text-xs text-slate-400 border border-slate-200 dark:border-slate-700"
                                         style="display: none;">
                                        Tidak ada data siswa yang cocok.
                                    </div>
                                </div>
                            </div>

                            {{-- Shared Date Range Picker --}}
                            <div x-show="['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display: none;">
                                <div>
                                    <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Dari Tanggal <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="start_date" type="date" name="start_date" 
                                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15" 
                                           value="{{ date('Y-m-d') }}" 
                                           x-bind:required="['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" 
                                           x-bind:disabled="!['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" />
                                </div>
                                <div>
                                    <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Sampai Tanggal <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="end_date" type="date" name="end_date" 
                                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15" 
                                           value="{{ date('Y-m-d') }}" 
                                           x-bind:required="['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" 
                                           x-bind:disabled="!['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="bg-slate-50/75 dark:bg-slate-850/50 px-6 sm:px-8 py-4 flex items-center justify-end border-t border-slate-100 dark:border-slate-800">
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                            <span class="material-icons text-base">picture_as_pdf</span>
                            <span>Generate & Cetak Laporan PDF</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

