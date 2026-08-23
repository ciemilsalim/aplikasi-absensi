<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('dashboard')],
                    ['title' => 'Verifikasi Klaim Orang Tua', 'url' => route('admin.parent_verification.index')]
                ]" />
                <div class="flex items-center gap-2 mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Verifikasi Klaim Orang Tua Siswa
                    </h1>
                    @if($pendingCount > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-500 text-white shadow-2xs animate-pulse">
                            {{ $pendingCount }} Menunggu Verifikasi
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5 sm:space-y-6">

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span class="material-icons text-base">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span class="material-icons text-base">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5">
            <form action="{{ route('admin.parent_verification.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    
                    <!-- Filter Status -->
                    <div>
                        <label for="status" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Status Pengajuan</label>
                        <select name="status" id="status" onchange="this.form.submit()"
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500">
                            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>⏳ Menunggu Verifikasi (Pending)</option>
                            <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>✅ Disetujui (Approved)</option>
                            <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>❌ Ditolak (Rejected)</option>
                            <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua Status</option>
                        </select>
                    </div>

                    <!-- Filter Kelas (jika bukan Wali Kelas terkunci) -->
                    <div>
                        <label for="school_class_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Filter Kelas</label>
                        <select name="school_class_id" id="school_class_id" onchange="this.form.submit()" {{ $homeroomClass && !auth()->user()->hasAnyRole(['admin', 'operator']) ? 'disabled' : '' }}
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-2.5 text-slate-800 dark:text-slate-100 focus:border-sky-500 disabled:opacity-60">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ (string)$classFilter === (string)$c->id || ($homeroomClass && $homeroomClass->id === $c->id) ? 'selected' : '' }}>
                                    Kelas {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div>
                        <label for="search" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Cari Orang Tua / Siswa</label>
                        <div class="relative">
                            <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">search</span>
                            <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Nama orang tua, siswa, NIS..."
                                   class="w-full text-xs py-2.5 pl-9 pr-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-medium">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Pengajuan -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            @if($requests->isEmpty())
                <div class="p-12 text-center text-xs text-slate-400 italic">
                    <span class="material-icons text-3xl mb-2 text-slate-300 dark:text-slate-600 block">fact_check</span>
                    <p>Tidak ada pengajuan verifikasi orang tua dengan filter ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-850 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3.5 px-4">Orang Tua Wali</th>
                                <th class="py-3.5 px-4">Siswa yang Ditingkat</th>
                                <th class="py-3.5 px-4">Kelas & NIS</th>
                                <th class="py-3.5 px-4">Kode Input</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-center">Aksi / Verifikator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                            @foreach($requests as $req)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/50 transition-colors">
                                    
                                    <!-- Orang Tua -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $req->parent?->name ?? 'Orang Tua' }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                                            <span class="material-icons text-[12px] text-emerald-500">phone</span>
                                            <span>{{ $req->parent?->phone_number ?? '-' }}</span>
                                        </div>
                                    </td>

                                    <!-- Siswa -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-sky-700 dark:text-sky-300">{{ $req->student?->name }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">Hubungan: {{ $req->relationship }}</div>
                                    </td>

                                    <!-- Kelas & NIS -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            Kelas {{ $req->student?->schoolClass?->name ?? '-' }}
                                        </span>
                                        <div class="text-[10px] text-slate-500 mt-1">NIS: {{ $req->student?->nis ?? '-' }}</div>
                                    </td>

                                    <!-- Kode Verifikasi -->
                                    <td class="py-3.5 px-4">
                                        <code class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-mono">
                                            {{ $req->verification_code ?: '(Tanpa Kode)' }}
                                        </code>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($req->status === 'pending')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                                <span class="material-icons text-xs">hourglass_top</span>
                                                <span>Menunggu Verifikasi</span>
                                            </span>
                                        @elseif($req->status === 'approved')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                <span class="material-icons text-xs">check_circle</span>
                                                <span>Disetujui</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                                                <span class="material-icons text-xs">cancel</span>
                                                <span>Ditolak</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Tombol Aksi / Verifikator -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        @if($req->status === 'pending')
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Setujui -->
                                                <form action="{{ route('admin.parent_verification.approve', $req) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menyetujui klaim anak ini?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold text-[11px] shadow-2xs transition-all">
                                                        <span class="material-icons text-xs">check</span>
                                                        <span>Setujui</span>
                                                    </button>
                                                </form>

                                                <!-- Tolak -->
                                                <form action="{{ route('admin.parent_verification.reject', $req) }}" method="POST" class="inline"
                                                      onsubmit="const reason = prompt('Masukkan alasan penolakan (opsional):', 'Data siswa belum dapat diverifikasi.'); if(reason === null) return false; this.querySelector('input[name=notes]').value = reason; return true;">
                                                    @csrf
                                                    <input type="hidden" name="notes" value="">
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 active:scale-95 text-white font-bold text-[11px] shadow-2xs transition-all">
                                                        <span class="material-icons text-xs">close</span>
                                                        <span>Tolak</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="text-[10px] text-slate-400">
                                                Oleh: <strong class="text-slate-700 dark:text-slate-300">{{ $req->verifier?->name ?? 'Sistem' }}</strong>
                                                <div class="text-[9px]">{{ $req->verified_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
