<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')],
                    ['title' => 'Catatan Anekdot Siswa', 'url' => route('teacher.anecdotes.index')]
                ]" />
                <div class="flex items-center gap-2 mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Rekapitulasi Catatan Anekdot Siswa
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                        Akademik • Kehadiran • Sikap
                    </span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <a href="{{ route('teacher.anecdotes.print', request()->query()) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-sm shadow-amber-600/20 transition-all active:scale-95">
                    <span class="material-icons text-sm">print</span>
                    <span>Cetak Lembar Anekdot</span>
                </a>

                <a href="{{ route('teacher.dashboard') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span>Dasbor</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5 sm:space-y-6">
        
        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5">
            <form action="{{ route('teacher.anecdotes.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    
                    <!-- Filter Kelas -->
                    <div>
                        <label for="school_class_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Kelas</label>
                        <select name="school_class_id" id="school_class_id" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $cId => $cName)
                                <option value="{{ $cId }}" {{ (string)$selectedClassId === (string)$cId ? 'selected' : '' }}>{{ $cName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Mapel -->
                    <div>
                        <label for="subject_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Mata Pelajaran</label>
                        <select name="subject_id" id="subject_id" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                            <option value="">Semua Mapel</option>
                            @foreach($subjects as $sId => $sName)
                                <option value="{{ $sId }}" {{ (string)$selectedSubjectId === (string)$sId ? 'selected' : '' }}>{{ $sName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tanggal Mulai -->
                    <div>
                        <label for="start_date" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}"
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                    </div>

                    <!-- Tanggal Selesai -->
                    <div>
                        <label for="end_date" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}"
                               class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                    </div>

                    <!-- Sentimen / Status -->
                    <div>
                        <label for="sentiment" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Sentimen</label>
                        <select name="sentiment" id="sentiment" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-amber-500/15">
                            <option value="">Semua Sentimen</option>
                            <option value="positive" {{ $selectedSentiment === 'positive' ? 'selected' : '' }}>🌟 Positif / Sangat Baik</option>
                            <option value="neutral" {{ $selectedSentiment === 'neutral' ? 'selected' : '' }}>⚖️ Netral / Cukup</option>
                            <option value="needs_guidance" {{ $selectedSentiment === 'needs_guidance' ? 'selected' : '' }}>💡 Perlu Bimbingan</option>
                        </select>
                    </div>

                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                    <div class="relative w-full sm:w-80">
                        <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">search</span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama siswa atau NIS..."
                               class="w-full text-xs py-2 pl-9 pr-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        @if(request()->hasAny(['school_class_id', 'subject_id', 'start_date', 'end_date', 'sentiment', 'search']))
                            <a href="{{ route('teacher.anecdotes.index') }}" 
                               class="px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-600 dark:text-slate-400 font-bold text-xs transition-colors">
                                Reset
                            </a>
                        @endif
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-amber-600 dark:hover:bg-amber-500 font-bold text-xs shadow-xs transition-all active:scale-95">
                            <span class="material-icons text-sm">filter_alt</span>
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Daftar Catatan Anekdot -->
        @if($anecdotes->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-12 text-center">
                <div class="w-16 h-16 rounded-3xl bg-amber-50 dark:bg-amber-950/50 text-amber-500 mx-auto flex items-center justify-center mb-3">
                    <span class="material-icons text-3xl">rate_review</span>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum Ada Catatan Anekdot</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Tidak ditemukan catatan anekdot siswa dengan filter yang dipilih. Guru mapel dapat menambahkan catatan saat sesi presensi berlangsung.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($anecdotes as $anecdote)
                    @php
                        $sentimentColors = [
                            'positive' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                            'neutral' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                            'needs_guidance' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                        ];
                        $sentimentLabels = [
                            'positive' => '🌟 Sangat Baik',
                            'neutral' => '⚖️ Cukup / Sesuai',
                            'needs_guidance' => '💡 Perlu Bimbingan',
                        ];
                    @endphp
                    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 transition-all hover:shadow-md">
                        
                        <!-- Header Baris: Siswa, Waktu, Mapel, Aksi -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <img src="{{ $anecdote->student->photo_url }}" alt="{{ $anecdote->student->name }}"
                                     class="w-10 h-10 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0 cursor-pointer hover:ring-2 hover:ring-sky-500/50 hover:scale-105 active:scale-95 transition-all student-avatar"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($anecdote->student->name) }}&color=d97706&background=fef3c7'"
                                     onclick="previewStudentPhoto('{{ $anecdote->student->photo_url }}', '{{ addslashes($anecdote->student->name) }}', 'Kelas {{ $anecdote->schoolClass?->name ?? '-' }} &bull; NIS {{ $anecdote->student->nis ?? '-' }}')"
                                     title="Klik untuk memperbesar foto siswa">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                                            {{ $anecdote->student->name }}
                                        </h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            Kelas {{ $anecdote->schoolClass?->name ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        NIS: {{ $anecdote->student->nis ?? '-' }} • Guru: {{ $anecdote->teacher?->name ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap self-start sm:self-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800">
                                    <span class="material-icons text-xs">event</span>
                                    <span>{{ $anecdote->date->translatedFormat('d M Y') }}</span>
                                </span>
                                
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $anecdote->subject?->name ?? ($anecdote->schedule?->getActivityName() ?? 'Mata Pelajaran') }}
                                </span>

                                @if($anecdote->is_visible_to_parents)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-xl text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="Bisa dilihat orang tua di portal">
                                        <span class="material-icons text-[12px]">visibility</span>
                                        <span>Ortu</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-xl text-[10px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400" title="Hanya internal guru">
                                        <span class="material-icons text-[12px]">lock</span>
                                        <span>Internal</span>
                                    </span>
                                @endif

                                @if(auth()->user()->teacher?->id === $anecdote->teacher_id || auth()->user()->isAdmin())
                                    <form action="{{ route('teacher.anecdotes.destroy', $anecdote) }}" method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan anekdot ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-7 h-7 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 flex items-center justify-center transition-colors" title="Hapus Catatan">
                                            <span class="material-icons text-xs">delete</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <!-- 3 Kategori Cards (Akademik, Kehadiran, Sikap) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-3.5">
                            
                            <!-- Akademik -->
                            <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between space-y-2">
                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                            <span class="material-icons text-xs">school</span>
                                            <span>Akademik</span>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $sentimentColors[$anecdote->academic_sentiment] ?? '' }}">
                                            {{ $sentimentLabels[$anecdote->academic_sentiment] ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed italic">
                                        {{ $anecdote->academic_note ? '"' . $anecdote->academic_note . '"' : '(Tidak ada catatan khusus)' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Kehadiran -->
                            <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between space-y-2">
                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-sky-600 dark:text-sky-400 flex items-center gap-1">
                                            <span class="material-icons text-xs">event_available</span>
                                            <span>Kehadiran</span>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $sentimentColors[$anecdote->attendance_sentiment] ?? '' }}">
                                            {{ $sentimentLabels[$anecdote->attendance_sentiment] ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed italic">
                                        {{ $anecdote->attendance_note ? '"' . $anecdote->attendance_note . '"' : '(Tidak ada catatan khusus)' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Sikap & Karakter -->
                            <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800/80 flex flex-col justify-between space-y-2">
                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                            <span class="material-icons text-xs">psychology</span>
                                            <span>Sikap & Karakter</span>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $sentimentColors[$anecdote->attitude_sentiment] ?? '' }}">
                                            {{ $sentimentLabels[$anecdote->attitude_sentiment] ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed italic">
                                        {{ $anecdote->attitude_note ? '"' . $anecdote->attitude_note . '"' : '(Tidak ada catatan khusus)' }}
                                    </p>
                                </div>
                            </div>

                        </div>

                        <!-- Tindak Lanjut (Jika Ada) -->
                        @if($anecdote->follow_up)
                            <div class="mt-3 p-2.5 rounded-2xl bg-amber-50/60 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-900/40 flex items-start gap-2">
                                <span class="material-icons text-amber-600 dark:text-amber-400 text-sm mt-0.5">flag</span>
                                <div class="text-xs">
                                    <strong class="text-amber-900 dark:text-amber-200">Tindak Lanjut Guru:</strong>
                                    <span class="text-slate-700 dark:text-slate-300 ml-1">{{ $anecdote->follow_up }}</span>
                                </div>
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pt-2">
                {{ $anecdotes->links() }}
            </div>
        @endif

    </div>
</x-app-layout>
