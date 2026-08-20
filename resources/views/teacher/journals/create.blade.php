<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'guru_mapel'])],
                    ['title' => 'Jurnal Mengajar', 'url' => route('teacher.journals.index')],
                    ['title' => 'Tulis Jurnal Baru', 'url' => route('teacher.journals.create')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Tulis Jurnal Mengajar Harian
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    SMP Negeri 1 Biau &bull; Pelaksanaan Pembelajaran Fase D
                </p>
            </div>

            <a href="{{ route('teacher.journals.index') }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 font-bold text-xs transition-all self-start sm:self-auto">
                <span class="material-icons text-base">arrow_back</span>
                <span>Kembali ke Jurnal</span>
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

        <form action="{{ route('teacher.journals.store') }}" method="POST" class="space-y-6" id="journalForm">
            @csrf

            <!-- Card 1: Data Jadwal & Waktu -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-lg">event_available</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">1. Informasi Pertemuan & Jadwal</h2>
                        <p class="text-[11px] text-slate-400">Pilih sesi jadwal mengajar dan tanggal pelaksanaan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="schedule_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            Jadwal Pelajaran / Kelas <span class="text-rose-500">*</span>
                        </label>
                        <select name="schedule_id" id="schedule_id" required 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">
                            <option value="" disabled {{ !$selectedSchedule ? 'selected' : '' }}>-- Pilih Jadwal Mengajar --</option>
                            @foreach($schedules as $sched)
                                <option value="{{ $sched->id }}" 
                                        data-class="{{ $sched->getTargetClass()?->name ?? '-' }}"
                                        data-subject="{{ $sched->getActivityName() }}"
                                        {{ (old('schedule_id', $selectedSchedule?->id) == $sched->id) ? 'selected' : '' }}>
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
                               value="{{ old('date', $selectedDate) }}"
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label for="jp" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            Jumlah Jam (JP) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="jp" id="jp" min="1" max="10" required 
                               value="{{ old('jp', $estimatedJp) }}"
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-3 p-3 rounded-2xl bg-sky-50/50 dark:bg-slate-800/60 border border-sky-100 dark:border-slate-700/60">
                        <span class="material-icons text-sky-500 text-xl shrink-0">fact_check</span>
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200">Presensi Siswa Sesi Ini:</span>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300" id="attendancePreview">
                                <span class="text-emerald-600">Hadir: <strong id="attHadir">{{ $attendanceData['hadir'] }}</strong></span> &bull;
                                <span class="text-amber-500">Sakit: <strong id="attSakit">{{ $attendanceData['sakit'] }}</strong></span> &bull;
                                <span class="text-sky-500">Izin: <strong id="attIzin">{{ $attendanceData['izin'] }}</strong></span> &bull;
                                <span class="text-rose-500">Alpa: <strong id="attAlpa">{{ $attendanceData['alpa'] }}</strong></span>
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
                        <p class="text-[11px] text-slate-400">Ringkasan substansi materi yang diajarkan pada pertemuan ini</p>
                    </div>
                </div>

                <!-- Topik / Materi -->
                <div>
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Topik / Materi Pokok <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="topic" id="topic" required 
                           value="{{ old('topic') }}" placeholder="Contoh: Keamanan Digital, Password & 2FA, Persamaan Linier, dll."
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <!-- Tujuan Pembelajaran (TP) -->
                <div>
                    <label for="learning_objective" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Tujuan Pembelajaran (TP) <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="learning_objective" id="learning_objective" rows="2" required 
                              placeholder="Tuliskan sasaran TP pada pertemuan ini (tidak perlu menyalin seluruh CP)..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('learning_objective') }}</textarea>
                </div>

                <!-- Kegiatan Pembelajaran -->
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1.5">
                        <label for="activity" class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Kegiatan Pembelajaran <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] text-slate-400">Gunakan preset di bawah untuk format cepat:</span>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <button type="button" onclick="insertActivityPreset('Apersepsi → Demonstrasi → Diskusi Kelompok → Praktik → Presentasi')" 
                                class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-sky-50 hover:text-sky-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                            + Apersepsi → Demo → Diskusi → Praktik → Presentasi
                        </button>
                        <button type="button" onclick="insertActivityPreset('Studi Kasus → Diskusi Kelompok → Presentasi Hasil → Penguatan Konsep')" 
                                class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-sky-50 hover:text-sky-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                            + Studi Kasus → Diskusi → Presentasi → Penguatan
                        </button>
                        <button type="button" onclick="insertActivityPreset('Eksplorasi Konsep → Pengerjaan LKPD Mandiri → Pembahasan Bersama')" 
                                class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-sky-50 hover:text-sky-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                            + Eksplorasi → LKPD Mandiri → Pembahasan
                        </button>
                    </div>

                    <textarea name="activity" id="activity" rows="3" required 
                              placeholder="Tuliskan alur aktivitas pembelajaran utama..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('activity') }}</textarea>
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
                                    class="tag-assessment px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 hover:text-emerald-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                                + {{ $tag }}
                            </button>
                        @endforeach
                    </div>
                    <input type="text" name="assessment" id="assessment" required 
                           value="{{ old('assessment') }}" placeholder="Contoh: Observasi, LKPD, Unjuk Kerja"
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <!-- Hasil / Refleksi -->
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1.5">
                        <label for="reflection" class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Hasil / Refleksi Pembelajaran <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] text-slate-400">Kondisi nyata setelah pembelajaran</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <button type="button" onclick="insertReflectionPreset('Sebagian besar peserta didik mampu mencapai TP dengan sangat baik.')" 
                                class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-sky-50 hover:text-sky-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                            + Sebagian besar capai TP
                        </button>
                        <button type="button" onclick="insertReflectionPreset('80% siswa mencapai TP, sekitar 5 siswa masih memerlukan penguatan konsep.')" 
                                class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-sky-50 hover:text-sky-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                            + 80% Capai TP, 5 Siswa butuh penguatan
                        </button>
                    </div>
                    <textarea name="reflection" id="reflection" rows="2" required 
                              placeholder="Tuliskan hasil evaluasi dan ketercapaian TP..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('reflection') }}</textarea>
                </div>

                <!-- Tindak Lanjut -->
                <div>
                    <label for="follow_up" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Tindak Lanjut <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        @foreach(['Remedial', 'Pengayaan', 'Penguatan Konsep', 'Pendampingan Khusus', 'Lanjut Materi Berikutnya'] as $fu)
                            <button type="button" onclick="selectFollowUpTag('{{ $fu }}')" 
                                    class="tag-followup px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-amber-50 hover:text-amber-600 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                                + {{ $fu }}
                            </button>
                        @endforeach
                    </div>
                    <input type="text" name="follow_up" id="follow_up" required 
                           value="{{ old('follow_up') }}" placeholder="Contoh: Remedial bagi yang belum tuntas, Pengayaan materi lanjutan"
                           class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15" />
                </div>

                <!-- Catatan Tambahan (Opsional) -->
                <div>
                    <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Catatan Kejadian / Catatan Khusus (Opsional)
                    </label>
                    <textarea name="notes" id="notes" rows="2" 
                              placeholder="Catatan perilaku, kendala sarana lab/alat, atau informasi penting lainnya..."
                              class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-sky-500/15">{{ old('notes') }}</textarea>
                </div>
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
                    <span>Simpan Jurnal Mengajar</span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function insertActivityPreset(text) {
            const el = document.getElementById('activity');
            el.value = text;
            el.focus();
        }

        function insertReflectionPreset(text) {
            const el = document.getElementById('reflection');
            el.value = text;
            el.focus();
        }

        function selectAssessmentTag(tag) {
            const el = document.getElementById('assessment');
            if (el.value.trim() === '') {
                el.value = tag;
            } else if (!el.value.includes(tag)) {
                el.value += ', ' + tag;
            }
            el.focus();
        }

        function selectFollowUpTag(tag) {
            const el = document.getElementById('follow_up');
            if (el.value.trim() === '') {
                el.value = tag;
            } else if (!el.value.includes(tag)) {
                el.value += ', ' + tag;
            }
            el.focus();
        }

        // Auto fetch attendance snapshot & estimated JP on schedule or date change
        document.getElementById('schedule_id')?.addEventListener('change', fetchSessionDetails);
        document.getElementById('date')?.addEventListener('change', fetchSessionDetails);

        function fetchSessionDetails() {
            const scheduleId = document.getElementById('schedule_id')?.value;
            const date = document.getElementById('date')?.value;

            if (!scheduleId) return;

            fetch(`{{ route('teacher.journals.session_data') }}?schedule_id=${scheduleId}&date=${date}`)
                .then(res => res.json())
                .then(data => {
                    if (data.estimated_jp) {
                        const jpEl = document.getElementById('jp');
                        if (jpEl && (!jpEl.value || jpEl.value == '2')) {
                            jpEl.value = data.estimated_jp;
                        }
                    }
                    if (data.attendance) {
                        document.getElementById('attHadir').textContent = data.attendance.hadir;
                        document.getElementById('attSakit').textContent = data.attendance.sakit;
                        document.getElementById('attIzin').textContent = data.attendance.izin;
                        document.getElementById('attAlpa').textContent = data.attendance.alpa;
                    }
                })
                .catch(err => console.error('Error fetching session details:', err));
        }
    </script>
    @endpush
</x-app-layout>
