{{--
================================================================================================
| File    : resources/views/teacher/partials/_dashboard-pembina-ekskul.blade.php
| Deskripsi : Tampilan ringkasan absensi harian untuk pembina ekstrakurikuler (Mobile Friendly).
================================================================================================
--}}

<div class="space-y-6">
    <!-- Header Bagian -->
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <span class="material-icons text-blue-500">star</span>
            Kegiatan Ekstrakurikuler
        </h2>
    </div>

    <!-- Daftar Ekskul yang Dibina -->
    <div class="grid grid-cols-1 gap-4">
        @forelse($coachedExtracurriculars as $ekskul)
            @php
                $stats = $todayExtracurricularStats[$ekskul->id] ?? null;
                $isRecorded = ($stats && $stats['total_recorded'] > 0);
            @endphp
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group">
                <!-- Background Decorative Icon -->
                <span class="material-icons absolute -right-4 -bottom-4 text-7xl opacity-[0.03] dark:opacity-[0.05] group-hover:scale-110 transition-transform duration-500">
                    military_tech
                </span>

                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white uppercase tracking-tight">{{ $ekskul->name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <span class="material-icons text-[14px]">groups</span>
                                {{ $ekskul->students_count }} Siswa Terdaftar
                            </p>
                        </div>
                        @if($isRecorded)
                            <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-black px-2 py-1 rounded-lg uppercase">
                                Sudah Absen
                            </span>
                        @else
                            <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-black px-2 py-1 rounded-lg uppercase">
                                Belum Absen
                            </span>
                        @endif
                    </div>

                    <!-- Mini Stats -->
                    @if($isRecorded)
                    <div class="grid grid-cols-4 gap-2 mb-4">
                        <div class="bg-slate-50 dark:bg-slate-700/50 p-2 rounded-xl text-center border border-slate-100 dark:border-slate-600">
                            <p class="text-[9px] uppercase font-bold text-slate-400 dark:text-slate-500">Hadir</p>
                            <p class="text-sm font-black text-emerald-500">{{ $stats['hadir'] }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/50 p-2 rounded-xl text-center border border-slate-100 dark:border-slate-600">
                            <p class="text-[9px] uppercase font-bold text-slate-400 dark:text-slate-500">Sakit</p>
                            <p class="text-sm font-black text-amber-500">{{ $stats['sakit'] }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/50 p-2 rounded-xl text-center border border-slate-100 dark:border-slate-600">
                            <p class="text-[9px] uppercase font-bold text-slate-400 dark:text-slate-500">Izin</p>
                            <p class="text-sm font-black text-blue-500">{{ $stats['izin'] }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/50 p-2 rounded-xl text-center border border-slate-100 dark:border-slate-600">
                            <p class="text-[9px] uppercase font-bold text-slate-400 dark:text-slate-500">Alpa</p>
                            <p class="text-sm font-black text-rose-500">{{ $stats['alpa'] }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Action Button -->
                    <div class="flex gap-2">
                        <a href="{{ route('teacher.extracurricular-attendance.create', $ekskul) }}" class="flex-1 bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-sky-100 dark:shadow-none transition-all text-center flex items-center justify-center gap-2">
                            <span class="material-icons text-base">edit_note</span>
                            {{ $isRecorded ? 'Edit Absensi' : 'Mulai Absensi' }}
                        </a>
                        <a href="{{ route('teacher.extracurricular-attendance.report', $ekskul) }}" class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 p-3 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all flex items-center justify-center shadow-sm">
                            <span class="material-icons">print</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-10 text-center border border-dashed border-slate-300 dark:border-slate-700">
                <span class="material-icons text-5xl text-slate-300 dark:text-slate-600 mb-4">info</span>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Anda belum ditugaskan sebagai pembina pada kegiatan ekstrakurikuler manapun.</p>
            </div>
        @endforelse
    </div>

    <!-- Quick Info -->
    <div class="bg-blue-600 rounded-2xl p-5 text-white shadow-xl shadow-blue-100 dark:shadow-none relative overflow-hidden">
        <div class="relative z-10 flex items-center gap-4">
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                <span class="material-icons">tips_and_updates</span>
            </div>
            <div>
                <h4 class="font-bold text-sm">Informasi Pembina</h4>
                <p class="text-xs text-blue-100 mt-0.5">Absensi dapat dilakukan setiap hari kegiatan sesuai dengan Tahun Ajaran Aktif.</p>
            </div>
        </div>
        <span class="material-icons absolute -right-6 -bottom-6 text-8xl text-white/10">lightbulb</span>
    </div>
</div>
