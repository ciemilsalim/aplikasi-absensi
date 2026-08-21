{{--
================================================================================================
| File       : resources/views/teacher/partials/_dashboard-pembina-ekskul.blade.php
| Deskripsi  : Dasbor Pembina Ekstrakurikuler dengan standar UX mobile dan desktop utuh.
================================================================================================
--}}

<div class="space-y-6">

    {{-- ========================================================================= --}}
    {{-- 1. TAMPILAN KHUSUS MOBILE (< sm breakpoint)                               --}}
    {{-- Sesuai Standar UX Mobile: Sesi Latihan Hari Ini, Pilihan Metode, Alur Jelas --}}
    {{-- ========================================================================= --}}
    <div class="block sm:hidden space-y-5" x-data="{ selectedEkskulModal: null }">
        
        <!-- Header Sapaan Sederhana & Ramah Jempol (72-88px) -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <img class="h-12 w-12 rounded-2xl object-cover ring-2 ring-amber-500/20 bg-slate-800 shrink-0 shadow-sm" 
                     src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=d97706&background=fef3c7' }}" 
                     alt="{{ Auth::user()->name }}">
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight truncate">
                        Halo, {{ $teacher->name }} 👋
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Pembina Ekstrakurikuler &bull; SMP Negeri 1 Biau
                    </p>
                </div>
            </div>
            <a href="{{ route('teacher.extracurricular-attendance.index') }}" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-500 hover:text-amber-600 shadow-2xs shrink-0" title="Rekap Ekskul">
                <span class="material-icons text-xl text-amber-500">military_tech</span>
            </a>
        </div>

        <!-- SESI LATIHAN HARI INI (HERO CARD PRIORITAS 1) -->
        @forelse($coachedExtracurriculars as $ekskul)
            @php
                $stats = $todayExtracurricularStats[$ekskul->id] ?? null;
                $isRecorded = ($stats && $stats['total_recorded'] > 0);
            @endphp
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <!-- Header Card: Info Sesi & Status -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">SESI LATIHAN HARI INI</span>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ now()->translatedFormat('l, d F Y') }}</p>
                    </div>

                    @if($isRecorded)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300/40">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Presensi Selesai</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300/40">
                            ● Belum Ada Presensi
                        </span>
                    @endif
                </div>

                <!-- Info Ekskul & Anggota -->
                <div class="bg-amber-50/50 dark:bg-amber-950/20 p-4 rounded-2xl border border-amber-200/60 dark:border-amber-900/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">workspace_premium</span>
                            <span>{{ $ekskul->name }}</span>
                        </h3>
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200">
                            {{ $ekskul->students_count }} Anggota
                        </span>
                    </div>

                    @if($isRecorded)
                        <!-- Mini Stats Hari Ini -->
                        <div class="grid grid-cols-4 gap-2 pt-2 text-center text-xs">
                            <div class="bg-white/80 dark:bg-slate-800/80 p-2 rounded-xl border border-emerald-200/60 dark:border-emerald-900/40">
                                <span class="font-extrabold text-emerald-700 dark:text-emerald-400 block">{{ $stats['hadir'] }}</span>
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Hadir</span>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-800/80 p-2 rounded-xl border border-amber-200/60 dark:border-amber-900/40">
                                <span class="font-extrabold text-amber-700 dark:text-amber-400 block">{{ $stats['sakit'] }}</span>
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Sakit</span>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-800/80 p-2 rounded-xl border border-purple-200/60 dark:border-purple-900/40">
                                <span class="font-extrabold text-purple-700 dark:text-purple-400 block">{{ $stats['izin'] }}</span>
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Izin</span>
                            </div>
                            <div class="bg-white/80 dark:bg-slate-800/80 p-2 rounded-xl border border-rose-200/60 dark:border-rose-900/40">
                                <span class="font-extrabold text-rose-700 dark:text-rose-400 block">{{ $stats['alpa'] }}</span>
                                <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400">Alpa</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- PRIMARY CTA: MULAI PRESENSI -->
                <div class="pt-1">
                    @if(!$isRecorded)
                        <button @click="selectedEkskulModal = { id: {{ $ekskul->id }}, name: '{{ addslashes($ekskul->name) }}', scanUrl: '{{ route('teacher.extracurricular-attendance.scanner', $ekskul) }}', manualUrl: '{{ route('teacher.extracurricular-attendance.create', $ekskul) }}' }"
                                class="w-full min-h-[48px] py-3 flex items-center justify-center gap-2 rounded-2xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-sm shadow-md shadow-amber-500/25 active:scale-95 transition-all">
                            <span class="material-icons text-lg">touch_app</span>
                            <span>MULAI PRESENSI</span>
                        </button>
                    @else
                        <div class="grid grid-cols-2 gap-2.5">
                            <button @click="selectedEkskulModal = { id: {{ $ekskul->id }}, name: '{{ addslashes($ekskul->name) }}', scanUrl: '{{ route('teacher.extracurricular-attendance.scanner', $ekskul) }}', manualUrl: '{{ route('teacher.extracurricular-attendance.create', $ekskul) }}' }"
                                    class="min-h-[44px] py-2.5 flex items-center justify-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 font-bold text-xs transition-colors">
                                <span class="material-icons text-base text-amber-500">replay</span>
                                <span>Presensi Lagi</span>
                            </button>
                            <a href="{{ route('teacher.extracurricular-attendance.report', $ekskul) }}" 
                               class="min-h-[44px] py-2.5 flex items-center justify-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/20 transition-all">
                                <span class="material-icons text-base">assessment</span>
                                <span>Lihat Rekap</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl p-6 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center">
                    <span class="material-icons text-2xl">sports_soccer</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Belum Ada Ekstrakurikuler</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Anda belum ditugaskan sebagai pembina pada kegiatan ekstrakurikuler manapun.</p>
            </div>
        @endforelse

        <!-- PRESENSI TERAKHIR -->
        @if(isset($lastAttendanceSummaryEkskul) && $lastAttendanceSummaryEkskul)
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span class="material-icons text-amber-500 text-base">history</span>
                            <span>Presensi Terakhir</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $lastAttendanceSummaryEkskul['extracurricular']->name }} &bull; {{ $lastAttendanceSummaryEkskul['date']->translatedFormat('d F Y') }}
                        </p>
                    </div>
                    <a href="{{ route('teacher.extracurricular-attendance.report', $lastAttendanceSummaryEkskul['extracurricular']) }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-0.5">
                        <span>Lihat</span>
                        <span class="material-icons text-sm">chevron_right</span>
                    </a>
                </div>

                <div class="grid grid-cols-4 gap-2 text-center">
                    <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-800">
                        <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block">Hadir</span>
                        <span class="text-base font-black text-emerald-700 dark:text-emerald-300">{{ $lastAttendanceSummaryEkskul['hadir'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200/60 dark:border-amber-800">
                        <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400 block">Sakit</span>
                        <span class="text-base font-black text-amber-700 dark:text-amber-300">{{ $lastAttendanceSummaryEkskul['sakit'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200/60 dark:border-purple-800">
                        <span class="text-[10px] uppercase font-bold text-purple-600 dark:text-purple-400 block">Izin</span>
                        <span class="text-base font-black text-purple-700 dark:text-purple-300">{{ $lastAttendanceSummaryEkskul['izin'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200/60 dark:border-rose-800">
                        <span class="text-[10px] uppercase font-bold text-rose-600 dark:text-rose-400 block">Alpa</span>
                        <span class="text-base font-black text-rose-700 dark:text-rose-300">{{ $lastAttendanceSummaryEkskul['alpa'] }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl p-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between min-h-[64px]">
                <div class="flex items-center gap-2.5">
                    <span class="material-icons text-slate-400 text-lg">history</span>
                    <div>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Riwayat Sesi Latihan</p>
                        <p class="text-[11px] text-slate-400">Belum ada rekaman sesi latihan sebelumnya.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- RIWAYAT LATIHAN (PREVIEW LIST 3-5 ITEM) -->
        @if(isset($recentPracticeSessions) && $recentPracticeSessions->isNotEmpty())
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-1">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-amber-500 text-base">event_note</span>
                        <span>Riwayat Latihan</span>
                    </h3>
                    <a href="{{ route('teacher.extracurricular-attendance.index') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-0.5">
                        <span>Semua</span>
                        <span class="material-icons text-sm">chevron_right</span>
                    </a>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($recentPracticeSessions as $session)
                        <div class="py-3 flex items-center justify-between gap-3 min-h-[60px]">
                            <div>
                                <p class="font-bold text-xs text-slate-900 dark:text-white">{{ $session->extracurricular?->name }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ Carbon\Carbon::parse($session->attendance_date)->translatedFormat('d M Y') }} &bull; {{ $session->total_hadir }} Hadir
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900/40">
                                ✓ Selesai
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- SISWA PERLU PERHATIAN -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-rose-500 text-base">priority_high</span>
                    <span>Siswa Perlu Perhatian</span>
                </h3>
            </div>

            @if(isset($studentsForAttentionEkskul) && $studentsForAttentionEkskul->isNotEmpty())
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($studentsForAttentionEkskul as $item)
                        <div class="py-2.5 flex items-center justify-between gap-3 min-h-[56px]">
                            <div class="min-w-0">
                                <p class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ $item->student->name }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $item->extracurricular?->name }} &bull; Kelas {{ $item->student->schoolClass?->name ?? '-' }}</p>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 rounded-full shrink-0">
                                {{ $item->alpa_count }}x Alpa
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-3 flex items-center gap-2.5 text-xs text-slate-600 dark:text-slate-300 min-h-[60px]">
                    <span class="material-icons text-emerald-500 text-lg">check_circle</span>
                    <span>Semua anggota ekskul dalam kondisi kehadiran baik.</span>
                </div>
            @endif
        </div>

        <!-- AKTIVITAS 30 HARI (2 METRIK BERSIH) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-amber-500 text-base">insights</span>
                    <span>Aktivitas Ekskul (30 Hari)</span>
                </h3>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40">
                    <span class="text-[10px] font-bold text-amber-700 dark:text-amber-300 block">Sesi Latihan</span>
                    <span class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $totalSessions30Days }} Sesi</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block">Rata-rata Kehadiran</span>
                    <span class="text-lg font-extrabold text-amber-600 dark:text-amber-400">{{ $avgAttendance30Days }}%</span>
                </div>
            </div>

            @if(isset($classPerformanceDataEkskul) && count($classPerformanceDataEkskul) > 0)
                <div class="space-y-2.5 pt-2">
                    @foreach($classPerformanceDataEkskul as $perf)
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-700 dark:text-slate-300 truncate">{{ $perf['label'] }}</span>
                                <span class="font-bold text-amber-600 dark:text-amber-400">{{ $perf['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $perf['percentage'])) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- CATATAN PEMBINA (AUTO-SAVE) -->
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
                    <span>Catatan Pembina</span>
                </h3>
                <span x-text="status" class="text-[10px] font-bold text-amber-600 dark:text-amber-400"></span>
            </div>
            
            <textarea x-model="note" 
                      @input.debounce.800ms="saveNote()" 
                      rows="3" 
                      placeholder="Tuliskan agenda latihan, target kompetisi, atau catatan persiapan..." 
                      class="w-full text-xs rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 resize-none transition-all placeholder:text-slate-400"></textarea>
            <p class="text-[10px] text-slate-400 mt-1">✓ Tersimpan otomatis saat mengetik</p>
        </div>

        <!-- MODAL PILIH METODE PRESENSI (MOBILE ONLY) -->
        <div x-show="selectedEkskulModal" class="relative z-50" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="selectedEkskulModal = null"></div>

            <!-- Bottom Sheet Dialog -->
            <div class="fixed inset-x-0 bottom-0 z-50 flex flex-col rounded-t-3xl bg-white dark:bg-slate-900 p-5 shadow-2xl border-t border-slate-200 dark:border-slate-800"
                 x-show="selectedEkskulModal"
                 x-transition:enter="transform transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full">
                
                <!-- Drag Handle -->
                <div class="mx-auto h-1 w-9 rounded-full bg-slate-300 dark:bg-slate-700 mb-3"></div>

                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pilih Metode Presensi</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400" x-text="selectedEkskulModal ? selectedEkskulModal.name : ''"></p>
                    </div>
                    <button @click="selectedEkskulModal = null" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600">
                        <span class="material-icons text-xl">close</span>
                    </button>
                </div>

                <div class="py-4 space-y-3">
                    <!-- Metode 1: Scan QR -->
                    <a :href="selectedEkskulModal ? selectedEkskulModal.scanUrl : '#'" 
                       class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all flex items-center gap-3.5 block group">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/25 group-hover:scale-105 transition-transform">
                            <span class="material-icons text-2xl">qr_code_scanner</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">Scan QR Kamera</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pindai kartu identitas siswa secara otomatis</p>
                        </div>
                        <span class="material-icons text-amber-500 text-base">chevron_right</span>
                    </a>

                    <!-- Metode 2: Input Manual Checklist -->
                    <a :href="selectedEkskulModal ? selectedEkskulModal.manualUrl : '#'" 
                       class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center gap-3.5 block group">
                        <div class="w-12 h-12 rounded-2xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <span class="material-icons text-2xl">checklist</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">Input Manual</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tandai kehadiran daftar anggota secara langsung</p>
                        </div>
                        <span class="material-icons text-slate-400 text-base">chevron_right</span>
                    </a>
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button @click="selectedEkskulModal = null" class="w-full min-h-[44px] rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold">
                        Batal
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- 2. TAMPILAN DESKTOP (≥ sm breakpoint)                                      --}}
    {{-- Tetap 100% Utuh: Hero 8-cols, Multi-cards Ekskul, Riwayat, Perhatian, Note --}}
    {{-- ========================================================================= --}}
    <div class="hidden sm:block space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Kolom Utama: Ekskul Binaan & Aksi Cepat (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Welcome Hero Banner (Amber / Orange Theme) -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-amber-950/80 to-orange-950 text-white p-6 sm:p-7 shadow-lg border border-amber-900/40">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>
                    <div class="absolute top-0 right-1/4 w-32 h-32 rounded-full bg-orange-500/10 blur-2xl pointer-events-none"></div>
                    
                    <div class="relative z-10 flex items-center gap-4">
                        <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-amber-500/30 bg-slate-800 shrink-0 shadow-md" 
                             src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=d97706&background=fef3c7' }}" 
                             alt="{{ Auth::user()->name }}">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-[10px] font-bold text-amber-300 border border-amber-400/20 mb-1">
                                <span class="material-icons text-xs">military_tech</span>
                                <span>Pembina Ekstrakurikuler</span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-snug">
                                Selamat Bertugas, {{ $teacher->name }}!
                            </h2>
                            <p class="text-xs text-amber-100/80 mt-0.5">
                                Bimbing bakat dan minat siswa melalui kegiatan ekstrakurikuler serta pantau presensi latihannya.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Daftar Ekstrakurikuler yang Dibina -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-amber-500 text-lg">sports_soccer</span>
                                Ekstrakurikuler Binaan Anda
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih kegiatan untuk membuka scanner kamera presensi atau input cepat</p>
                        </div>
                        
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold border border-amber-200 dark:border-amber-800 shadow-2xs self-start sm:self-auto">
                            <span class="material-icons text-xs">groups</span>
                            <span>{{ $coachedExtracurriculars->count() }} Kegiatan Dibina</span>
                        </span>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        @forelse($coachedExtracurriculars as $ekskul)
                            @php
                                $stats = $todayExtracurricularStats[$ekskul->id] ?? null;
                                $isRecorded = ($stats && $stats['total_recorded'] > 0);
                            @endphp
                            <div class="p-5 rounded-2xl border transition-all relative overflow-hidden group {{ $isRecorded ? 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-200 dark:border-amber-900/50 shadow-xs' : 'bg-slate-50 dark:bg-slate-850/60 border-slate-200/80 dark:border-slate-800' }}">
                                <span class="material-icons absolute -right-3 -bottom-3 text-7xl opacity-[0.03] dark:opacity-[0.05] group-hover:scale-110 transition-transform duration-500 pointer-events-none">
                                    military_tech
                                </span>

                                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $isRecorded ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                            <span class="material-icons text-2xl">workspace_premium</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="text-base font-extrabold text-slate-900 dark:text-white">
                                                    {{ $ekskul->name }}
                                                </h4>
                                                @if($isRecorded)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/60 px-2.5 py-0.5 rounded-full">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Sudah Absen Hari Ini
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/60 px-2.5 py-0.5 rounded-full">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Belum Ada Presensi Hari Ini
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
                                                <span class="material-icons text-xs text-slate-400">group</span>
                                                <span>{{ $ekskul->students_count }} Anggota Terdaftar</span>
                                                @if(!empty($ekskul->description))
                                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                                    <span class="truncate max-w-xs">{{ $ekskul->description }}</span>
                                                @endif
                                            </p>

                                            @if($ekskul->teachers && $ekskul->teachers->count() > 1)
                                                <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Tim Pembina:</span>
                                                    @foreach($ekskul->teachers as $c)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c->id === $teacher->id ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}">
                                                            <span>{{ $c->name }}</span>
                                                            @if($c->id === $teacher->id)
                                                                <span class="text-[9px] text-amber-600 dark:text-amber-400 font-black">(Saya)</span>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if($isRecorded)
                                                <div class="flex flex-wrap items-center gap-2 mt-3 text-xs">
                                                    <span class="px-2.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold text-[11px]">
                                                        Hadir: {{ $stats['hadir'] }}
                                                    </span>
                                                    @if($stats['sakit'] > 0)
                                                        <span class="px-2.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-bold text-[11px]">
                                                            Sakit: {{ $stats['sakit'] }}
                                                        </span>
                                                    @endif
                                                    @if($stats['izin'] > 0)
                                                        <span class="px-2.5 py-0.5 rounded-md bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 font-bold text-[11px]">
                                                            Izin: {{ $stats['izin'] }}
                                                        </span>
                                                    @endif
                                                    @if($stats['alpa'] > 0)
                                                        <span class="px-2.5 py-0.5 rounded-md bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 font-bold text-[11px]">
                                                            Alpa: {{ $stats['alpa'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-200/60 dark:border-slate-700/60">
                                        <a href="{{ route('teacher.extracurricular-attendance.scanner', $ekskul) }}" 
                                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs shadow-md shadow-amber-500/20 transition-all">
                                            <span class="material-icons text-sm">qr_code_scanner</span>
                                            <span>Scan QR</span>
                                        </a>

                                        <a href="{{ route('teacher.extracurricular-attendance.create', $ekskul) }}" 
                                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-colors border border-slate-200/60 dark:border-slate-700">
                                            <span class="material-icons text-sm text-slate-500">checklist</span>
                                            <span>Manual</span>
                                        </a>

                                        <a href="{{ route('teacher.extracurricular-attendance.report', $ekskul) }}" 
                                           class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-amber-600 transition-colors border border-slate-200/60 dark:border-slate-700" 
                                           title="Lihat Rekap & Cetak Laporan">
                                            <span class="material-icons text-base">summarize</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center bg-slate-50 dark:bg-slate-850/50 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                                <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 mx-auto flex items-center justify-center mb-3">
                                    <span class="material-icons text-3xl">sports_soccer</span>
                                </div>
                                <h4 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum Ada Ekstrakurikuler</h4>
                                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                    Anda belum ditugaskan sebagai pembina pada kegiatan ekstrakurikuler manapun.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Kolom Samping: Ringkasan, Perhatian, Grafik & Catatan (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Ringkasan Sesi Latihan Terakhir -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">history</span>
                            Sesi Latihan Terakhir
                        </h3>
                    </div>

                    @if(isset($lastAttendanceSummaryEkskul) && $lastAttendanceSummaryEkskul)
                        <div>
                            <h4 class="font-extrabold text-sm text-amber-600 dark:text-amber-400">{{ $lastAttendanceSummaryEkskul['extracurricular']->name }}</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                                <span class="material-icons text-xs text-slate-400">calendar_today</span>
                                <span>{{ $lastAttendanceSummaryEkskul['date']->translatedFormat('l, d F Y') }}</span>
                            </p>

                            <div class="grid grid-cols-4 gap-2 mt-4 text-center">
                                <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/60 dark:border-emerald-800">
                                    <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block">Hadir</span>
                                    <span class="text-base font-black text-emerald-700 dark:text-emerald-300">{{ $lastAttendanceSummaryEkskul['hadir'] }}</span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200/60 dark:border-amber-800">
                                    <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400 block">Sakit</span>
                                    <span class="text-base font-black text-amber-700 dark:text-amber-300">{{ $lastAttendanceSummaryEkskul['sakit'] }}</span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200/60 dark:border-purple-800">
                                    <span class="text-[10px] uppercase font-bold text-purple-600 dark:text-purple-400 block">Izin</span>
                                    <span class="text-base font-black text-purple-700 dark:text-purple-300">{{ $lastAttendanceSummaryEkskul['izin'] }}</span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200/60 dark:border-rose-800">
                                    <span class="text-[10px] uppercase font-bold text-rose-600 dark:text-rose-400 block">Alpa</span>
                                    <span class="text-base font-black text-rose-700 dark:text-rose-300">{{ $lastAttendanceSummaryEkskul['alpa'] }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <span class="material-icons text-3xl mb-1 text-slate-300 dark:text-slate-600 block">event_busy</span>
                            Belum ada riwayat rekaman latihan ekskul.
                        </div>
                    @endif
                </div>

                <!-- Siswa Ekskul Perlu Perhatian Khusus -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-rose-500 text-lg">warning_amber</span>
                            Perlu Perhatian Khusus
                        </h3>
                    </div>

                    @if(isset($studentsForAttentionEkskul) && $studentsForAttentionEkskul->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($studentsForAttentionEkskul as $item)
                                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-850/70 border border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($item->student->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-xs text-slate-800 dark:text-white truncate">{{ $item->student->name }}</h4>
                                            <p class="text-[10px] text-slate-400 truncate">{{ $item->extracurricular?->name }} • Kelas {{ $item->student->schoolClass?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 rounded-full shrink-0">
                                        {{ $item->alpa_count }}x Alpa
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <span class="material-icons text-3xl mb-1 text-emerald-400 block">verified</span>
                            Kehadiran seluruh anggota ekskul dalam kondisi sangat baik.
                        </div>
                    @endif
                </div>

                <!-- Grafik Performa Kehadiran Ekskul 30 Hari Terakhir -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">insights</span>
                            Keaktifan Ekskul (30 Hari)
                        </h3>
                    </div>

                    @if(isset($classPerformanceDataEkskul) && count($classPerformanceDataEkskul) > 0)
                        <div class="space-y-3">
                            @foreach($classPerformanceDataEkskul as $perf)
                                <div>
                                    <div class="flex justify-between text-xs font-semibold mb-1">
                                        <span class="text-slate-700 dark:text-slate-300 truncate mr-2">{{ $perf['label'] }}</span>
                                        <span class="font-bold text-amber-600 dark:text-amber-400 shrink-0">{{ $perf['percentage'] }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $perf['percentage'])) }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">{{ $perf['total_sessions'] ?? 0 }} Sesi Latihan</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <span class="material-icons text-3xl mb-1 text-slate-300 dark:text-slate-600 block">bar_chart</span>
                            Belum cukup data sesi latihan dalam 30 hari terakhir.
                        </div>
                    @endif
                </div>

                <!-- Catatan Pembina Ekskul (Auto-Save) -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6" 
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
                                     this.status = 'Gagal menyimpan';
                                 }
                             })
                             .catch(() => {
                                 this.status = 'Koneksi error';
                             });
                         }
                     }">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">edit_note</span>
                            Catatan Pembina
                        </h3>
                        <span x-text="status" class="text-[10px] font-bold text-amber-600 dark:text-amber-400"></span>
                    </div>
                    
                    <textarea x-model="note" 
                              @input.debounce.800ms="saveNote()" 
                              rows="4" 
                              placeholder="Tuliskan agenda latihan, target kompetisi, atau catatan persiapan ekskul..." 
                              class="w-full text-xs rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 resize-none transition-all placeholder:text-slate-400"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                        <span class="material-icons text-xs">info</span>
                        <span>Catatan tersimpan otomatis saat Anda mengetik.</span>
                    </p>
                </div>

            </div>
        </div>

    </div>

</div>
