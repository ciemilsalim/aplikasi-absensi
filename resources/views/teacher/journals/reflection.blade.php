<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Jurnal Mengajar', 'url' => route('teacher.journals.index')],
                    ['title' => 'Refleksi Semester', 'url' => route('teacher.journals.reflection')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    F. Refleksi Guru Semester
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    SMP Negeri 1 Biau &bull; Evaluasi Diri & Rencana Perbaikan Pembelajaran Akhir Semester
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('teacher.journals.print', ['subject_id' => $selectedSubjectId]) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200/80 dark:border-slate-700 transition-all">
                    <span class="material-icons text-base text-amber-500">print</span>
                    <span>Cetak Jurnal & Refleksi</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if(session('success'))
            <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 text-xs font-semibold">
                <span class="material-icons text-lg text-emerald-500">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

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
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800 text-xs font-bold transition-all whitespace-nowrap shrink-0">
                <span class="material-icons text-sm text-indigo-500">assessment</span>
                <span>Rekap Semester & Asesmen</span>
            </a>
            <a href="{{ route('teacher.journals.reflection') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl bg-sky-600 text-white text-xs font-extrabold shadow-sm whitespace-nowrap shrink-0">
                <span class="material-icons text-sm">psychology</span>
                <span>Refleksi Guru</span>
            </a>
        </div>

        <form action="{{ route('teacher.journals.reflection.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Card Pilihan Mapel -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <label for="subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Mata Pelajaran</label>
                    <select name="subject_id" id="subject_id" onchange="window.location.href='{{ route('teacher.journals.reflection') }}?subject_id=' + this.value"
                            class="text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ $selectedSubjectId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-[11px] text-slate-400 max-w-sm">
                    Isi refleksi evaluatif berikut pada akhir semester untuk dilampirkan pada laporan supervisi pembelajaran.
                </p>
            </div>

            <!-- Form 6 Aspek Refleksi (Bagian F) -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-lg">psychology</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">6 Aspek Refleksi Evaluatif Guru</h2>
                        <p class="text-[11px] text-slate-400">Deskripsikan evaluasi pelaksanaan pembelajaran selama satu semester</p>
                    </div>
                </div>

                <!-- 1. Pembelajaran yang berjalan baik -->
                <div>
                    <label for="good_aspects" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        1. Pembelajaran yang Berjalan Baik <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="good_aspects" id="good_aspects" rows="3" required 
                              placeholder="Hal-hal positif, partisipasi aktif siswa, materi yang paling mudah dipahami, atau keberhasilan metode yang diterapkan..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('good_aspects', $reflection?->good_aspects) }}</textarea>
                </div>

                <!-- 2. Kendala utama -->
                <div>
                    <label for="challenges" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        2. Kendala Utama yang Dihadapi <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="challenges" id="challenges" rows="3" required 
                              placeholder="Hambatan waktu, sarana prasana lab/alat, pemahaman dasar siswa, alokasi waktu JP, dll..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('challenges', $reflection?->challenges) }}</textarea>
                </div>

                <!-- 3. Peserta didik yang memerlukan perhatian -->
                <div>
                    <label for="attention_students" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        3. Peserta Didik yang Memerlukan Perhatian Khusus <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="attention_students" id="attention_students" rows="3" required 
                              placeholder="Catatan mengenai kelompok atau siswa yang memerlukan bimbingan ekstra, remedial berkala, atau motivasi belajar..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('attention_students', $reflection?->attention_students) }}</textarea>
                </div>

                <!-- 4. Strategi yang efektif -->
                <div>
                    <label for="effective_strategies" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        4. Strategi Pembelajaran yang Efektif <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="effective_strategies" id="effective_strategies" rows="3" required 
                              placeholder="Pendekatan, metode diskusi kelompok, demonstrasi, atau media ajar interaktif yang terbukti meningkatkan keterlibatan siswa..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('effective_strategies', $reflection?->effective_strategies) }}</textarea>
                </div>

                <!-- 5. Perbaikan pembelajaran berikutnya -->
                <div>
                    <label for="future_improvements" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        5. Perbaikan Pembelajaran untuk Semester / Periode Berikutnya <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="future_improvements" id="future_improvements" rows="3" required 
                              placeholder="Rencana perbaikan modul ajar, penyesuaian asesmen formatif, diferensiasi konten/proses, manajemen kelas..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('future_improvements', $reflection?->future_improvements) }}</textarea>
                </div>

                <!-- 6. Rencana tindak lanjut -->
                <div>
                    <label for="follow_up_plan" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        6. Rencana Tindak Lanjut <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="follow_up_plan" id="follow_up_plan" rows="3" required 
                              placeholder="Langkah nyata koordinasi dengan wali kelas, guru BK, orang tua, atau persiapan pengayaan..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('follow_up_plan', $reflection?->follow_up_plan) }}</textarea>
                </div>
            </div>

            <!-- Action Button -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-extrabold text-xs shadow-lg shadow-sky-600/25 transition-all active:scale-95">
                    <span class="material-icons text-base">save</span>
                    <span>Simpan Refleksi Semester</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
