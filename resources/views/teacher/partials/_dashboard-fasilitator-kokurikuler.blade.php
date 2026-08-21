{{--
================================================================================================
| File       : resources/views/teacher/partials/_dashboard-fasilitator-kokurikuler.blade.php
| Deskripsi  : Dasbor Fasilitator Kokurikuler dengan standar UX mobile dan desktop utuh.
================================================================================================
--}}

<div class="space-y-6">

    {{-- ========================================================================= --}}
    {{-- 1. TAMPILAN KHUSUS MOBILE (< sm breakpoint)                               --}}
    {{-- Sesuai Standar UX Mobile: Hero Sesi Aktif, Ringkasan Operasional, Alur Jelas --}}
    {{-- ========================================================================= --}}
    <div class="block sm:hidden space-y-5" x-data="{ showAllKokurikulerModal: false }">
        
        <!-- Header Sapaan Sederhana & Ramah Jempol (64-72px) -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <img class="h-12 w-12 rounded-2xl object-cover ring-2 ring-indigo-500/20 bg-slate-800 shrink-0 shadow-sm" 
                     src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=6366f1&background=e0e7ff' }}" 
                     alt="{{ Auth::user()->name }}">
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight truncate">
                        Halo, {{ $teacher->name }} 👋
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Fasilitator Kokurikuler &bull; SMP Negeri 1 Biau
                    </p>
                </div>
            </div>
            <a href="{{ route('teacher.subject.attendance.report', ['type' => 'cocurricular']) }}" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-500 hover:text-indigo-600 shadow-2xs shrink-0" title="Rekap Kokurikuler">
                <span class="material-icons text-xl text-indigo-500">table_chart</span>
            </a>
        </div>

        <!-- HERO CARD: SESI PROYEK HARI INI (PRIORITAS 1) -->
        @if(isset($heroScheduleKokurikuler) && $heroScheduleKokurikuler)
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <!-- Header Card: Status Waktu -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">
                            {{ $heroScheduleKokurikuler->status_time === 'ongoing' ? 'SESI PROYEK BERLANGSUNG' : ($heroScheduleKokurikuler->status_time === 'upcoming' ? 'SESI PROYEK BERIKUTNYA' : 'SESI PROYEK HARI INI') }}
                        </span>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                            {{ \Carbon\Carbon::parse($heroScheduleKokurikuler->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($heroScheduleKokurikuler->end_time)->format('H:i') }} WIB
                        </p>
                    </div>

                    @if($heroScheduleKokurikuler->status_time === 'ongoing')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/40">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sedang Berlangsung</span>
                        </span>
                    @elseif($heroScheduleKokurikuler->has_attended_today)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/40">
                            ✓ Selesai Diabsen
                        </span>
                    @elseif($heroScheduleKokurikuler->status_time === 'upcoming')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300/40">
                            ● Belum Dimulai
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300/40">
                            Belum Presensi
                        </span>
                    @endif
                </div>

                <!-- Info Proyek & Kelas -->
                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                            Kelas {{ $heroScheduleKokurikuler->schoolClass?->name ?? '-' }}
                        </span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $heroScheduleKokurikuler->teacher_id === $teacher->id ? 'Pengampu Utama' : 'Tim Fasilitator' }}
                        </span>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                        {{ $heroScheduleKokurikuler->cocurricular?->title ?? 'Proyek Kokurikuler' }}
                    </h3>
                </div>

                <!-- PRIMARY CTA BUTTON -->
                <div class="pt-1">
                    <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $heroScheduleKokurikuler->id]) }}" 
                       class="w-full min-h-[48px] py-3 flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold text-sm shadow-md shadow-indigo-600/25 active:scale-95 transition-all">
                        <span class="material-icons text-lg">qr_code_scanner</span>
                        <span>{{ $heroScheduleKokurikuler->has_attended_today ? 'BUKA SESI PRESENSI LAGI' : 'BUKA PRESENSI PROYEK' }}</span>
                    </a>
                </div>
            </div>
        @else
            <!-- Keadaan Tidak Ada Jadwal Hari Ini -->
            <div class="rounded-3xl p-6 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center">
                    <span class="material-icons text-2xl">event_available</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak Ada Sesi Kokurikuler Hari Ini</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Anda tidak memiliki jadwal sesi proyek kokurikuler pada hari ini.</p>
                <button @click="showAllKokurikulerModal = true" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200">
                    Lihat Semua Jadwal Mingguan
                </button>
            </div>
        @endif

        <!-- PRESENSI PROYEK HARI INI (RINGKASAN OPERASIONAL) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-indigo-500 text-base">fact_check</span>
                        <span>Presensi Proyek Hari Ini</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $totalSessionsTodayKokurikuler }} Sesi Total
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2.5 text-center">
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block">Total Sesi</span>
                    <span class="text-lg font-extrabold text-slate-800 dark:text-white">{{ $totalSessionsTodayKokurikuler }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-900/40">
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 block">✓ Selesai</span>
                    <span class="text-lg font-extrabold text-emerald-700 dark:text-emerald-400">{{ $completedSessionsTodayKokurikuler }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-900/40">
                    <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 block">● Sisa Sesi</span>
                    <span class="text-lg font-extrabold text-indigo-700 dark:text-indigo-400">{{ $remainingSessionsTodayKokurikuler }}</span>
                </div>
            </div>
        </div>

        <!-- JADWAL SESI PROYEK HARI INI (COMPACT PREVIEW LIST) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-1">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-indigo-500 text-base">calendar_month</span>
                    <span>Jadwal Proyek Hari Ini</span>
                </h3>
                <button @click="showAllKokurikulerModal = true" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5">
                    <span>Semua ({{ $allSchedulesKokurikuler->count() }})</span>
                    <span class="material-icons text-sm">chevron_right</span>
                </button>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($schedulesTodayKokurikuler as $schedule)
                    @php
                        $isOngoing = $schedule->status_time === 'ongoing';
                    @endphp
                    <div class="py-3 flex items-center justify-between gap-3 min-h-[64px]">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $isOngoing ? 'bg-indigo-600 text-white' : ($schedule->has_attended_today ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500') }}">
                                <span class="material-icons text-lg">{{ $schedule->has_attended_today ? 'task_alt' : 'psychology' }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-xs text-slate-900 dark:text-white truncate">
                                    {{ $schedule->cocurricular?->title ?? 'Proyek Kokurikuler' }}
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                    Kelas {{ $schedule->schoolClass?->name ?? '-' }} &bull; {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if($isOngoing)
                                <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                                   class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-600 text-white flex items-center gap-1 shadow-xs">
                                    <span>Presensi</span>
                                    <span class="material-icons text-xs">arrow_forward</span>
                                </a>
                            @elseif($schedule->has_attended_today)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/40">
                                    ✓ Selesai
                                </span>
                            @elseif($schedule->status_time === 'upcoming')
                                <span class="inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    Belum Mulai
                                </span>
                            @else
                                <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                                   class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Absen
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">Tidak ada jadwal kokurikuler hari ini.</p>
                @endforelse
            </div>
        </div>

        <!-- TIM PROYEK KOKURIKULER SAYA -->
        @if(isset($myCocurricularProjects) && $myCocurricularProjects->isNotEmpty())
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-indigo-500 text-base">auto_stories</span>
                        <span>Proyek Kokurikuler Saya</span>
                    </h3>
                </div>

                <div class="space-y-3">
                    @foreach($myCocurricularProjects as $project)
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/60 dark:border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">
                                    {{ $project->level?->name ?? 'Tingkat' }} &bull; {{ $project->time_allocation }} JP
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">
                                    {{ str_replace('_', ' ', $project->activity_type) }}
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white">
                                {{ $project->title }}
                            </h4>
                            <div class="flex items-center gap-1.5 flex-wrap pt-1">
                                <span class="text-[10px] text-slate-400 font-semibold">Tim:</span>
                                @foreach($project->teachers as $facTeacher)
                                    <span class="text-[9px] px-2 py-0.5 rounded-full {{ $facTeacher->id === $teacher->id ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                                        {{ $facTeacher->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- PRESENSI SESI TERAKHIR -->
        @if(isset($lastAttendanceSummaryKokurikuler) && $lastAttendanceSummaryKokurikuler)
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span class="material-icons text-indigo-500 text-base">history</span>
                            <span>Presensi Sesi Terakhir</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $lastAttendanceSummaryKokurikuler['schedule']->cocurricular?->title ?? 'Proyek Kokurikuler' }} &bull; Kelas {{ $lastAttendanceSummaryKokurikuler['schedule']->schoolClass?->name ?? '-' }}
                        </p>
                    </div>
                    <a href="{{ route('teacher.subject.attendance.report', ['type' => 'cocurricular']) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5">
                        <span>Rekap</span>
                        <span class="material-icons text-sm">chevron_right</span>
                    </a>
                </div>

                <div class="grid grid-cols-5 gap-1.5 text-center">
                    <div class="bg-emerald-50 dark:bg-emerald-950/40 p-2 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                        <span class="font-extrabold text-xs text-emerald-700 dark:text-emerald-300 block">{{ $lastAttendanceSummaryKokurikuler['hadir'] }}</span>
                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">Hadir</span>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-950/40 p-2 rounded-xl border border-amber-100 dark:border-amber-900/40">
                        <span class="font-extrabold text-xs text-amber-700 dark:text-amber-300 block">{{ $lastAttendanceSummaryKokurikuler['sakit'] }}</span>
                        <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400">Sakit</span>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-950/40 p-2 rounded-xl border border-purple-100 dark:border-purple-900/40">
                        <span class="font-extrabold text-xs text-purple-700 dark:text-purple-300 block">{{ $lastAttendanceSummaryKokurikuler['izin'] }}</span>
                        <span class="text-[9px] font-bold text-purple-600 dark:text-purple-400">Izin</span>
                    </div>
                    <div class="bg-red-50 dark:bg-red-950/40 p-2 rounded-xl border border-red-100 dark:border-red-900/40">
                        <span class="font-extrabold text-xs text-red-700 dark:text-red-300 block">{{ $lastAttendanceSummaryKokurikuler['alpa'] }}</span>
                        <span class="text-[9px] font-bold text-red-600 dark:text-red-400">Alpa</span>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-950/40 p-2 rounded-xl border border-orange-100 dark:border-orange-900/40">
                        <span class="font-extrabold text-xs text-orange-700 dark:text-orange-300 block">{{ $lastAttendanceSummaryKokurikuler['bolos'] }}</span>
                        <span class="text-[9px] font-bold text-orange-600 dark:text-orange-400">Bolos</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- SISWA PERLU PERHATIAN (MAKSIMAL 3 SISWA) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-rose-500 text-base">priority_high</span>
                    <span>Siswa Perlu Perhatian</span>
                </h3>
            </div>

            @if(isset($studentsForAttentionKokurikuler) && $studentsForAttentionKokurikuler->isNotEmpty())
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($studentsForAttentionKokurikuler->take(3) as $data)
                        @if($data->student)
                            <div class="py-2.5 flex items-center justify-between gap-3 min-h-[56px]">
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ $data->student->name }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $data->student->schoolClass->name ?? 'Kelas' }}</p>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if($data->alpa_count > 0)
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300">
                                            Alpa {{ $data->alpa_count }}x
                                        </span>
                                    @endif
                                    @if($data->bolos_count > 0)
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">
                                            Bolos {{ $data->bolos_count }}x
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="py-3 flex items-center gap-2.5 text-xs text-slate-600 dark:text-slate-300 min-h-[56px]">
                    <span class="material-icons text-emerald-500 text-lg">check_circle</span>
                    <span>Semua peserta didik aktif mengikuti proyek kokurikuler.</span>
                </div>
            @endif
        </div>

        <!-- KEAKTIFAN KOKURIKULER 30 HARI (2 METRIK & BAR PROGRES) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-indigo-500 text-base">insights</span>
                    <span>Keaktifan Kokurikuler (30 Hari)</span>
                </h3>
                <a href="{{ route('teacher.subject.attendance.charts', ['type' => 'cocurricular']) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5">
                    <span>Analitik</span>
                    <span class="material-icons text-sm">chevron_right</span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="p-3 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-900/40">
                    <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 block">Sesi Terlaksana</span>
                    <span class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $totalSessions30DaysKokurikuler }} Sesi</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block">Rata-rata Kehadiran</span>
                    <span class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400">{{ $avgAttendance30DaysKokurikuler }}%</span>
                </div>
            </div>

            @if(isset($classPerformanceDataKokurikuler) && count($classPerformanceDataKokurikuler) > 0)
                <div class="space-y-2.5 pt-2">
                    @foreach($classPerformanceDataKokurikuler as $perf)
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-700 dark:text-slate-300 truncate">{{ $perf['label'] }}</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $perf['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $perf['percentage'])) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- CATATAN FASILITATOR (AUTO-SAVE) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm"
             x-data="{
                 note: '{{ addslashes($teacherNote->content ?? ($teacherNote->note ?? '')) }}',
                 status: '',
                 timeout: null,
                 saveNote() {
                     clearTimeout(this.timeout);
                     this.status = 'Menyimpan...';
                     fetch('{{ route('teacher.notes.update') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}'
                         },
                         body: JSON.stringify({ note: this.note })
                     })
                     .then(res => res.json())
                     .then(data => {
                         if (data.success) {
                             this.status = 'Tersimpan';
                             this.timeout = setTimeout(() => { this.status = ''; }, 2000);
                         } else {
                             this.status = 'Gagal';
                         }
                     })
                     .catch(() => { this.status = 'Error'; });
                 }
             }">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-amber-500 text-base">edit_note</span>
                    <span>Catatan Fasilitator</span>
                </h3>
                <span x-text="status" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400"></span>
            </div>
            
            <textarea x-model="note" 
                      @input.debounce.800ms="saveNote()" 
                      rows="3" 
                      placeholder="Tuliskan perkembangan proyek, diferensiasi siswa, atau catatan kelas..." 
                      class="w-full text-xs rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 resize-none transition-all placeholder:text-slate-400"></textarea>
            <p class="text-[10px] text-slate-400 mt-1">✓ Tersimpan otomatis saat mengetik</p>
        </div>

        <!-- MODAL BOTTOM SHEET SEMUA JADWAL KOKURIKULER (MOBILE ONLY) -->
        <div x-show="showAllKokurikulerModal" class="relative z-50" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="showAllKokurikulerModal = false"></div>

            <!-- Bottom Sheet -->
            <div class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85vh] flex-col rounded-t-3xl bg-white dark:bg-slate-900 p-5 shadow-2xl border-t border-slate-200 dark:border-slate-800 transition-transform duration-300"
                 x-show="showAllKokurikulerModal"
                 x-transition:enter="transform transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full">
                
                <!-- Drag handle -->
                <div class="mx-auto h-1 w-9 rounded-full bg-slate-300 dark:bg-slate-700 mb-3"></div>

                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Semua Jadwal Kokurikuler</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Total {{ $allSchedulesKokurikuler->count() }} Sesi Terdaftar</p>
                    </div>
                    <button @click="showAllKokurikulerModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600">
                        <span class="material-icons text-xl">close</span>
                    </button>
                </div>

                <!-- Schedule List -->
                @php
                    $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                @endphp
                <div class="overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 max-h-[55vh] no-scrollbar pt-2">
                    @forelse($allSchedulesKokurikuler as $schedule)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">
                                        {{ $dayNames[$schedule->day_of_week] ?? 'Hari Lain' }}
                                    </span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                    </span>
                                </div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white mt-1">
                                    {{ $schedule->cocurricular?->title ?? 'Proyek Kokurikuler' }} — <span class="font-normal text-slate-500 dark:text-slate-400">Kelas {{ $schedule->schoolClass?->name ?? '-' }}</span>
                                </h4>
                            </div>

                            <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                               class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 text-xs font-bold text-slate-700 dark:text-slate-200 shrink-0">
                                Presensi
                            </a>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-6 text-center">Belum ada jadwal kokurikuler.</p>
                    @endforelse
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="showAllKokurikulerModal = false" class="w-full min-h-[44px] rounded-xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 text-xs font-bold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- 2. TAMPILAN DESKTOP (≥ sm breakpoint)                                      --}}
    {{-- Tetap 100% Utuh: Hero 8-cols, Tabbed Schedules, Chart, Auto-save Note      --}}
    {{-- ========================================================================= --}}
    <div class="hidden sm:block space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Kolom Utama: Jadwal Kokurikuler & Grafik (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Welcome Hero Banner -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-7 shadow-lg border border-indigo-900/50">
                    <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                    <div class="absolute top-0 right-1/4 w-32 h-32 rounded-full bg-sky-500/10 blur-2xl pointer-events-none"></div>
                    
                    <div class="relative z-10 flex items-center gap-4">
                        <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-indigo-500/20 bg-slate-800 shrink-0" 
                             src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=6366f1&background=e0e7ff' }}" 
                             alt="{{ Auth::user()->name }}">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-[10px] font-bold text-indigo-300 border border-indigo-400/20 mb-1">
                                <span class="material-icons text-xs">psychology</span>
                                <span>Fasilitator Kokurikuler</span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-snug">
                                Selamat Bertugas, {{ $teacher->name }}!
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Kelola presensi siswa pada jadwal sesi Proyek Kokurikuler dan pantau keaktifan peserta didik di setiap kelas binaan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Sesi Kokurikuler Timeline & Tabs -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden" x-data="{ activeTab: 'today' }">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-indigo-500 text-lg">calendar_month</span>
                                Jadwal Sesi Kokurikuler
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih jadwal proyek untuk membuka pemindai QR/Wajah dan absensi kelas</p>
                        </div>
                        
                        <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl self-start sm:self-auto border border-slate-200/60 dark:border-slate-700/60">
                            <button @click="activeTab = 'today'" 
                                    :class="activeTab === 'today' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" 
                                    class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all">
                                Hari Ini ({{ $schedulesTodayKokurikuler->count() }})
                            </button>
                            <button @click="activeTab = 'all'" 
                                    :class="activeTab === 'all' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'" 
                                    class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all">
                                Semua Jadwal ({{ $allSchedulesKokurikuler->count() }})
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Tab Jadwal Hari Ini -->
                        <div x-show="activeTab === 'today'" class="space-y-4">
                            @forelse($schedulesTodayKokurikuler as $schedule)
                                @php
                                    $isOngoing = now()->between(Carbon\Carbon::parse($schedule->start_time), Carbon\Carbon::parse($schedule->end_time));
                                    $isAssignedDirectly = $schedule->teacher_id === $teacher->id;
                                @endphp
                                <div class="p-4 sm:p-5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $isOngoing ? 'bg-indigo-50/70 dark:bg-indigo-950/30 border-indigo-300 dark:border-indigo-800 shadow-xs' : 'bg-slate-50 dark:bg-slate-850/60 border-slate-200/80 dark:border-slate-800' }}">
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $isOngoing ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                            <span class="material-icons text-2xl">psychology</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold {{ $isOngoing ? 'bg-indigo-200 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                </span>
                                                @if($isOngoing)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Sedang Berlangsung
                                                    </span>
                                                @endif
                                                @if($isAssignedDirectly)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/60 px-2 py-0.5 rounded-md">
                                                        Pengampu Langsung
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/60 px-2 py-0.5 rounded-md">
                                                        Fasilitator Pengganti: {{ $schedule->teacher?->name ?? '-' }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white mt-1.5">
                                                {{ $schedule->cocurricular?->title ?? 'Proyek Kokurikuler' }}
                                            </h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                Kelas Binaan: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $schedule->schoolClass?->name ?? '-' }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/25 transition-all shrink-0">
                                        <span class="material-icons text-base">qr_code_scanner</span>
                                        <span>Buka Sesi Presensi</span>
                                    </a>
                                </div>
                            @empty
                                <div class="text-center py-10 px-6">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                        <span class="material-icons text-2xl">event_busy</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Tidak ada jadwal kokurikuler untuk Anda hari ini.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Tab Semua Jadwal -->
                        <div x-show="activeTab === 'all'" class="space-y-3" style="display: none;">
                            @forelse($allSchedulesKokurikuler as $schedule)
                                @php
                                    $isAssignedDirectly = $schedule->teacher_id === $teacher->id;
                                @endphp
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                            <span class="material-icons text-xl">event_note</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">
                                                    {{ $dayNames[$schedule->day_of_week] ?? 'Hari Lain' }}
                                                </span>
                                                <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                </span>
                                                <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold {{ $isAssignedDirectly ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' : 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300' }}">
                                                    Guru: {{ $schedule->teacher?->name ?? '-' }}
                                                </span>
                                            </div>
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-1">
                                                {{ $schedule->cocurricular?->title ?? 'Proyek Kokurikuler' }} — <span class="font-normal text-slate-500 dark:text-slate-400">Kelas {{ $schedule->schoolClass?->name ?? '-' }}</span>
                                            </h4>
                                        </div>
                                    </div>

                                    <a href="{{ route('teacher.subject.attendance.scanner', ['schedule' => $schedule->id]) }}" 
                                       class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 text-xs font-bold transition-all shrink-0">
                                        <span class="material-icons text-sm text-indigo-500">qr_code_2</span>
                                        <span>Buka Presensi</span>
                                    </a>
                                </div>
                            @empty
                                <div class="text-center py-10 px-6">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                        <span class="material-icons text-2xl">event_busy</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada jadwal kokurikuler terdaftar untuk tim fasilitator Anda.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Performa Kehadiran per Kelas Kokurikuler -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-indigo-500 text-lg">bar_chart</span>
                            Performa Kehadiran Kelas Kokurikuler
                        </h3>
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                            30 Hari Terakhir
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rata-rata persentase kehadiran proyek kokurikuler pada kelas binaan Anda</p>
                    
                    @if(!empty($classPerformanceDataKokurikuler) && count($classPerformanceDataKokurikuler) > 0)
                        <div class="h-64 w-full">
                            <canvas id="cocurricularClassPerformanceChart"></canvas>
                        </div>
                    @else
                        <div class="py-10 px-4 text-center rounded-2xl bg-slate-50 dark:bg-slate-850/50 border border-dashed border-slate-200 dark:border-slate-800">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 mx-auto flex items-center justify-center mb-2.5 shadow-2xs">
                                <span class="material-icons text-2xl">insights</span>
                            </div>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Belum Ada Data Presensi Kokurikuler</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Grafik performa kelas akan terakumulasi otomatis saat sesi presensi kokurikuler dilaksanakan.</p>
                        </div>
                    @endif
                </div>

                <!-- Tombol Rekap Laporan & Analitik Grafis (Di Bawah Grafik Performa) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <a href="{{ route('teacher.subject.attendance.report', ['type' => 'cocurricular']) }}" 
                       class="group p-4 sm:p-4.5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                            <span class="material-icons text-2xl">table_chart</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Rekap Laporan</h4>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Cetak & Preview Presensi</p>
                        </div>
                        <span class="material-icons text-slate-400 text-base group-hover:translate-x-0.5 transition-transform shrink-0">chevron_right</span>
                    </a>

                    <a href="{{ route('teacher.subject.attendance.charts', ['type' => 'cocurricular']) }}" 
                       class="group p-4 sm:p-4.5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-700 shadow-2xs hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                            <span class="material-icons text-2xl">insights</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Analitik Grafis</h4>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Grafik & Analisis Tren</p>
                        </div>
                        <span class="material-icons text-slate-400 text-base group-hover:translate-x-0.5 transition-transform shrink-0">chevron_right</span>
                    </a>
                </div>
            </div>

            <!-- Kolom Samping (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Daftar Proyek Kokurikuler yang Diikuti -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-indigo-500 text-lg">auto_stories</span>
                            Tim Proyek Kokurikuler
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Daftar penugasan fasilitator proyek Anda</p>
                    </div>
                    <div class="p-5 space-y-3.5 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($myCocurricularProjects as $project)
                            <div class="{{ !$loop->first ? 'pt-3.5' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">
                                        {{ $project->level?->name ?? 'Tingkat' }} • {{ $project->time_allocation }} JP
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">
                                        {{ str_replace('_', ' ', $project->activity_type) }}
                                    </span>
                                </div>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-1">
                                    {{ $project->title }}
                                </h4>
                                
                                <!-- Anggota Tim Fasilitator -->
                                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] text-slate-400 font-semibold">Tim:</span>
                                    @foreach($project->teachers as $facTeacher)
                                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $facTeacher->id === $teacher->id ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                                            {{ $facTeacher->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-xs text-slate-400">
                                Belum ada data proyek kokurikuler yang ditugaskan.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Catatan Pribadi Guru -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <form id="note-form" action="{{ route('teacher.notes.update') }}" method="POST">
                        @csrf
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="material-icons text-amber-500 text-lg">edit_note</span>
                                    Catatan Fasilitator
                                </h3>
                                <div id="note-status" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 opacity-0 transition-opacity">
                                    Tersimpan
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-3">Pengingat progres proyek kelas binaan (tersimpan otomatis)</p>
                            
                            <textarea id="teacher-note-content" name="content" rows="4" 
                                      class="w-full border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-xs p-3 transition-all" 
                                      placeholder="Tulis catatan perkembangan proyek kokurikuler di sini...">{{ $teacherNote->content ?? '' }}</textarea>
                        </div>
                    </form>
                </div>

                <!-- Ringkasan Presensi Terakhir Kokurikuler -->
                @if($lastAttendanceSummaryKokurikuler)
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-indigo-500 text-lg">history</span>
                            Presensi Sesi Kokurikuler Terakhir
                        </h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Proyek & Kelas</span>
                            <h4 class="font-bold text-sm text-indigo-600 dark:text-indigo-400">
                                {{ $lastAttendanceSummaryKokurikuler['schedule']->cocurricular?->title ?? 'Proyek Kokurikuler' }}
                            </h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                Kelas {{ $lastAttendanceSummaryKokurikuler['schedule']->schoolClass?->name ?? '-' }}
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-5 gap-1.5 pt-2 text-center">
                            <div class="bg-emerald-50 dark:bg-emerald-950/40 p-2 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                                <span class="font-extrabold text-xs text-emerald-700 dark:text-emerald-300 block">{{ $lastAttendanceSummaryKokurikuler['hadir'] }}</span>
                                <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">Hadir</span>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-950/40 p-2 rounded-xl border border-amber-100 dark:border-amber-900/40">
                                <span class="font-extrabold text-xs text-amber-700 dark:text-amber-300 block">{{ $lastAttendanceSummaryKokurikuler['sakit'] }}</span>
                                <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400">Sakit</span>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-950/40 p-2 rounded-xl border border-purple-100 dark:border-purple-900/40">
                                <span class="font-extrabold text-xs text-purple-700 dark:text-purple-300 block">{{ $lastAttendanceSummaryKokurikuler['izin'] }}</span>
                                <span class="text-[9px] font-bold text-purple-600 dark:text-purple-400">Izin</span>
                            </div>
                            <div class="bg-red-50 dark:bg-red-950/40 p-2 rounded-xl border border-red-100 dark:border-red-900/40">
                                <span class="font-extrabold text-xs text-red-700 dark:text-red-300 block">{{ $lastAttendanceSummaryKokurikuler['alpa'] }}</span>
                                <span class="text-[9px] font-bold text-red-600 dark:text-red-400">Alpa</span>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-950/40 p-2 rounded-xl border border-orange-100 dark:border-orange-900/40">
                                <span class="font-extrabold text-xs text-orange-700 dark:text-orange-300 block">{{ $lastAttendanceSummaryKokurikuler['bolos'] }}</span>
                                <span class="text-[9px] font-bold text-orange-600 dark:text-orange-400">Bolos</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Siswa Butuh Perhatian (Kokurikuler) -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-rose-500 text-lg">priority_high</span>
                            Siswa Butuh Perhatian
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Berdasarkan ketidakhadiran di sesi kokurikuler semester ini</p>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80 max-h-60 overflow-y-auto no-scrollbar">
                        @forelse($studentsForAttentionKokurikuler as $data)
                            @if($data->student)
                            <div class="p-3.5 flex justify-between items-center hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                                <div>
                                    <p class="font-bold text-xs text-slate-800 dark:text-white">{{ $data->student->name }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $data->student->schoolClass->name ?? '' }}</p>
                                </div>
                                <div class="text-right flex items-center gap-1.5 shrink-0">
                                    @if($data->alpa_count > 0)
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300">
                                            Alpa: {{ $data->alpa_count }}
                                        </span>
                                    @endif
                                    @if($data->bolos_count > 0)
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                            Bolos: {{ $data->bolos_count }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @empty
                            <div class="p-6 text-center text-xs text-slate-400 italic">
                                Tidak ada siswa yang perlu perhatian khusus di kokurikuler.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isDarkMode = document.documentElement.classList.contains('dark');
        const chartElement = document.getElementById('cocurricularClassPerformanceChart');
        
        if (chartElement) {
            const performanceData = @json($classPerformanceDataKokurikuler ?? []);
            
            if (performanceData && performanceData.length > 0) {
                const ctx = chartElement.getContext('2d');
                const labels = performanceData.map(d => d.label);
                const data = performanceData.map(d => d.percentage);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Kehadiran Rata-rata (%)',
                            data: data,
                            backgroundColor: isDarkMode ? 'rgba(99, 102, 241, 0.8)' : 'rgba(79, 70, 229, 0.85)',
                            hoverBackgroundColor: isDarkMode ? 'rgba(129, 140, 248, 0.95)' : 'rgba(99, 102, 241, 0.95)',
                            borderWidth: 0,
                            borderRadius: 8,
                            maxBarThickness: 28
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                ticks: { 
                                    callback: (value) => value + '%',
                                    color: isDarkMode ? '#94a3b8' : '#64748b',
                                    font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                                },
                                grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }
                            },
                            y: {
                                 ticks: { 
                                     color: isDarkMode ? '#94a3b8' : '#64748b',
                                     font: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 11, weight: '600' }
                                 },
                                 grid: { display: false }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDarkMode ? '#1e293b' : '#0f172a',
                                titleFont: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 12, weight: 'bold' },
                                bodyFont: { family: '"Plus Jakarta Sans", Inter, sans-serif', size: 12 },
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label: (context) => ' Kehadiran Rata-rata: ' + context.parsed.x + '%'
                                }
                            }
                        }
                    }
                });
            }
        }

        // Auto-save teacher notes logic
        const noteForm = document.getElementById('note-form');
        const noteContent = document.getElementById('teacher-note-content');
        const noteStatus = document.getElementById('note-status');
        let saveTimeout;

        if (noteContent && noteForm) {
            noteContent.addEventListener('input', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    saveNote();
                }, 1500);
            });
        }

        function saveNote() {
            if (!noteForm) return;
            const formData = new FormData(noteForm);

            fetch(noteForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    noteStatus.textContent = 'Tersimpan!';
                    noteStatus.classList.remove('text-rose-600');
                    noteStatus.classList.add('text-emerald-600');
                    noteStatus.style.opacity = '1';
                    setTimeout(() => {
                        noteStatus.style.opacity = '0';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                noteStatus.textContent = 'Gagal menyimpan.';
                noteStatus.classList.remove('text-emerald-600');
                noteStatus.classList.add('text-rose-600');
                noteStatus.style.opacity = '1';
            });
        }
    });
</script>
@endpush
