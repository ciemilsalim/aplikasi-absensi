<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Jurnal Mengajar', 'url' => route('teacher.journals.index')],
                    ['title' => 'Rekap Mingguan', 'url' => route('teacher.journals.weekly')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    C. Rekap Jurnal Mengajar Mingguan
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    SMP Negeri 1 Biau &bull; Ringkasan Progres Pembelajaran Mingguan Guru
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('teacher.journals.print', ['month' => $selectedMonth, 'school_class_id' => $selectedClassId, 'subject_id' => $selectedSubjectId]) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200/80 dark:border-slate-700 transition-all">
                    <span class="material-icons text-base text-amber-500">print</span>
                    <span>Cetak Rekap Ini</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Quick Tabs Navigation -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <a href="{{ route('teacher.journals.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all">
                <span class="material-icons text-sm text-sky-500">menu_book</span>
                <span>Jurnal Pelaksanaan</span>
            </a>
            <a href="{{ route('teacher.journals.weekly') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-sky-600 text-white text-xs font-extrabold shadow-sm">
                <span class="material-icons text-sm">calendar_view_week</span>
                <span>Rekap Mingguan</span>
            </a>
            <a href="{{ route('teacher.journals.semester') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all">
                <span class="material-icons text-sm text-indigo-500">assessment</span>
                <span>Rekap Semester & Asesmen</span>
            </a>
            <a href="{{ route('teacher.journals.reflection') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all">
                <span class="material-icons text-sm text-amber-500">psychology</span>
                <span>Refleksi Guru</span>
            </a>
        </div>

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <form action="{{ route('teacher.journals.weekly') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                <div>
                    <label for="school_class_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Kelas</label>
                    <select name="school_class_id" id="school_class_id" class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Mata Pelajaran</label>
                    <select name="subject_id" id="subject_id" class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ $selectedSubjectId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="month" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Bulan</label>
                    <input type="month" name="month" id="month" value="{{ $selectedMonth }}" 
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <div>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm transition-all active:scale-95">
                        <span class="material-icons text-sm">filter_alt</span>
                        <span>Tampilkan Rekap</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Bagian C -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">C. Rekap Jurnal Mengajar Mingguan</h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Bulan: <strong class="text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($selectedMonth . '-01')->translatedFormat('F Y') }}</strong> &bull; 
                        Kelas: <strong class="text-slate-700 dark:text-slate-200">{{ $classes->firstWhere('id', $selectedClassId)?->name ?? '-' }}</strong>
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5 text-center w-20">Minggu Ke-</th>
                            <th class="p-3.5 w-36">Periode</th>
                            <th class="p-3.5 w-24">Kelas</th>
                            <th class="p-3.5 text-center w-28">Jml Pertemuan</th>
                            <th class="p-3.5 text-center w-24">Jumlah JP</th>
                            <th class="p-3.5 min-w-[240px]">TP yang Dilaksanakan</th>
                            <th class="p-3.5 w-36">Asesmen Dominan</th>
                            <th class="p-3.5 min-w-[200px]">Catatan / Tindak Lanjut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($weeklyData as $row)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3.5 text-center font-black text-slate-800 dark:text-white">{{ $row['week_number'] }}</td>
                                <td class="p-3.5 whitespace-nowrap font-medium text-slate-600 dark:text-slate-300">{{ $row['period'] }}</td>
                                <td class="p-3.5 font-bold text-sky-600 dark:text-sky-400">{{ $row['class_name'] }}</td>
                                <td class="p-3.5 text-center font-extrabold text-slate-900 dark:text-white">{{ $row['meeting_count'] }}</td>
                                <td class="p-3.5 text-center font-extrabold text-indigo-600 dark:text-indigo-400">{{ $row['total_jp'] }}</td>
                                <td class="p-3.5">
                                    <p class="text-xs leading-relaxed text-slate-800 dark:text-slate-200">{{ $row['tp_conducted'] }}</p>
                                </td>
                                <td class="p-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold">
                                        {{ $row['dominant_assessment'] }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">
                                    {{ $row['notes_follow_up'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400">
                                    Tidak ada data rekap pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
