<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Jurnal Mengajar', 'url' => route('teacher.journals.index')],
                    ['title' => 'Rekap Semester', 'url' => route('teacher.journals.semester')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Rekap Pelaksanaan Pembelajaran & Asesmen Semester
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    SMP Negeri 1 Biau &bull; Tahun Pelajaran {{ $academicYear->name ?? '2026/2027' }} (Semester {{ $semester->name ?? 'Ganjil' }})
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('teacher.journals.print', ['subject_id' => $selectedSubjectId]) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200/80 dark:border-slate-700 transition-all">
                    <span class="material-icons text-base text-amber-500">print</span>
                    <span>Cetak Rekap Lengkap</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Quick Tabs Navigation -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-none">
            <a href="{{ route('teacher.journals.index') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-sky-500">menu_book</span>
                <span>Jurnal Pelaksanaan</span>
            </a>
            <a href="{{ route('teacher.journals.weekly') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-sky-500">calendar_view_week</span>
                <span>Rekap Mingguan</span>
            </a>
            <a href="{{ route('teacher.journals.semester') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-sky-600 text-white text-xs font-extrabold shadow-sm whitespace-nowrap shrink-0">
                <span class="material-icons text-sm">assessment</span>
                <span>Rekap Semester & Asesmen</span>
            </a>
            <a href="{{ route('teacher.journals.reflection') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-amber-500">psychology</span>
                <span>Refleksi Guru</span>
            </a>
        </div>

        <!-- Filter Mata Pelajaran -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <form action="{{ route('teacher.journals.semester') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="subject_id" class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Pilih Mata Pelajaran</label>
                    <select name="subject_id" id="subject_id" class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ $selectedSubjectId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm transition-all">
                    <span class="material-icons text-sm">filter_list</span>
                    <span>Terapkan</span>
                </button>
            </form>
        </div>

        <!-- Rekap Pelaksanaan Pembelajaran Semester -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Rekap Pelaksanaan Pembelajaran Semester</h2>
                <p class="text-xs text-slate-400 mt-0.5">Perbandingan jam pelajaran yang direncanakan vs terlaksana serta ketuntasan tujuan pembelajaran</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5 text-center w-12">No.</th>
                            <th class="p-3.5 w-28">Kelas</th>
                            <th class="p-3.5 text-center w-36">Jml JP Direncanakan</th>
                            <th class="p-3.5 text-center w-36">Jml JP Terlaksana</th>
                            <th class="p-3.5 text-center w-28">Persentase</th>
                            <th class="p-3.5 text-center w-28">TP Selesai</th>
                            <th class="p-3.5 text-center w-28">TP Belum Selesai</th>
                            <th class="p-3.5 min-w-[160px]">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @foreach($semesterClassData as $index => $row)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3.5 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="p-3.5 font-black text-slate-800 dark:text-white">{{ $row['class_name'] }}</td>
                                <td class="p-3.5 text-center font-semibold">{{ $row['planned_jp'] }} JP</td>
                                <td class="p-3.5 text-center font-bold text-sky-600 dark:text-sky-400">{{ $row['actual_jp'] }} JP</td>
                                <td class="p-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $row['percentage'] >= 80 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }} text-[11px] font-bold">
                                        {{ $row['percentage'] }}%
                                    </span>
                                </td>
                                <td class="p-3.5 text-center font-bold text-emerald-600">{{ $row['tp_done_count'] }}</td>
                                <td class="p-3.5 text-center font-bold text-amber-500">{{ $row['tp_pending_count'] }}</td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $row['notes'] }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50/80 dark:bg-slate-800/70 font-extrabold text-slate-900 dark:text-white border-t border-slate-300 dark:border-slate-700">
                            <td colspan="2" class="p-3.5 text-right uppercase tracking-wider text-[11px]">Jumlah / Total</td>
                            <td class="p-3.5 text-center">{{ $totalPlannedJp }} JP</td>
                            <td class="p-3.5 text-center text-sky-600 dark:text-sky-400">{{ $totalActualJp }} JP</td>
                            <td class="p-3.5 text-center">
                                {{ $totalPlannedJp > 0 ? round(($totalActualJp / $totalPlannedJp) * 100, 1) : 0 }}%
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rekap Asesmen dan Tindak Lanjut -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Rekap Asesmen dan Tindak Lanjut</h2>
                <p class="text-xs text-slate-400 mt-0.5">Penilaian ketercapaian tujuan pembelajaran dan tindak lanjut remedial/pengayaan</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5 text-center w-12">No.</th>
                            <th class="p-3.5 w-24">Kelas</th>
                            <th class="p-3.5 min-w-[200px]">TP / Topik</th>
                            <th class="p-3.5 w-32">Bentuk Asesmen</th>
                            <th class="p-3.5 min-w-[180px]">Hasil / Ketercapaian TP</th>
                            <th class="p-3.5 min-w-[160px]">Tindak Lanjut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($assessmentJournals as $index => $j)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3.5 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="p-3.5 font-bold text-sky-600 dark:text-sky-400">{{ $j->schoolClass?->name ?? '-' }}</td>
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $j->topic }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $j->learning_objective }}</div>
                                </td>
                                <td class="p-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold">
                                        {{ $j->assessment }}
                                    </span>
                                </td>
                                <td class="p-3.5">
                                    <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 line-clamp-2">
                                        {{ $j->reflection }}
                                    </p>
                                </td>
                                <td class="p-3.5">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $j->follow_up }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    Belum ada data rekaman asesmen untuk mata pelajaran ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
