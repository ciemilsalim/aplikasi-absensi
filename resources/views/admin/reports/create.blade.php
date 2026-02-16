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
                {{-- Initialize Alpine.js data context --}}
                <div x-data="{ reportType: 'class_monthly' }">
                    <form action="{{ route('admin.reports.generate') }}" method="POST" target="_blank">
                        @csrf
                        <div class="p-6 space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pilih Jenis Laporan</h3>
                            {{-- This hidden input will hold the actual value for the form submission --}}
                            <input type="hidden" name="report_type" x-model="reportType">
                            
                            <!-- Pilihan Jenis Laporan -->
                            {{-- Updated to a 2x2 grid to accommodate the new option --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                {{-- NEW REPORT OPTION ADDED HERE --}}
                                <label @click="reportType = 'school_no_checkout'" class="flex items-center p-4 border rounded-lg cursor-pointer transition" :class="reportType === 'school_no_checkout' ? 'bg-sky-50 border-sky-500 dark:bg-sky-900/50' : 'border-gray-300 dark:border-slate-700'">
                                    <input type="radio" name="report_type_option" value="school_no_checkout" x-model="reportType" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500">
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak Absen Pulang</span>
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
                                        <x-input-label for="student_search" value="Cari Siswa" />
                                        
                                        {{-- Hidden input to store the actual ID --}}
                                        <input type="hidden" name="student_id" x-bind:value="selected ? selected.id : ''" x-bind:required="reportType === 'student_detailed'" x-bind:disabled="reportType !== 'student_detailed'">
                                        
                                        {{-- Visual Search Input --}}
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                x-model="search"
                                                @focus="open = true"
                                                @click.away="open = false"
                                                @keydown.escape="open = false"
                                                class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm"
                                                placeholder="Ketik nama siswa atau kelas..."
                                            >
                                            
                                            {{-- Clear Button --}}
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3" x-show="search.length > 0">
                                                <button type="button" @click="search = ''; selected = null; open = true" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Dropdown Results --}}
                                        <div x-show="open && filteredStudents.length > 0" 
                                             class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                                             style="display: none;">
                                            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                                                <template x-for="student in filteredStudents" :key="student.id">
                                                    <li @click="selectStudent(student)" 
                                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-sky-50 dark:hover:bg-slate-700 text-gray-900 dark:text-gray-300">
                                                        <div class="flex items-center">
                                                            <span class="font-normal block truncate" x-text="student.name"></span>
                                                            <span class="ml-2 text-gray-500 dark:text-gray-400 text-xs" x-text="student.school_class ? student.school_class.name : 'Tanpa Kelas'"></span>
                                                        </div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                        
                                        {{-- No Results Message --}}
                                        <div x-show="open && filteredStudents.length === 0" 
                                             class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 shadow-lg rounded-md py-2 px-3 text-sm text-gray-500 dark:text-gray-400"
                                             style="display: none;">
                                            Tidak ada siswa ditemukan.
                                        </div>
                                    </div>
                                </div>

                                {{-- REFACTORED: Shared Date Range Picker --}}
                                {{-- This block will now appear for multiple report types --}}
                                <div x-show="['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" x-transition class="grid grid-cols-2 gap-4" style="display: none;">
                                    <div>
                                        <x-input-label for="start_date" value="Dari Tanggal" />
                                        <x-text-input id="start_date" type="date" name="start_date" class="mt-1 block w-full" :value="date('Y-m-d')" x-bind:required="['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" x-bind:disabled="!['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" />
                                    </div>
                                    <div>
                                        <x-input-label for="end_date" value="Sampai Tanggal" />
                                        <x-text-input id="end_date" type="date" name="end_date" class="mt-1 block w-full" :value="date('Y-m-d')" x-bind:required="['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" x-bind:disabled="!['student_detailed', 'school_lateness', 'school_no_checkout'].includes(reportType)" />
                                    </div>
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
