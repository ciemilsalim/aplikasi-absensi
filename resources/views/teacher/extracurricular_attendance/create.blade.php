<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'pembina_ekskul'])],
                    ['title' => 'Presensi ' . $extracurricular->name, 'url' => route('teacher.extracurricular-attendance.scanner', $extracurricular)],
                    ['title' => 'Form Input Manual', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Form Presensi Manual: <span class="text-amber-600 dark:text-amber-400">{{ $extracurricular->name }}</span>
                    </h1>
                </div>
            </div>

            <!-- Header Badges & Back Button -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.extracurricular-attendance.scanner', $extracurricular) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/20 active:scale-95 transition-all">
                    <span class="material-icons text-sm">qr_code_scanner</span>
                    <span>Buka Scanner Kamera</span>
                </a>

                <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span class="hidden sm:inline">Dasbor</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{
        search: '',
        markAllPresent() {
            document.querySelectorAll('input[type=radio][value=hadir]').forEach(el => {
                el.checked = true;
                el.dispatchEvent(new Event('change'));
            });
        }
    }">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <form action="{{ route('teacher.extracurricular-attendance.store', $extracurricular) }}" method="POST">
                @csrf
                
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-3xl border border-slate-200/80 dark:border-slate-800">
                    
                    <!-- Control Bar (Title, Date & Fast Tools) -->
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-850/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-amber-500 text-lg">fact_check</span>
                                Daftar Kehadiran Anggota Ekstrakurikuler
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Tandai status kehadiran siswa dan tambahkan catatan bila diperlukan.
                            </p>
                        </div>

                        <!-- Date & Quick Tools -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Quick All Present Button -->
                            <button type="button" 
                                    @click="markAllPresent()" 
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition-all active:scale-95">
                                <span class="material-icons text-sm text-emerald-600 dark:text-emerald-400">done_all</span>
                                <span>Tandai Semua Hadir</span>
                            </button>

                            <!-- Date Picker -->
                            <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xs">
                                <span class="material-icons text-slate-400 text-xs">event</span>
                                <label for="attendance_date" class="text-xs font-bold text-slate-600 dark:text-slate-300">Tanggal:</label>
                                <input id="attendance_date" name="attendance_date" type="date" value="{{ $today }}" required 
                                       class="border-0 bg-transparent text-slate-800 dark:text-white focus:ring-0 text-xs font-bold p-0 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- Search Filter Bar -->
                    <div class="px-6 py-3.5 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <input type="text" x-model="search" placeholder="Cari nama atau NIS siswa..." 
                                   class="w-full text-xs py-2 pl-9 pr-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-amber-500 focus:border-amber-500 text-slate-800 dark:text-slate-100">
                            <span class="material-icons text-slate-400 text-sm absolute left-3 top-1/2 -translate-y-1/2">search</span>
                        </div>
                        <div class="text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">
                            Total: <span class="text-amber-600 dark:text-amber-400">{{ $extracurricular->students->count() }}</span> Siswa
                        </div>
                    </div>

                    <!-- Student List Table / Cards -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($extracurricular->students as $student)
                            @php
                                $existing = $existingAttendances->get($student->id);
                                $currentStatus = $existing ? $existing->status : 'hadir';
                                $currentNotes = $existing ? $existing->notes : '';
                            @endphp
                            <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-slate-50/70 dark:hover:bg-slate-850/40 transition-colors"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($student->name . ' ' . $student->nis)) }}.includes(search.toLowerCase())">
                                
                                <!-- Student Info -->
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-extrabold text-xs flex items-center justify-center shrink-0 border border-amber-200/60 dark:border-amber-800">
                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                                            {{ $student->name }}
                                        </h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                            Kelas {{ $student->schoolClass?->name ?? '-' }} • NIS: {{ $student->nis ?? '-' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Attendance Status Options & Note -->
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 shrink-0">
                                    
                                    <!-- Radio Status Pills -->
                                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl border border-slate-200/60 dark:border-slate-700">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="hadir" {{ $currentStatus === 'hadir' ? 'checked' : '' }} class="peer sr-only">
                                            <span class="px-3 py-1.5 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-400 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-xs inline-block transition-all">
                                                Hadir
                                            </span>
                                        </label>

                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="sakit" {{ $currentStatus === 'sakit' ? 'checked' : '' }} class="peer sr-only">
                                            <span class="px-3 py-1.5 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-400 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:shadow-xs inline-block transition-all">
                                                Sakit
                                            </span>
                                        </label>

                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="izin" {{ $currentStatus === 'izin' ? 'checked' : '' }} class="peer sr-only">
                                            <span class="px-3 py-1.5 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-400 peer-checked:bg-purple-600 peer-checked:text-white peer-checked:shadow-xs inline-block transition-all">
                                                Izin
                                            </span>
                                        </label>

                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="alpa" {{ $currentStatus === 'alpa' ? 'checked' : '' }} class="peer sr-only">
                                            <span class="px-3 py-1.5 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-400 peer-checked:bg-rose-600 peer-checked:text-white peer-checked:shadow-xs inline-block transition-all">
                                                Alpa
                                            </span>
                                        </label>
                                    </div>

                                    <!-- Notes Input Field -->
                                    <div class="w-full sm:w-44">
                                        <input type="text" name="attendances[{{ $student->id }}][notes]" value="{{ $currentNotes }}" placeholder="Catatan..." 
                                               class="w-full text-xs py-1.5 px-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:border-amber-500 focus:ring-amber-500/15">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400 text-xs">
                                Belum ada siswa yang terdaftar pada ekstrakurikuler ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- Footer Submit -->
                    <div class="p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-850/50 flex items-center justify-between">
                        <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}" 
                           class="px-4 py-2.5 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors shadow-2xs">
                            Batal
                        </a>

                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/25 active:scale-95 transition-all">
                            <span class="material-icons text-base">save</span>
                            <span>Simpan Presensi</span>
                        </button>
                    </div>

                </div>
            </form>
            
        </div>
    </div>
</x-app-layout>
