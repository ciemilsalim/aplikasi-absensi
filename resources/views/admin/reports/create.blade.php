<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Laporan', 'url' => route('admin.reports.create')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Cetak Laporan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div x-data="{ reportType: 'class_monthly' }">
                    <form action="{{ route('admin.reports.generate') }}" method="POST" target="_blank">
                        @csrf
                        <div class="p-6 space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pilih Jenis Laporan</h3>
                            <input type="hidden" name="report_type" x-model="reportType">
                            
                            <!-- Pilihan Jenis Laporan -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label @click="reportType = 'class_monthly'" class="flex items-center p-4 border rounded-lg cursor-pointer transition" :class="reportType === 'class_monthly' ? 'bg-sky-50 border-sky-500 dark:bg-sky-900/50' : 'border-gray-300 dark:border-slate-700'">
                                    <input type="radio" name="report_type_option" value="class_monthly" x-model="reportType" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Rekap Kelas Bulanan</span>
                                </label>
                                <label @click="reportType = 'student_detailed'" class="flex items-center p-4 border rounded-lg cursor-pointer transition" :class="reportType === 'student_detailed' ? 'bg-sky-50 border-sky-500 dark:bg-sky-900/50' : 'border-gray-300 dark:border-slate-700'">
                                    <input type="radio" name="report_type_option" value="student_detailed" x-model="reportType" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Detail per Siswa</span>
                                </label>
                                <label @click="reportType = 'school_lateness'" class="flex items-center p-4 border rounded-lg cursor-pointer transition" :class="reportType === 'school_lateness' ? 'bg-sky-50 border-sky-500 dark:bg-sky-900/50' : 'border-gray-300 dark:border-slate-700'">
                                    <input type="radio" name="report_type_option" value="school_lateness" x-model="reportType" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Rekap Terlambat</span>
                                </label>
                            </div>
                            
                            <!-- Filter Dinamis -->
                            <div class="border-t border-gray-200 dark:border-slate-700 pt-6 space-y-4">
                                {{-- Filter untuk Rekap Kelas Bulanan --}}
                                <div x-show="reportType === 'class_monthly'" x-transition class="space-y-4">
                                    <div>
                                        <x-input-label for="school_class_id" value="Pilih Kelas" />
                                        <select name="school_class_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm" x-bind:required="reportType === 'class_monthly'" x-bind:disabled="reportType !== 'class_monthly'">
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach($classes as $class)
                                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="month" value="Pilih Bulan" />
                                        <x-text-input id="month" type="month" name="month" class="mt-1 block w-full" :value="date('Y-m')" x-bind:required="reportType === 'class_monthly'" x-bind:disabled="reportType !== 'class_monthly'" />
                                    </div>
                                </div>
                                {{-- Filter untuk Detail per Siswa --}}
                                <div x-show="reportType === 'student_detailed'" x-transition class="space-y-4" style="display: none;">
                                    <div>
                                        <x-input-label for="student_id" value="Pilih Siswa" />
                                        <select name="student_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm" x-bind:required="reportType === 'student_detailed'" x-bind:disabled="reportType !== 'student_detailed'">
                                            <option value="">-- Pilih Siswa --</option>
                                            @foreach($students as $student)
                                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->schoolClass->name ?? 'Tanpa Kelas' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><x-input-label for="start_date_student" value="Dari Tanggal" /><x-text-input id="start_date_student" type="date" name="start_date" class="mt-1 block w-full" :value="date('Y-m-d')" x-bind:required="reportType === 'student_detailed'" x-bind:disabled="reportType !== 'student_detailed'" /></div>
                                        <div><x-input-label for="end_date_student" value="Sampai Tanggal" /><x-text-input id="end_date_student" type="date" name="end_date" class="mt-1 block w-full" :value="date('Y-m-d')" x-bind:required="reportType === 'student_detailed'" x-bind:disabled="reportType !== 'student_detailed'" /></div>
                                    </div>
                                </div>
                                {{-- Filter untuk Rekap Keterlambatan --}}
                                <div x-show="reportType === 'school_lateness'" x-transition class="grid grid-cols-2 gap-4" style="display: none;">
                                    <div><x-input-label for="start_date_late" value="Dari Tanggal" /><x-text-input id="start_date_late" type="date" name="start_date" class="mt-1 block w-full" :value="date('Y-m-d')" x-bind:required="reportType === 'school_lateness'" x-bind:disabled="reportType !== 'school_lateness'" /></div>
                                    <div><x-input-label for="end_date_late" value="Sampai Tanggal" /><x-text-input id="end_date_late" type="date" name="end_date" class="mt-1 block w-full" :value="date('Y-m-d')" x-bind:required="reportType === 'school_lateness'" x-bind:disabled="reportType !== 'school_lateness'" /></div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                            <x-primary-button>Cetak Laporan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
