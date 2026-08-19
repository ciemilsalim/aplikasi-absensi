<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Admin Dasbor', 'url' => route('dashboard')],
                    ['title' => 'Supervisi Jurnal Mengajar', 'url' => route('admin.teaching_journals.index')],
                    ['title' => $teacher->name, 'url' => route('admin.teaching_journals.show', $teacher)]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Supervisi Jurnal: {{ $teacher->name }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                    NIP: {{ $teacher->nip ?: '-' }} &bull; SMP Negeri 1 Biau
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.journals.print') }}?teacher_id={{ $teacher->id }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition-all">
                    <span class="material-icons text-base">print</span>
                    <span>Cetak Dokumen Resmi Guru</span>
                </a>
                <a href="{{ route('admin.teaching_journals.index') }}" 
                   class="inline-flex items-center gap-1 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 transition-all">
                    <span class="material-icons text-base">arrow_back</span>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Stats Guru -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Entri Jurnal</span>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ $journals->total() }}</h3>
                <p class="text-[11px] text-slate-400">Pertemuan pembelajaran terlaksana</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Durasi JP</span>
                <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $totalJp }} <span class="text-sm font-bold text-slate-400">JP</span></h3>
                <p class="text-[11px] text-slate-400">Jam pelajaran di kelas</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Validasi</span>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $totalVerified }} <span class="text-sm font-bold text-slate-400">/ {{ $journals->total() }}</span></h3>
                <p class="text-[11px] text-slate-400">Telah diverifikasi supervisor</p>
            </div>
        </div>

        <!-- Refleksi Akhir Semester Guru (Bagian F) jika ada -->
        @if($reflection)
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <span class="material-icons text-amber-500 text-xl">psychology</span>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Refleksi Akhir Semester Guru (Bagian F)</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 block mb-1">1. Pembelajaran yang Berjalan Baik</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reflection->good_aspects }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="font-bold text-rose-600 dark:text-rose-400 block mb-1">2. Kendala Utama</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reflection->challenges }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="font-bold text-amber-600 dark:text-amber-400 block mb-1">3. Peserta Didik Butuh Perhatian</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reflection->attention_students }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="font-bold text-sky-600 dark:text-sky-400 block mb-1">4. Strategi Efektif</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reflection->effective_strategies }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 block mb-1">5. Perbaikan Semester Berikutnya</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reflection->future_improvements }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="font-bold text-purple-600 dark:text-purple-400 block mb-1">6. Rencana Tindak Lanjut</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reflection->follow_up_plan }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Daftar Jurnal Guru -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-slate-800">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Riwayat Pertemuan & Jurnal Pembelajaran</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5 text-center w-12">No.</th>
                            <th class="p-3.5">Tanggal & Kelas</th>
                            <th class="p-3.5 min-w-[200px]">TP & Topik</th>
                            <th class="p-3.5 min-w-[180px]">Kegiatan & Asesmen</th>
                            <th class="p-3.5 min-w-[160px]">Hasil & Tindak Lanjut</th>
                            <th class="p-3.5 text-center">Supervisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($journals as $index => $j)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3.5 text-center font-bold text-slate-400">{{ $journals->firstItem() + $index }}</td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $j->date->translatedFormat('d M Y') }}</div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 font-extrabold text-[11px] mt-0.5">
                                        {{ $j->schoolClass?->name ?? '-' }}
                                    </span>
                                    <div class="text-[11px] text-slate-400">{{ $j->subject?->name ?? '-' }} &bull; {{ $j->jp }} JP</div>
                                </td>
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $j->topic }}</div>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 mt-0.5">{{ $j->learning_objective }}</p>
                                </td>
                                <td class="p-3.5">
                                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">{{ $j->activity }}</p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold mt-1">
                                        {{ $j->assessment }}
                                    </span>
                                </td>
                                <td class="p-3.5">
                                    <p class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2">{{ $j->reflection }}</p>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-1">TL: {{ $j->follow_up }}</div>
                                </td>
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <form action="{{ route('admin.teaching_journals.verify', $j) }}" method="POST" class="inline">
                                        @csrf
                                        @if($j->is_verified)
                                            <input type="hidden" name="is_verified" value="0">
                                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 font-bold text-[10px]" title="Klik untuk membatalkan verifikasi">
                                                <span class="material-icons text-xs align-middle">check_circle</span> Terverifikasi
                                            </button>
                                        @else
                                            <input type="hidden" name="is_verified" value="1">
                                            <input type="hidden" name="supervisor_notes" value="Disupervisi & Sesuai TP">
                                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] shadow-sm transition-all active:scale-95">
                                                Setujui
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    Guru ini belum memiliki catatan jurnal mengajar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($journals->hasPages())
                <div class="p-4 border-t border-slate-200/80 dark:border-slate-800">
                    {{ $journals->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
