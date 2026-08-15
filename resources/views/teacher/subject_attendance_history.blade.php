<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Riwayat Absensi Mapel', 'url' => route('teacher.subject.attendance.history')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Riwayat Presensi Mata Pelajaran
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span class="material-icons text-xs text-sky-500">event</span>
                    <span>{{ $selectedDate->translatedFormat('l, d F Y') }}</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        
        <!-- Filter Tanggal Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-5">
            <form action="{{ route('teacher.subject.attendance.history') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="date" class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Pilih Tanggal</label>
                    <input type="date" name="date" id="date" value="{{ $selectedDate->format('Y-m-d') }}" 
                           class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>
                <button type="submit" 
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all active:scale-95">
                    <span class="material-icons text-sm">calendar_month</span>
                    <span>Tampilkan Riwayat</span>
                </button>
            </form>
        </div>

        @if($attendances->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-12 text-center">
                <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 mx-auto flex items-center justify-center mb-3">
                    <span class="material-icons text-3xl">event_busy</span>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak Ada Rekaman Presensi</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Belum ada data presensi siswa yang tersimpan untuk tanggal <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $selectedDate->translatedFormat('d F Y') }}</span>.
                </p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($attendances as $scheduleId => $attendanceGroup)
                    @php
                        $firstRecord = $attendanceGroup->first();
                        $scheduleInfo = $firstRecord->schedule;
                        $assignment = $scheduleInfo->teachingAssignment;
                        $hadirCount = $attendanceGroup->where('status', 'hadir')->count();
                        $sakitCount = $attendanceGroup->where('status', 'sakit')->count();
                        $izinCount = $attendanceGroup->where('status', 'izin')->count();
                        $alpaCount = $attendanceGroup->where('status', 'alpa')->count();
                        $bolosCount = $attendanceGroup->where('status', 'bolos')->count();
                    @endphp
                    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                        <!-- Group Header -->
                        <div class="p-6 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                    <span class="material-icons">class</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                        {{ $assignment->subject->name }} <span class="text-slate-400 font-normal">|</span> Kelas {{ $assignment->schoolClass->name }}
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <span class="material-icons text-xs text-slate-400">schedule</span>
                                        <span>Pukul {{ \Carbon\Carbon::parse($scheduleInfo->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($scheduleInfo->end_time)->format('H:i') }} WIB</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Group Counters -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold text-[11px]">
                                    Hadir: {{ $hadirCount }}
                                </span>
                                @if($sakitCount > 0)
                                    <span class="px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-bold text-[11px]">
                                        Sakit: {{ $sakitCount }}
                                    </span>
                                @endif
                                @if($izinCount > 0)
                                    <span class="px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 font-bold text-[11px]">
                                        Izin: {{ $izinCount }}
                                    </span>
                                @endif
                                @if($alpaCount > 0)
                                    <span class="px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 font-bold text-[11px]">
                                        Alpa: {{ $alpaCount }}
                                    </span>
                                @endif
                                @if($bolosCount > 0)
                                    <span class="px-2.5 py-1 rounded-full bg-orange-100 dark:bg-orange-950/60 text-orange-800 dark:text-orange-300 font-bold text-[11px]">
                                        Bolos: {{ $bolosCount }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Student Records List -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($attendanceGroup as $record)
                                <div class="px-6 py-3.5 flex items-center justify-between hover:bg-slate-50/50 dark:hover:bg-slate-850/30 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($record->student->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white block">{{ $record->student->name }}</span>
                                            <span class="text-[10px] text-slate-400">NISN / NIS: {{ $record->student->nisn ?? $record->student->nis ?? '-' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline-flex items-center gap-1">
                                            <span class="material-icons text-xs">access_time</span>
                                            {{ $record->created_at->format('H:i:s') }}
                                        </span>
                                        
                                        @php
                                            $statusMap = [
                                                'hadir' => ['label' => 'Hadir', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 ring-1 ring-emerald-500/20'],
                                                'sakit' => ['label' => 'Sakit', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 ring-1 ring-amber-500/20'],
                                                'izin'  => ['label' => 'Izin',  'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 ring-1 ring-purple-500/20'],
                                                'alpa'  => ['label' => 'Alpa',  'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 ring-1 ring-rose-500/20'],
                                                'bolos' => ['label' => 'Bolos', 'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300 ring-1 ring-orange-500/20'],
                                            ];
                                            $st = $statusMap[$record->status] ?? ['label' => ucfirst($record->status), 'class' => 'bg-slate-100 text-slate-700'];
                                        @endphp
                                        <span class="px-3 py-1 text-xs font-extrabold rounded-full {{ $st['class'] }}">
                                            {{ $st['label'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-app-layout>

