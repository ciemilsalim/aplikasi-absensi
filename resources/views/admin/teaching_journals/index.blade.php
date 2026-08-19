<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Admin Dasbor', 'url' => route('dashboard')],
                    ['title' => 'Supervisi Jurnal Mengajar', 'url' => route('admin.teaching_journals.index')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Supervisi Jurnal Mengajar Guru
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    Monitoring & Validasi Pembelajaran Guru Mata Pelajaran SMP Negeri 1 Biau
                </p>
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

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Jurnal Masuk</span>
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
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Telah Disupervisi</span>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-xl">verified</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $verifiedJournals }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Divalidasi Kepala Sekolah/Waka</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Menunggu Supervisi</span>
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-xl">pending_actions</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ $unverifiedJournals }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Perlu diperiksa & divalidasi</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Guru Aktif Mengisi</span>
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-xl">people</span>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white">{{ $activeTeachersCount }} <span class="text-sm font-bold text-slate-400">/ {{ $teachers->count() }}</span></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Kepatuhan administrasi jurnal</p>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <form action="{{ route('admin.teaching_journals.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label for="teacher_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Pilih Guru</label>
                    <select name="teacher_id" id="teacher_id" class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        <option value="">Semua Guru</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

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
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Status Supervisi</label>
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
                    @if(request()->hasAny(['teacher_id', 'school_class_id', 'subject_id', 'status']))
                        <a href="{{ route('admin.teaching_journals.index') }}" class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 text-xs font-bold transition-all" title="Reset Filter">
                            <span class="material-icons text-sm">refresh</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Supervisi Jurnal -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Daftar Entri Jurnal Mengajar</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Tinjau kesesuaian TP, materi, asesmen, dan berikan validasi supervisi</p>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 self-start sm:self-auto">
                    {{ $journals->total() }} Data Ditemukan
                </span>
            </div>

            @if($journals->isEmpty())
                <div class="p-12 text-center text-slate-400">
                    <span class="material-icons text-4xl text-slate-300 mb-2">find_in_page</span>
                    <p class="text-xs font-semibold">Tidak ada data jurnal yang sesuai dengan kriteria filter.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-3.5 text-center w-12">No.</th>
                                <th class="p-3.5">Guru Pengampu</th>
                                <th class="p-3.5">Tanggal & Kelas</th>
                                <th class="p-3.5 min-w-[200px]">TP & Topik</th>
                                <th class="p-3.5 min-w-[180px]">Kegiatan & Asesmen</th>
                                <th class="p-3.5 min-w-[160px]">Hasil & Tindak Lanjut</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center w-32">Aksi Supervisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @foreach($journals as $index => $j)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-3.5 text-center font-bold text-slate-400">{{ $journals->firstItem() + $index }}</td>
                                    <td class="p-3.5">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $j->teacher?->name ?? '-' }}</div>
                                        <div class="text-[11px] text-slate-400">NIP: {{ $j->teacher?->nip ?: '-' }}</div>
                                        <a href="{{ route('admin.teaching_journals.show', $j->teacher_id) }}" class="text-[10px] font-bold text-sky-600 dark:text-sky-400 hover:underline mt-0.5 inline-block">
                                            Lihat Rekap Guru &rarr;
                                        </a>
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $j->date->translatedFormat('d M Y') }}</div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 font-extrabold text-[11px] mt-0.5">
                                            {{ $j->schoolClass?->name ?? '-' }}
                                        </span>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $j->subject?->name ?? '-' }} &bull; {{ $j->jp }} JP</div>
                                    </td>
                                    <td class="p-3.5">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $j->topic }}</div>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 mt-0.5" title="{{ $j->learning_objective }}">
                                            {{ $j->learning_objective }}
                                        </p>
                                    </td>
                                    <td class="p-3.5">
                                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2" title="{{ $j->activity }}">
                                            {{ $j->activity }}
                                        </p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold mt-1">
                                            {{ $j->assessment }}
                                        </span>
                                    </td>
                                    <td class="p-3.5">
                                        <p class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2" title="{{ $j->reflection }}">
                                            {{ $j->reflection }}
                                        </p>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-1">
                                            TL: {{ $j->follow_up }}
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        @if($j->is_verified)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 text-[11px] font-bold" title="Diverifikasi oleh {{ $j->verifier?->name ?? 'Supervisor' }} pada {{ $j->verified_at?->format('d/m/Y H:i') }}">
                                                <span class="material-icons text-xs">check_circle</span>
                                                <span>Disetujui</span>
                                            </span>
                                            @if($j->supervisor_notes)
                                                <div class="text-[10px] text-slate-400 italic max-w-[120px] truncate mx-auto mt-0.5" title="{{ $j->supervisor_notes }}">
                                                    "{{ $j->supervisor_notes }}"
                                                </div>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 text-[11px] font-bold">
                                                <span class="material-icons text-xs">pending</span>
                                                <span>Belum Diperiksa</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <form action="{{ route('admin.teaching_journals.verify', $j) }}" method="POST" class="inline">
                                            @csrf
                                            @if($j->is_verified)
                                                <input type="hidden" name="is_verified" value="0">
                                                <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 font-bold text-[10px] transition-all">
                                                    Batalkan
                                                </button>
                                            @else
                                                <input type="hidden" name="is_verified" value="1">
                                                <input type="hidden" name="supervisor_notes" value="Disupervisi & Sesuai TP">
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] shadow-sm transition-all active:scale-95">
                                                    <span class="material-icons text-xs">check</span>
                                                    <span>Setujui</span>
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($journals->hasPages())
                    <div class="p-4 border-t border-slate-200/80 dark:border-slate-800">
                        {{ $journals->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
