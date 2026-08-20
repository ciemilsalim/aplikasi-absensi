<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Jurnal Mengajar', 'url' => route('teacher.journals.index')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Jurnal Mengajar Guru Mata Pelajaran
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    SMP Negeri 1 Biau &bull; Fase D &bull; Tahun Pelajaran 2026/2027
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('teacher.journals.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all active:scale-95">
                    <span class="material-icons text-base">add_circle</span>
                    <span>Tulis Jurnal Baru</span>
                </a>
                
                <a href="{{ route('teacher.journals.print') }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200/80 dark:border-slate-700 transition-all">
                    <span class="material-icons text-base text-amber-500">print</span>
                    <span>Cetak Jurnal</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 text-xs font-semibold">
                <span class="material-icons text-lg text-emerald-500">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="flex items-center gap-3 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 text-xs font-semibold">
                <span class="material-icons text-lg text-rose-500">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Quick Tabs Navigation -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-none">
            <a href="{{ route('teacher.journals.index') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-sky-600 text-white text-xs font-extrabold shadow-sm whitespace-nowrap shrink-0">
                <span class="material-icons text-sm">menu_book</span>
                <span>Jurnal Pelaksanaan</span>
            </a>
            <a href="{{ route('teacher.journals.weekly') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-sky-500">calendar_view_week</span>
                <span>Rekap Mingguan</span>
            </a>
            <a href="{{ route('teacher.journals.semester') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-indigo-500">assessment</span>
                <span>Rekap Semester & Asesmen</span>
            </a>
            <a href="{{ route('teacher.journals.reflection') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-amber-500">psychology</span>
                <span>Refleksi Guru</span>
            </a>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Entri Jurnal</span>
                    <div class="w-10 h-10 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-xl">auto_stories</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white">{{ $totalJournals }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Pertemuan pembelajaran tercatat</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Jam Pelajaran</span>
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-xl">schedule</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white">{{ $totalJp }} <span class="text-sm font-bold text-slate-400">JP</span></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi durasi tatap muka</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Supervisi</span>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-xl">verified</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $verifiedCount }} <span class="text-sm font-bold text-slate-400">/ {{ $totalJournals }}</span></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Telah diperiksa Kepala Sekolah/Waka</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelas / Mapel Diampu</span>
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-xl">class</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white">{{ $classes->count() }} <span class="text-sm font-bold text-slate-400">Kelas</span></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $subjects->pluck('name')->implode(', ') ?: 'Mata Pelajaran' }}</p>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <form action="{{ route('teacher.journals.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label for="school_class_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Kelas</label>
                    <select name="school_class_id" id="school_class_id" class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Mata Pelajaran</label>
                    <select name="subject_id" id="subject_id" class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">Semua Mapel</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="month" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Bulan</label>
                    <input type="month" name="month" id="month" value="{{ request('month') }}" 
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Supervisi</label>
                    <select name="status" id="status" class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">Semua Status</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Telah Diverifikasi</option>
                        <option value="unverified" {{ request('status') === 'unverified' ? 'selected' : '' }}>Belum Diverifikasi</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm transition-all active:scale-95">
                        <span class="material-icons text-sm">filter_list</span>
                        <span>Filter</span>
                    </button>
                    @if(request()->hasAny(['school_class_id', 'subject_id', 'month', 'status']))
                        <a href="{{ route('teacher.journals.index') }}" class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 text-xs font-bold transition-all" title="Reset Filter">
                            <span class="material-icons text-sm">refresh</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Jurnal Pelaksanaan Pembelajaran -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Jurnal Pelaksanaan Pembelajaran</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Catatan keterlaksanaan tatap muka, asesmen, refleksi dan tindak lanjut</p>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 self-start sm:self-auto">
                    {{ $journals->total() }} Data Ditemukan
                </span>
            </div>

            @if($journals->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-16 h-16 rounded-3xl bg-sky-50 dark:bg-sky-950/40 text-sky-500 mx-auto flex items-center justify-center mb-3">
                        <span class="material-icons text-3xl">menu_book</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum Ada Jurnal Mengajar</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                        Anda belum memiliki entri jurnal untuk filter yang dipilih. Silakan klik tombol di bawah untuk menulis jurnal baru.
                    </p>
                    <a href="{{ route('teacher.journals.create') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all">
                        <span class="material-icons text-sm">add_circle</span>
                        <span>Mulai Isi Jurnal Mengajar</span>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5 text-center w-12">No.</th>
                                <th class="p-3.5">Hari / Tanggal</th>
                                <th class="p-3.5">Kelas & Mapel</th>
                                <th class="p-3.5 text-center">JP</th>
                                <th class="p-3.5 min-w-[180px]">Tujuan Pembelajaran</th>
                                <th class="p-3.5 min-w-[140px]">Topik / Materi</th>
                                <th class="p-3.5 min-w-[180px]">Kegiatan</th>
                                <th class="p-3.5">Asesmen</th>
                                <th class="p-3.5 min-w-[160px]">Hasil / Refleksi</th>
                                <th class="p-3.5 min-w-[140px]">Tindak Lanjut</th>
                                <th class="p-3.5 text-center">Supervisi</th>
                                <th class="p-3.5 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @foreach($journals as $index => $journal)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-3.5 text-center font-bold text-slate-400">{{ $journals->firstItem() + $index }}</td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $journal->date->translatedFormat('l') }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $journal->date->translatedFormat('d F Y') }}</div>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 font-extrabold text-[11px]">
                                            {{ $journal->schoolClass?->name ?? $journal->schedule?->getTargetClass()?->name ?? '-' }}
                                        </span>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                                            {{ $journal->subject?->name ?? $journal->schedule?->getActivityName() ?? '-' }}
                                        </div>
                                        <!-- Kehadiran Badge -->
                                        <div class="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                                            <span title="Hadir" class="text-emerald-600 font-bold">H: {{ $journal->attendance_hadir }}</span> &bull;
                                            <span title="Sakit" class="text-amber-500 font-bold">S: {{ $journal->attendance_sakit }}</span> &bull;
                                            <span title="Izin" class="text-sky-500 font-bold">I: {{ $journal->attendance_izin }}</span> &bull;
                                            <span title="Alpa" class="text-rose-500 font-bold">A: {{ $journal->attendance_alpa }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center font-extrabold text-slate-900 dark:text-white">
                                        {{ $journal->jp }}
                                    </td>
                                    <td class="p-3.5">
                                        <p class="line-clamp-2 text-xs leading-relaxed" title="{{ $journal->learning_objective }}">
                                            {{ $journal->learning_objective }}
                                        </p>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $journal->topic }}</span>
                                    </td>
                                    <td class="p-3.5">
                                        <p class="line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400" title="{{ $journal->activity }}">
                                            {{ $journal->activity }}
                                        </p>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold">
                                            {{ $journal->assessment }}
                                        </span>
                                    </td>
                                    <td class="p-3.5">
                                        <p class="line-clamp-2 text-xs leading-relaxed" title="{{ $journal->reflection }}">
                                            {{ $journal->reflection }}
                                        </p>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            {{ $journal->follow_up }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        @if($journal->is_verified)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold" title="Diverifikasi oleh {{ $journal->verifier?->name ?? 'Supervisor' }}">
                                                <span class="material-icons text-xs">check_circle</span>
                                                <span>Disupervisi</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 text-[10px] font-bold">
                                                <span class="material-icons text-xs">pending</span>
                                                <span>Belum</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('teacher.journals.edit', $journal) }}" 
                                               class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-sky-950 hover:text-sky-600 transition-colors" title="Edit Jurnal">
                                                <span class="material-icons text-base">edit</span>
                                            </a>
                                            <form action="{{ route('teacher.journals.destroy', $journal) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-rose-50 dark:hover:bg-rose-950 hover:text-rose-600 transition-colors" title="Hapus Jurnal">
                                                    <span class="material-icons text-base">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($journals->hasPages())
                    <div class="p-4 border-t border-slate-200/80 dark:border-slate-800">
                        {{ $journals->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
