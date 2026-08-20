<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Jurnal Mengajar', 'url' => route('teacher.journals.index')],
                    ['title' => 'Edit Jurnal', 'url' => route('teacher.journals.edit', $journal)]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Edit Jurnal Mengajar
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    {{ $journal->schoolClass?->name ?? '-' }} &bull; {{ $journal->subject?->name ?? '-' }} &bull; {{ $journal->date->translatedFormat('d F Y') }}
                </p>
            </div>

            <a href="{{ route('teacher.journals.index') }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 font-bold text-xs transition-all self-start sm:self-auto">
                <span class="material-icons text-base">arrow_back</span>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 text-xs">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <span class="material-icons text-base">error_outline</span>
                    <span>Terdapat beberapa kesalahan pengisian:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('teacher.journals.update', $journal) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Card 1: Data Jadwal & Waktu -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-lg">event_available</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">1. Informasi Pertemuan & Jadwal</h2>
                        <p class="text-[11px] text-slate-400">Jadwal dan tanggal pelaksanaan pembelajaran</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="schedule_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            Jadwal Pelajaran / Kelas <span class="text-rose-500">*</span>
                        </label>
                        <select name="schedule_id" id="schedule_id" required 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                            @foreach($schedules as $sched)
                                <option value="{{ $sched->id }}" {{ (old('schedule_id', $journal->schedule_id) == $sched->id) ? 'selected' : '' }}>
                                    {{ $sched->getDayName() }}: {{ $sched->getTargetClass()?->name ?? '-' }} - {{ $sched->getActivityName() }} ({{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            Hari / Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" id="date" required 
                               value="{{ old('date', $journal->date->format('Y-m-d')) }}"
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label for="jp" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            Jumlah Jam (JP) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="jp" id="jp" min="1" max="10" required 
                               value="{{ old('jp', $journal->jp) }}"
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-3 p-3 rounded-2xl bg-sky-50/50 dark:bg-slate-800/60 border border-sky-100 dark:border-slate-700/60">
                        <span class="material-icons text-sky-500 text-xl shrink-0">fact_check</span>
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200">Presensi Siswa Sesi Ini:</span>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                <span class="text-emerald-600">Hadir: <strong>{{ $journal->attendance_hadir }}</strong></span> &bull;
                                <span class="text-amber-500">Sakit: <strong>{{ $journal->attendance_sakit }}</strong></span> &bull;
                                <span class="text-sky-500">Izin: <strong>{{ $journal->attendance_izin }}</strong></span> &bull;
                                <span class="text-rose-500">Alpa: <strong>{{ $journal->attendance_alpa }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Konten Pembelajaran Inti -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-icons text-lg">menu_book</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">2. Tujuan, Topik & Aktivitas Pembelajaran</h2>
                        <p class="text-[11px] text-slate-400">Ringkasan substansi materi yang diajarkan</p>
                    </div>
                </div>

                <!-- Topik / Materi -->
                <div>
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Topik / Materi Pokok <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="topic" id="topic" required 
                           value="{{ old('topic', $journal->topic) }}"
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <!-- Tujuan Pembelajaran (TP) -->
                <div>
                    <label for="learning_objective" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Tujuan Pembelajaran (TP) <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="learning_objective" id="learning_objective" rows="2" required 
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('learning_objective', $journal->learning_objective) }}</textarea>
                </div>

                <!-- Kegiatan Pembelajaran -->
                <div>
                    <label for="activity" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Kegiatan Pembelajaran <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="activity" id="activity" rows="3" required 
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('activity', $journal->activity) }}</textarea>
                </div>
            </div>

            <!-- Card 3: Asesmen, Refleksi & Tindak Lanjut -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-lg">assessment</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">3. Asesmen, Refleksi & Tindak Lanjut</h2>
                        <p class="text-[11px] text-slate-400">Hasil observasi ketercapaian TP dan rencana tindak lanjut</p>
                    </div>
                </div>

                <!-- Asesmen -->
                <div>
                    <label for="assessment" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Bentuk Asesmen <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        @foreach(['Observasi', 'LKPD', 'Kuis', 'Praktik / Unjuk Kerja', 'Presentasi', 'Produk', 'Tes Tertulis'] as $tag)
                            <button type="button" onclick="selectAssessmentTag('{{ $tag }}')" 
                                    class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 hover:text-emerald-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                                + {{ $tag }}
                            </button>
                        @endforeach
                    </div>
                    <input type="text" name="assessment" id="assessment" required 
                           value="{{ old('assessment', $journal->assessment) }}"
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <!-- Hasil / Refleksi -->
                <div>
                    <label for="reflection" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Hasil / Refleksi Pembelajaran <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="reflection" id="reflection" rows="2" required 
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('reflection', $journal->reflection) }}</textarea>
                </div>

                <!-- Tindak Lanjut -->
                <div>
                    <label for="follow_up" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Tindak Lanjut <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="follow_up" id="follow_up" required 
                           value="{{ old('follow_up', $journal->follow_up) }}"
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <!-- Catatan Tambahan (Opsional) -->
                <div>
                    <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Catatan Kejadian / Catatan Khusus (Opsional)
                    </label>
                    <textarea name="notes" id="notes" rows="2" 
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('notes', $journal->notes) }}</textarea>
                </div>

                @if($journal->supervisor_notes)
                    <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-300 mb-1">
                            <span class="material-icons text-sm">assignment_turned_in</span>
                            <span>Catatan Supervisi ({{ $journal->verifier?->name ?? 'Supervisor' }}):</span>
                        </div>
                        <p class="text-xs text-amber-900 dark:text-amber-200">{{ $journal->supervisor_notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Submit Button Card -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('teacher.journals.index') }}" 
                   class="px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 transition-colors">
                    Batal
                </a>
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-extrabold text-xs shadow-lg shadow-sky-600/25 transition-all active:scale-95">
                    <span class="material-icons text-base">save</span>
                    <span>Perbarui Jurnal Mengajar</span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function selectAssessmentTag(tag) {
            const el = document.getElementById('assessment');
            if (el.value.trim() === '') {
                el.value = tag;
            } else if (!el.value.includes(tag)) {
                el.value += ', ' + tag;
            }
            el.focus();
        }
    </script>
    @endpush
</x-app-layout>
