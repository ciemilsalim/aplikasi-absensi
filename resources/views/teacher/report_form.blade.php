<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')],
                    ['title' => 'Laporan & Rekap Kehadiran', 'url' => route('teacher.subject.attendance.report')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Rekap Presensi Sesi Pembelajaran & Kokurikuler
                </h1>
            </div>

            <a href="{{ route('teacher.subject.attendance.charts') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs border border-indigo-200/60 dark:border-indigo-900/40 shadow-2xs transition-all shrink-0">
                <span class="material-icons text-base text-indigo-600 dark:text-indigo-400">insights</span>
                <span>Analitik Grafis</span>
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto" x-data="{ activityType: '{{ old('activity_type', 'regular') }}' }">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6 sm:p-8">
                
                <div class="mb-6">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-sky-500 text-lg">filter_alt</span>
                        Pilih Parameter Rekap
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tentukan jenis kegiatan, kelas, mata pelajaran / proyek, dan rentang tanggal</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 text-xs font-medium" role="alert">
                        <strong class="font-bold flex items-center gap-1.5 mb-1">
                            <span class="material-icons text-sm">error_outline</span>
                            Periksa Kesalahan Input:
                        </strong>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Tipe Kegiatan Switcher Tabs -->
                <div class="mb-6 bg-slate-100 dark:bg-slate-800 p-1.5 rounded-2xl flex items-center gap-1">
                    <button type="button" 
                            @click="activityType = 'regular'" 
                            :class="activityType === 'regular' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                            class="flex-1 py-2.5 px-3 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                        <span class="material-icons text-base">menu_book</span>
                        <span>Mata Pelajaran</span>
                    </button>
                    <button type="button" 
                            @click="activityType = 'cocurricular'" 
                            :class="activityType === 'cocurricular' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                            class="flex-1 py-2.5 px-3 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                        <span class="material-icons text-base">psychology</span>
                        <span>Proyek Kokurikuler</span>
                    </button>
                </div>

                <form method="GET" action="{{ route('teacher.subject.attendance.preview') }}">
                    <input type="hidden" name="activity_type" :value="activityType">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tanggal Mulai -->
                        <div>
                            <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Dari Tanggal <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="start_date" id="start_date" 
                                   value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" required 
                                   class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Sampai Tanggal <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="end_date" id="end_date" 
                                   value="{{ old('end_date', now()->endOfMonth()->format('Y-m-d')) }}" required 
                                   class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                        </div>

                        <!-- Pilih Kelas (Regular) -->
                        <div x-show="activityType === 'regular'">
                            <label for="school_class_id_regular" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Target Kelas <span class="text-rose-500">*</span>
                            </label>
                            <select name="school_class_id" id="school_class_id_regular" :disabled="activityType !== 'regular'" required 
                                    class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $id => $name)
                                    <option value="{{ $id }}" {{ old('school_class_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pilih Kelas (Kokurikuler) -->
                        <div x-show="activityType === 'cocurricular'" style="display: none;">
                            <label for="school_class_id_cocurricular" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Target Kelas Kokurikuler <span class="text-rose-500">*</span>
                            </label>
                            <select name="school_class_id" id="school_class_id_cocurricular" :disabled="activityType !== 'cocurricular'" required 
                                    class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                                <option value="">-- Pilih Kelas Binaan --</option>
                                @foreach($cocurricularClasses as $id => $name)
                                    <option value="{{ $id }}" {{ old('school_class_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pilih Mata Pelajaran -->
                        <div x-show="activityType === 'regular'">
                            <label for="subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Mata Pelajaran <span class="text-rose-500">*</span>
                            </label>
                            <select name="subject_id" id="subject_id" :disabled="activityType !== 'regular'" required 
                                    class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($subjects as $id => $name)
                                    <option value="{{ $id }}" {{ old('subject_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pilih Proyek Kokurikuler -->
                        <div x-show="activityType === 'cocurricular'" style="display: none;">
                            <label for="cocurricular_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Proyek Kokurikuler <span class="text-rose-500">*</span>
                            </label>
                            <select name="cocurricular_id" id="cocurricular_id" :disabled="activityType !== 'cocurricular'" required 
                                    class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                                <option value="">-- Pilih Proyek Kokurikuler --</option>
                                @foreach($cocurricularProjects as $id => $title)
                                    <option value="{{ $id }}" {{ old('cocurricular_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                            <span class="material-icons text-base">visibility</span>
                            <span>Tampilkan Matriks Rekap</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
