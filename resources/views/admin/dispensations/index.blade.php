<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Administrasi', 'url' => '#'],
                    ['title' => 'E-Dispensasi Siswa', 'url' => route('admin.dispensations.index')]
                ]" />
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 dark:text-white tracking-tight leading-tight">
                    {{ __('E-Dispensasi & Pass Card Gerbang Siswa') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Penerbitan izin resmi siswa pulang cepat & keluar sementara terintegrasi sistem pemindai satpam dan notifikasi orang tua.
                </p>
            </div>
            
            <div class="flex items-center gap-2.5">
                <a href="{{ route('permit.scanner') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition-all shadow-xs">
                    <span class="material-icons text-base text-indigo-500">qr_code_scanner</span>
                    <span>Buka Scanner Gerbang</span>
                </a>

                @if(auth()->user()->hasAnyRole(['admin', 'operator', 'teacher', 'guru', 'satpam', 'tu', 'tata_usaha']))
                <button x-data @click="$dispatch('open-dispensation-modal')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <span class="material-icons text-base">add_card</span>
                    <span>+ Terbitkan E-Dispensasi</span>
                </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="{ showModal: false, selectedType: 'pulang_cepat' }" @open-dispensation-modal.window="showModal = true" class="py-6 sm:py-8">
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

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-medium flex items-center justify-between no-print">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-base text-rose-600 dark:text-rose-400">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{-- 1. KARTU STATISTIK DISPENSASI (4 Kolom) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print">
                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-amber-600 dark:text-amber-400">directions_run</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Masih di Luar</p>
                            @if(($statistics['active_outside_count'] ?? 0) > 0)
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                </span>
                            @endif
                        </div>
                        <h4 class="text-lg font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($statistics['active_outside_count'] ?? 0) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-rose-600 dark:text-rose-400">home</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Pulang Cepat Hari Ini</p>
                        <h4 class="text-lg font-extrabold text-rose-600 dark:text-rose-400">{{ number_format($statistics['early_leave_today'] ?? 0) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-indigo-600 dark:text-indigo-400">access_time</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Keluar Sementara</p>
                        <h4 class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400">{{ number_format($statistics['temporary_outside_today'] ?? 0) }}</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl text-slate-700 dark:text-slate-300">confirmation_number</span>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Izin Hari Ini</p>
                        <h4 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ number_format($statistics['total_dispensations_today'] ?? 0) }}</h4>
                    </div>
                </div>
            </div>

            {{-- 2. FILTER & SEARCH BAR --}}
            <div class="bg-white dark:bg-slate-850 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs no-print">
                <form method="GET" action="{{ route('admin.dispensations.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    {{-- Pencarian --}}
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Cari Siswa / Kode Pass</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Ketik nama, NIS, kode pass (PASS-)..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Filter Kelas --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Kelas</label>
                        <select name="school_class_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ ($filters['school_class_id'] ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Jenis Dispensasi --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Jenis Dispensasi</label>
                        <select name="permit_type" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="all">Semua Jenis</option>
                            <option value="pulang_cepat" {{ ($filters['permit_type'] ?? '') == 'pulang_cepat' ? 'selected' : '' }}>Pulang Cepat</option>
                            <option value="keluar_sementara" {{ ($filters['permit_type'] ?? '') == 'keluar_sementara' ? 'selected' : '' }}>Keluar Sementara</option>
                            <option value="dispensasi_lomba" {{ ($filters['permit_type'] ?? '') == 'dispensasi_lomba' ? 'selected' : '' }}>Dispensasi Lomba</option>
                            <option value="tugas_luar" {{ ($filters['permit_type'] ?? '') == 'tugas_luar' ? 'selected' : '' }}>Tugas Luar</option>
                            <option value="lainnya" {{ ($filters['permit_type'] ?? '') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>

                    {{-- Filter Status --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Status Gerbang</label>
                        <select name="status" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="all">Semua Status</option>
                            <option value="approved" {{ ($filters['status'] ?? '') == 'approved' ? 'selected' : '' }}>Di Luar (Belum Kembali)</option>
                            <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Selesai / Sudah Kembali</option>
                            <option value="pending_approval" {{ ($filters['status'] ?? '') == 'pending_approval' ? 'selected' : '' }}>Menunggu Gerbang</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>

                    {{-- Tombol Aksi Filter --}}
                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <span class="material-icons text-sm">filter_alt</span>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('admin.dispensations.index') }}" class="py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold" title="Reset Filter">
                            <span class="material-icons text-sm">restart_alt</span>
                        </a>
                    </div>
                </form>
            </div>

            {{-- 3. TABEL DATA E-DISPENSASI --}}
            <div class="bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Pass Code / Waktu</th>
                                <th class="py-3.5 px-4">Siswa & Kelas</th>
                                <th class="py-3.5 px-4">Jenis & Keperluan</th>
                                <th class="py-3.5 px-4">Otorisasi Guru</th>
                                <th class="py-3.5 px-4">Verifikasi Satpam</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($permits as $permit)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    {{-- Pass Code & Waktu --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800 text-[11px]">
                                                {{ $permit->pass_code ?? ('PERMIT #' . $permit->id) }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1">
                                            {{ $permit->created_at ? $permit->created_at->format('d/m/Y H:i') : '-' }}
                                        </p>
                                    </td>

                                    {{-- Siswa & Kelas --}}
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">
                                            {{ $permit->student?->name ?? 'Siswa Tidak Ditemukan' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            NIS: {{ $permit->student?->nis ?? '-' }} • <span class="font-medium text-slate-600 dark:text-slate-300">{{ $permit->student?->schoolClass?->name ?? 'Tanpa Kelas' }}</span>
                                        </div>
                                    </td>

                                    {{-- Jenis & Keperluan --}}
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold mb-1
                                            @if($permit->permit_type === 'pulang_cepat') bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800
                                            @elseif($permit->permit_type === 'keluar_sementara') bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800
                                            @elseif($permit->permit_type === 'dispensasi_lomba') bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800
                                            @else bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700
                                            @endif">
                                            <span>{{ $permit->permit_type_label }}</span>
                                        </div>
                                        <p class="text-slate-700 dark:text-slate-300 line-clamp-2 text-[11px]">
                                            {{ $permit->reason }}
                                        </p>
                                        @if($permit->notes)
                                            <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5 italic">
                                                Catatan: {{ $permit->notes }}
                                            </p>
                                        @endif
                                    </td>

                                    {{-- Otorisasi Guru --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">
                                            {{ $permit->approved_by_name ?? ($permit->approver?->name ?? 'Admin / Guru Piket') }}
                                        </div>
                                        @if($permit->valid_until)
                                            <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">
                                                Batas: {{ \Carbon\Carbon::parse($permit->valid_until)->format('H:i') }} WITA
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Verifikasi Satpam --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($permit->checkout_time)
                                            <div class="text-slate-700 dark:text-slate-300 font-medium">
                                                Keluar: <span class="font-bold">{{ $permit->checkout_time->format('H:i') }}</span>
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                Satpam: {{ $permit->security_name ?? ($permit->security?->name ?? 'Pos Gerbang') }}
                                            </div>
                                            @if($permit->checkin_time)
                                                <div class="text-emerald-600 dark:text-emerald-400 font-medium text-[10px] mt-0.5">
                                                    Kembali: {{ $permit->checkin_time->format('H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-slate-400 italic text-[11px]">Belum scan keluar</span>
                                        @endif
                                    </td>

                                    {{-- Status Gerbang --}}
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        @if($permit->status === 'completed')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                <span class="material-icons text-[12px]">check_circle</span>
                                                <span>Selesai / Kembali</span>
                                            </span>
                                        @elseif($permit->status === 'approved')
                                            @if($permit->permit_type === 'pulang_cepat')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                                    <span class="material-icons text-[12px]">home</span>
                                                    <span>Pulang Cepat</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                                    <span class="material-icons text-[12px]">directions_walk</span>
                                                    <span>Masih di Luar</span>
                                                </span>
                                            @endif
                                        @elseif($permit->status === 'pending_approval')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                                <span class="material-icons text-[12px]">schedule</span>
                                                <span>Siap di Gerbang</span>
                                            </span>
                                        @elseif($permit->status === 'cancelled')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700">
                                                <span class="material-icons text-[12px]">cancel</span>
                                                <span>Dibatalkan</span>
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap no-print">
                                        <div class="inline-flex items-center gap-1.5">
                                            {{-- Cetak Pass Card --}}
                                            <a href="{{ route('admin.dispensations.print', $permit->id) }}" target="_blank" class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600" title="Cetak Pass Card Digital">
                                                <span class="material-icons text-base">print</span>
                                            </a>

                                            {{-- Batalkan Izin --}}
                                            @if(!in_array($permit->status, ['completed', 'cancelled']) && auth()->user()->hasAnyRole(['admin', 'operator', 'teacher', 'guru']))
                                                <form method="POST" action="{{ route('admin.dispensations.destroy', $permit->id) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan e-dispensasi ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 rounded-lg border border-rose-200 dark:border-rose-900 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50" title="Batalkan Izin">
                                                        <span class="material-icons text-base">delete_outline</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <span class="material-icons text-4xl mb-2 text-slate-300 dark:text-slate-600">badge</span>
                                        <p class="text-xs font-semibold">Belum ada data E-Dispensasi yang diterbitkan hari ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($permits->hasPages())
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 no-print">
                        {{ $permits->links() }}
                    </div>
                @endif
            </div>

            {{-- 4. MODAL POPUP PENERBITAN E-DISPENSASI & PASS CARD --}}
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
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <span class="material-icons text-xl">confirmation_number</span>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Terbitkan E-Dispensasi</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Pass card otomatis terverifikasi di gerbang satpam.</p>
                                </div>
                            </div>
                            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-icons">close</span>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.dispensations.store') }}" class="space-y-4">
                            @csrf

                            {{-- Pilih Siswa --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Siswa *</label>
                                <select name="student_id" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Pilih Siswa --</option>
                                    @foreach(\App\Models\Student::with('schoolClass')->orderBy('name')->get() as $s)
                                        <option value="{{ $s->id }}">
                                            {{ $s->name }} (NIS: {{ $s->nis }}) - {{ $s->schoolClass?->name ?? 'Tanpa Kelas' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Jenis Dispensasi & Batas Waktu --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jenis Dispensasi *</label>
                                    <select name="permit_type" x-model="selectedType" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="pulang_cepat">Pulang Cepat (Sakit/Keluarga)</option>
                                        <option value="keluar_sementara">Keluar Sementara (Kembali)</option>
                                        <option value="dispensasi_lomba">Dispensasi Lomba / Olimpiade</option>
                                        <option value="tugas_luar">Tugas Luar Sekolah</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Batas Waktu Berlaku</label>
                                    <input type="time" name="valid_until" value="{{ date('H:i', strtotime('+2 hours')) }}" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            {{-- Alasan / Keperluan --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Alasan & Keperluan Lengkap *
                                </label>
                                <textarea name="reason" rows="2" required placeholder="Contoh: Mengalami demam tinggi di UKS dijemput orang tua / Mengikuti seleksi O2SN di Dinas Pendidikan..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>

                            {{-- Catatan untuk Satpam Gerbang --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Khusus untuk Satpam (Opsional)</label>
                                <input type="text" name="notes" placeholder="Misal: Dijemput ibunya, mohon cek identitas penjemput..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            {{-- Checkbox Notifikasi WhatsApp --}}
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 flex items-center gap-3">
                                <input type="checkbox" name="notify_parent" id="notify_parent" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                <label for="notify_parent" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                    <span class="font-bold">Kirim Notifikasi WhatsApp ke Wali Murid</span>
                                    <span class="block text-[10px] text-slate-400">Pemberitahuan otomatis saat pass card diterbitkan dan saat siswa melewati gerbang.</span>
                                </label>
                            </div>

                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                                <button type="button" @click="showModal = false" class="py-2.5 px-4 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    Batal
                                </button>
                                <button type="submit" class="py-2.5 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/20 flex items-center gap-1.5">
                                    <span class="material-icons text-sm">qr_code</span>
                                    <span>Terbitkan & Cetak</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
