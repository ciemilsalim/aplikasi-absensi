<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Orang Tua/Wali', 'url' => route('parent.dashboard')],
                    ['title' => 'Riwayat Izin/Sakit', 'url' => route('parent.leave-requests.index')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Riwayat Pengajuan Izin / Sakit Anak
                </h1>
            </div>

            <a href="{{ route('parent.leave-requests.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0 shrink-0">
                <span class="material-icons text-base">add_circle</span>
                <span>Buat Pengajuan Baru</span>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2" role="alert">
                <span class="material-icons text-base">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-indigo-500 text-lg">fact_check</span>
                        Daftar Surat Izin & Sakit
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Status persetujuan pengajuan oleh wali kelas atau pihak sekolah</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                    <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Nama Siswa</th>
                            <th scope="col" class="px-6 py-3.5">Rentang Tanggal</th>
                            <th scope="col" class="px-6 py-3.5">Kategori & Alasan</th>
                            <th scope="col" class="px-6 py-3.5">Status Pengajuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($leaveRequests as $request)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 font-bold flex items-center justify-center">
                                        {{ strtoupper(substr($request->student->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $request->student->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                                    <span class="material-icons text-xs text-slate-400">event</span>
                                    <span>{{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase inline-block mb-1 {{ $request->type == 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' }}">
                                    {{ $request->type }}
                                </span>
                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $request->reason }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($request->status == 'approved')
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 text-[10px] font-bold px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disetujui
                                    </span>
                                @elseif ($request->status == 'rejected')
                                    <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 text-[10px] font-bold px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 text-[10px] font-bold px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Menunggu Konfirmasi
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-xs text-slate-400 italic">
                                Belum ada riwayat pengajuan izin atau sakit.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leaveRequests->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $leaveRequests->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>