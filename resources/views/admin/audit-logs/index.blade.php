<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Administrasi', 'url' => '#'],
                    ['title' => 'Audit Trail Presensi', 'url' => route('admin.audit_logs.index')]
                ]" />
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 dark:text-white tracking-tight leading-tight">
                    {{ __('Audit Trail & Log Koreksi Presensi') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Rekam jejak digital mutasi data kehadiran untuk audit akuntabilitas BOSP & pemeriksaan Inspektorat.
                </p>
            </div>
            
            <div class="flex items-center gap-2.5">
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition-all shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05.65-2.079 1.72-2.079h7.12c1.07 0 1.96 1.029 1.72 2.079M6.72 13.829a2.25 2.25 0 0 0-2.22 1.875l-.47 2.115A2.25 2.25 0 0 0 6.22 20.25h11.56a2.25 2.25 0 0 0 2.19-2.431l-.47-2.115a2.25 2.25 0 0 0-2.22-1.875M6.72 13.829h10.56M6 9V4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V9m-12 0h12" />
                    </svg>
                    <span>Cetak Bukti Audit</span>
                </button>

                @if(auth()->user()->hasAnyRole(['admin', 'operator', 'tu', 'tata_usaha']))
                <button x-data @click="$dispatch('open-correct-modal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    <span>Input Koreksi Presensi</span>
                </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="{ showModal: false }" @open-correct-modal.window="showModal = true" class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Alert Notifikasi --}}
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-medium flex items-center justify-between no-print">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-base text-emerald-600 dark:text-emerald-400">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- 1. KARTU STATISTIK JEJAK AUDIT (4 Kolom) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print">
                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-slate-700 dark:text-slate-300">history_edu</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Koreksi</p>
                        <h4 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ number_format($statistics['total_corrections']) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-emerald-600 dark:text-emerald-400">how_to_reg</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Koreksi Hadir/Tepat</p>
                        <h4 class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($statistics['to_present_count']) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-amber-600 dark:text-amber-400">medical_information</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Koreksi Sakit/Izin</p>
                        <h4 class="text-lg font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($statistics['to_excused_count']) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-rose-600 dark:text-rose-400">event_busy</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Koreksi Jadi Alpa</p>
                        <h4 class="text-lg font-extrabold text-rose-600 dark:text-rose-400">{{ number_format($statistics['to_absent_count']) }}</h4>
                    </div>
                </div>
            </div>

            {{-- 2. FILTER & SEARCH BAR --}}
            <div class="bg-white dark:bg-slate-850 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs no-print">
                <form method="GET" action="{{ route('admin.audit_logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    {{-- Pencarian --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Cari Siswa / Pengubah</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama, NIS, atau alasan..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    {{-- Rentang Tanggal Kehadiran --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    {{-- Filter Status Baru --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Status Baru</label>
                        <select name="new_status" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                            <option value="">Semua Status</option>
                            <option value="tepat_waktu" {{ request('new_status') === 'tepat_waktu' ? 'selected' : '' }}>Tepat Waktu</option>
                            <option value="terlambat" {{ request('new_status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="sakit" {{ request('new_status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="izin" {{ request('new_status') === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="alpa" {{ request('new_status') === 'alpa' ? 'selected' : '' }}>Alpa</option>
                        </select>
                    </div>

                    {{-- Tombol Submit & Reset --}}
                    <div class="flex items-center gap-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 dark:bg-slate-700 text-white text-xs font-bold hover:bg-slate-800 transition shadow-xs">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'start_date', 'end_date', 'new_status', 'school_class_id', 'action_type']))
                            <a href="{{ route('admin.audit_logs.index') }}" class="py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- 3. TABEL AUDIT TRAIL --}}
            <div class="bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Daftar Rekam Jejak Audit</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Menampilkan seluruh intervensi dan perubahan presensi secara kronologis.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $logs->total() }} Log Tercatat</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 text-slate-600 dark:text-slate-400 font-extrabold text-[11px]">
                                <th class="py-3 px-4">Waktu Koreksi</th>
                                <th class="py-3 px-4">Tgl Hadir</th>
                                <th class="py-3 px-4">Siswa</th>
                                <th class="py-3 px-4 text-center">Perubahan Status</th>
                                <th class="py-3 px-4">Alasan & Justifikasi</th>
                                <th class="py-3 px-4">Petugas / Aktor</th>
                                <th class="py-3 px-4 text-right">Tipe Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse($logs as $log)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                    {{-- Waktu Koreksi --}}
                                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $log->created_at->format('d/m/Y') }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('H:i:s') }} WITA</div>
                                    </td>

                                    {{-- Tanggal Hadir --}}
                                    <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 whitespace-nowrap font-semibold">
                                        {{ $log->attendance_date->format('d M Y') }}
                                    </td>

                                    {{-- Siswa --}}
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $log->student_name }}</div>
                                        <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                            <span>NIS: {{ $log->student_nis ?? '-' }}</span>
                                            @if($log->class_name)
                                                <span>•</span>
                                                <span class="font-semibold text-sky-600 dark:text-sky-400">{{ $log->class_name }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Perubahan Status (Badge Sebelum -> Sesudah) --}}
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 font-bold text-[10px]">
                                            @php
                                                $formatStatusBadge = function($status) {
                                                    return match($status) {
                                                        'tepat_waktu' => '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300">Tepat Waktu</span>',
                                                        'terlambat'   => '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300">Terlambat</span>',
                                                        'sakit'       => '<span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300">Sakit</span>',
                                                        'izin'        => '<span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300">Izin</span>',
                                                        'alpa'        => '<span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300">Alpa</span>',
                                                        'belum_absen' => '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Belum Hadir</span>',
                                                        default       => '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">' . ($status ?? '-') . '</span>'
                                                    };
                                                };
                                            @endphp
                                            {!! $formatStatusBadge($log->old_status) !!}
                                            <span class="text-slate-400">→</span>
                                            {!! $formatStatusBadge($log->new_status) !!}
                                        </div>
                                    </td>

                                    {{-- Alasan & Justifikasi --}}
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <p class="text-slate-700 dark:text-slate-300 line-clamp-2 leading-relaxed font-medium">
                                            {{ $log->reason }}
                                        </p>
                                        @if($log->new_notes && $log->new_notes !== $log->reason)
                                            <p class="text-[10px] text-slate-400 mt-0.5 italic">Catatan: {{ $log->new_notes }}</p>
                                        @endif
                                    </td>

                                    {{-- Petugas / Aktor --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $log->changed_by_name ?? 'Sistem' }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase font-semibold">
                                            {{ $log->changed_by_role ?? 'Sistem' }}
                                        </div>
                                    </td>

                                    {{-- Tipe Aksi --}}
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        @php
                                            $actionBadge = match($log->action_type) {
                                                'manual_correction' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900',
                                                'teacher_override'  => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-900',
                                                'leave_sync'        => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900',
                                                default             => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                                            };
                                            $actionLabel = match($log->action_type) {
                                                'manual_correction' => 'Koreksi Admin/TU',
                                                'teacher_override'  => 'Wali Kelas',
                                                'leave_sync'        => 'Sinkronisasi Izin',
                                                default             => $log->action_type
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $actionBadge }}">
                                            {{ $actionLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <span class="material-icons text-4xl mb-2 text-slate-300 dark:text-slate-600">verified_user</span>
                                        <p class="text-xs font-semibold">Belum ada riwayat koreksi presensi yang dicatat.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($logs->hasPages())
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 no-print">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

            {{-- 4. MODAL POPUP INPUT KOREKSI PRESENSI MANUAL --}}
            <div x-show="showModal" 
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 style="display: none;">
                
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>

                {{-- Dialog --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-lg bg-white dark:bg-slate-850 rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-750 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Form Koreksi Presensi Siswa</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Setiap perubahan akan dicatat ke dalam audit trail BOS & Inspektorat.</p>
                            </div>
                            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-icons">close</span>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.audit_logs.correct') }}" class="space-y-4">
                            @csrf

                            {{-- Pilih Siswa --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Siswa *</label>
                                <select name="student_id" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                                    <option value="">-- Pilih Siswa --</option>
                                    @foreach(\App\Models\Student::with('schoolClass')->orderBy('name')->get() as $s)
                                        <option value="{{ $s->id }}">
                                            {{ $s->name }} (NIS: {{ $s->nis }}) - {{ $s->schoolClass?->name ?? 'Tanpa Kelas' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tanggal Kehadiran & Status Baru --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Kehadiran *</label>
                                    <input type="date" name="attendance_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status Baru *</label>
                                    <select name="new_status" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                                        <option value="tepat_waktu">Tepat Waktu (Hadir)</option>
                                        <option value="terlambat">Terlambat</option>
                                        <option value="sakit">Sakit</option>
                                        <option value="izin">Izin</option>
                                        <option value="alpa">Alpa</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Alasan / Justifikasi Wajib --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Alasan Koreksi (Wajib untuk Akuntabilitas) *
                                </label>
                                <textarea name="reason" rows="2" required placeholder="Contoh: Orang tua menyerahkan surat dokter dari Puskesmas / Dispensasi lomba OSN..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500"></textarea>
                                <p class="text-[10px] text-slate-400 mt-1">Alasan ini akan ditunjukkan secara permanen pada laporan rekonsiliasi audit.</p>
                            </div>

                            {{-- Catatan Tambahan (Opsional) --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Catatan internal tambahan..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-rose-500 focus:border-rose-500">
                            </div>

                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                                <button type="button" @click="showModal = false" class="py-2.5 px-4 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    Batal
                                </button>
                                <button type="submit" class="py-2.5 px-5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20">
                                    Simpan & Catat Audit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
