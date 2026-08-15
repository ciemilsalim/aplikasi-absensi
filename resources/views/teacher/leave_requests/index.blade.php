<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('teacher.dashboard')],
                    ['title' => 'Pengajuan Izin', 'url' => route('teacher.leave_requests.index')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Pengajuan Izin Siswa Perwalian
                </h1>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40 text-xs font-bold text-amber-700 dark:text-amber-300">
                    <span class="w-2 h-2 rounded-full bg-amber-500 {{ $pendingRequests->count() > 0 ? 'animate-ping' : '' }}"></span>
                    <span>{{ $pendingRequests->count() }} Menunggu Validasi</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2" role="alert">
                <span class="material-icons text-base">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2" role="alert">
                <span class="material-icons text-base">error_outline</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- 1. Bagian Pengajuan yang Perlu Diproses -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-amber-500 text-lg">pending_actions</span>
                        Pengajuan Perlu Diproses (Wali Kelas)
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tinjau permohonan izin dari orang tua siswa kelas binaan Anda</p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                    <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Siswa & Wali Murid</th>
                            <th scope="col" class="px-6 py-3.5">Rentang Tanggal</th>
                            <th scope="col" class="px-6 py-3.5">Keterangan & Bukti</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($pendingRequests as $request)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-300 font-bold flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-900/40">
                                        {{ strtoupper(substr($request->student->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $request->student->name }}</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Diajukan: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $request->parent->name ?? 'Orang Tua' }}</span></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    <span class="material-icons text-xs text-slate-400">date_range</span>
                                    <span>{{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $request->type == 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' }}">
                                        {{ $request->type }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm">{{ $request->reason }}</p>
                                @if($request->attachment)
                                    <a href="{{ asset('storage/' . $request->attachment) }}" target="_blank" 
                                       class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline mt-1.5">
                                        <span class="material-icons text-xs">attachment</span>
                                        <span>Lihat Dokumen / Surat Dokter</span>
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 relative text-center">
                                <div class="flex items-center justify-center gap-2" x-data="{ showRejectForm: false }">
                                    <form action="{{ route('teacher.leave_requests.approve', $request) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui pengajuan izin ini?');">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition-all active:scale-95">
                                            <span class="material-icons text-sm">check</span>
                                            <span>Setujui</span>
                                        </button>
                                    </form>
                                    
                                    <button @click="showRejectForm = !showRejectForm" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-900/40 text-xs font-bold transition-all active:scale-95">
                                        <span class="material-icons text-sm">close</span>
                                        <span>Tolak</span>
                                    </button>

                                    <!-- Rejection Form Popup -->
                                    <div x-show="showRejectForm" @click.away="showRejectForm = false" 
                                         class="absolute right-6 top-12 w-72 bg-white dark:bg-slate-850 p-4 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 z-30 text-left" 
                                         style="display: none;" x-transition>
                                        <form action="{{ route('teacher.leave_requests.reject', $request) }}" method="POST">
                                            @csrf
                                            <label for="rejection_reason_{{ $request->id }}" class="text-xs font-bold text-slate-800 dark:text-white block mb-1">
                                                Alasan Penolakan
                                            </label>
                                            <input type="text" name="rejection_reason" id="rejection_reason_{{ $request->id }}" 
                                                   class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500" 
                                                   placeholder="Tulis alasan singkat..." required />
                                            <div class="flex justify-end gap-2 mt-3">
                                                <button type="button" @click="showRejectForm = false" 
                                                        class="px-2.5 py-1 text-xs font-bold text-slate-500 hover:text-slate-700">
                                                    Batal
                                                </button>
                                                <button type="submit" 
                                                        class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-xs">
                                                    Kirim Tolak
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-xs text-slate-400 italic">
                                Tidak ada pengajuan izin dari siswa kelas perwalian yang perlu diproses.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Bagian Riwayat Pengajuan Selesai -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-icons text-sky-500 text-lg">history</span>
                    Riwayat Keputusan Izin Siswa Perwalian
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                    <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Nama Siswa</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal Izin</th>
                            <th scope="col" class="px-6 py-3.5">Status Akhir</th>
                            <th scope="col" class="px-6 py-3.5">Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($processedRequests as $request)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                            <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">
                                {{ $request->student->name }}
                            </td>
                            <td class="px-6 py-3.5 font-semibold">
                                {{ $request->start_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($request->status == 'approved')
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disetujui
                                    </span>
                                @elseif ($request->status == 'rejected')
                                    <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 font-medium">
                                {{ $request->approver->name ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-xs text-slate-400 italic">
                                Belum ada riwayat pengajuan izin yang diproses.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($processedRequests->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $processedRequests->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

