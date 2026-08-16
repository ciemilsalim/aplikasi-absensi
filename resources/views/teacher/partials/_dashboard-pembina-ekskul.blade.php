{{--
================================================================================================
| File    : resources/views/teacher/partials/_dashboard-pembina-ekskul.blade.php
| Deskripsi : Dasbor Pembina Ekstrakurikuler dengan tata letak 2 kolom modern & tema Amber/Orange.
================================================================================================
--}}

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

        <!-- Fast Actions (Scan Datang/Pulang & Izin Keluar) -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <a href="{{ route('scanner') }}" 
               class="group p-4 sm:p-4.5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-700 shadow-xs hover:shadow-md transition-all flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                    <span class="material-icons text-2xl">qr_code_scanner</span>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Absen Datang/Pulang</h4>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Scan Masuk & Pulang Sekolah</p>
                </div>
            </a>

            <a href="{{ route('permit.scanner') }}" 
               class="group p-4 sm:p-4.5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 shadow-xs hover:shadow-md transition-all flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                    <span class="material-icons text-2xl">assignment</span>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Scan Izin Keluar</h4>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Izin Tinggalkan Sekolah</p>
                </div>
            </a>
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
                        <!-- Background Decorative Icon -->
                        <span class="material-icons absolute -right-3 -bottom-3 text-7xl opacity-[0.03] dark:opacity-[0.05] group-hover:scale-110 transition-transform duration-500 pointer-events-none">
                            military_tech
                        </span>

                        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Info Ekskul -->
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
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                                        <span class="material-icons text-xs text-slate-400">group</span>
                                        <span>{{ $ekskul->students_count }} Anggota Terdaftar</span>
                                        @if(!empty($ekskul->description))
                                            <span class="text-slate-300 dark:text-slate-600">•</span>
                                            <span class="truncate max-w-xs">{{ $ekskul->description }}</span>
                                        @endif
                                    </p>

                                    <!-- Mini Stats Status Hari Ini -->
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

                            <!-- Tombol Aksi -->
                            <div class="flex items-center gap-2 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-200/60 dark:border-slate-700/60">
                                <!-- Buka Scanner Live Kamera -->
                                <a href="{{ route('teacher.extracurricular-attendance.scanner', $ekskul) }}" 
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs shadow-md shadow-amber-500/20 transition-all">
                                    <span class="material-icons text-sm">qr_code_scanner</span>
                                    <span>Scanner Live</span>
                                </a>

                                <!-- Input Manual / Checklist -->
                                <a href="{{ route('teacher.extracurricular-attendance.create', $ekskul) }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-colors border border-slate-200/60 dark:border-slate-700">
                                    <span class="material-icons text-sm text-slate-500">checklist</span>
                                    <span>Manual</span>
                                </a>

                                <!-- Laporan & Rekap -->
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
                            Anda belum ditugaskan sebagai pembina pada kegiatan ekstrakurikuler manapun. Silakan hubungi bagian Kesiswaan/Admin.
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
                 note: '{{ addslashes($teacherNote->note ?? '') }}',
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
