<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.extracurricular-attendance.index') }}" 
                   class="inline-flex items-center justify-center p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                   title="Kembali ke Daftar Ekstrakurikuler">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-bold text-xl text-slate-800 dark:text-white leading-tight">
                            Form Absensi: <span class="text-sky-600 dark:text-sky-400">{{ $extracurricular->name }}</span>
                        </h2>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kelola presensi peserta ekstrakurikuler per tanggal kegiatan.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 text-xs font-bold rounded-xl border border-sky-200 dark:border-sky-800">
                    {{ $activeYear->name ?? 'Tahun Ajaran Aktif' }}
                </span>
                <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold rounded-xl border border-indigo-200 dark:border-indigo-800">
                    {{ $activeSemester->name ?? 'Semester Aktif' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        search: '',
        markAllPresent() {
            document.querySelectorAll('input[type=radio][value=hadir]').forEach(el => {
                el.checked = true;
                el.dispatchEvent(new Event('change'));
            });
        }
    }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('teacher.extracurricular-attendance.store', $extracurricular) }}" method="POST">
                @csrf
                
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-3xl border border-slate-200/80 dark:border-slate-800">
                    
                    <!-- Form Top Control Panel -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">Daftar Kehadiran Anggota</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih status kehadiran untuk setiap siswa yang terdaftar.</p>
                        </div>

                        <!-- Date & Quick Tools -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Quick All Present Button -->
                            <button type="button" 
                                    @click="markAllPresent()" 
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-emerald-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Tandai Semua Hadir
                            </button>

                            <!-- Date Picker -->
                            <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                                <span class="material-icons text-slate-400 text-sm">event</span>
                                <label for="attendance_date" class="text-xs font-semibold text-slate-600 dark:text-slate-300">Tanggal:</label>
                                <input id="attendance_date" name="attendance_date" type="date" value="{{ $today }}" required 
                                       class="border-0 bg-transparent text-slate-800 dark:text-white focus:ring-0 text-xs font-semibold p-0 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- Search Filter Bar -->
                    <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <input type="text" x-model="search" placeholder="Cari nama atau NIS siswa..." 
                                   class="w-full text-xs py-2 pl-9 pr-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl focus:ring-sky-500 focus:border-sky-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">
                            Total: <span class="font-bold text-slate-800 dark:text-white">{{ $extracurricular->students->count() }}</span> Siswa
                        </div>
                    </div>

                    <!-- Student List Table / Cards -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($extracurricular->students as $student)
                            @php
                                $existing = $existingAttendances->get($student->id);
                                $currentStatus = $existing ? $existing->status : 'hadir';
                            @endphp
                            <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($student->name . ' ' . $student->nis)) }}.includes(search.toLowerCase())">
                                
                                <!-- Student Info with Photo Avatar -->
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="relative shrink-0">
                                        <img class="w-11 h-11 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm"
                                             src="{{ $student->photo_url }}" 
                                             alt="{{ $student->name }}"
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=7F9CF5&background=EBF4FF'">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm text-slate-800 dark:text-slate-100 truncate">{{ $student->name }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                {{ $student->schoolClass->name ?? 'Tanpa Kelas' }}
                                            </span>
                                            <span class="text-[11px] text-slate-400 font-medium">NIS: {{ $student->nis ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance Radio Group & Notes -->
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @foreach([
                                            'hadir' => ['label' => 'Hadir', 'badge' => 'border-emerald-200 text-emerald-700 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 dark:border-emerald-800 dark:text-emerald-300'],
                                            'sakit' => ['label' => 'Sakit', 'badge' => 'border-amber-200 text-amber-700 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 dark:border-amber-800 dark:text-amber-300'],
                                            'izin'  => ['label' => 'Izin',  'badge' => 'border-sky-200 text-sky-700 peer-checked:bg-sky-600 peer-checked:text-white peer-checked:border-sky-600 dark:border-sky-800 dark:text-sky-300'],
                                            'alpa'  => ['label' => 'Alpa',  'badge' => 'border-rose-200 text-rose-700 peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600 dark:border-rose-800 dark:text-rose-300']
                                        ] as $status => $style)
                                            <label class="cursor-pointer">
                                                <input type="radio" 
                                                       name="attendances[{{ $student->id }}][status]" 
                                                       value="{{ $status }}" 
                                                       class="hidden peer" 
                                                       {{ $currentStatus === $status ? 'checked' : '' }} 
                                                       required>
                                                <div class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all duration-150 bg-white dark:bg-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 {{ $style['badge'] }}">
                                                    {{ $style['label'] }}
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    
                                    <!-- Optional Notes Input -->
                                    <div class="w-full sm:w-44">
                                        <input type="text" 
                                               name="attendances[{{ $student->id }}][notes]" 
                                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 focus:ring-sky-500 focus:border-sky-500 py-1.5 px-3" 
                                               placeholder="Catatan..." 
                                               value="{{ $existing->notes ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-16 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Belum ada siswa yang terdaftar pada ekstrakurikuler ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="p-6 bg-slate-50/80 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 dark:border-slate-800">
                        <a href="{{ route('teacher.extracurricular-attendance.index') }}" 
                           class="px-5 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                            Batal
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-7 py-3 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-md shadow-sky-500/20 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Simpan Seluruh Absensi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
