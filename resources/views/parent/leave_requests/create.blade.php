<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Riwayat Izin/Sakit', 'url' => route('parent.leave-requests.index')],
                    ['title' => 'Buat Pengajuan', 'url' => '#']
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Form Pengajuan Izin / Sakit Siswa
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                        <span class="material-icons text-2xl">edit_document</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Informasi Surat Izin</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Mohon lengkapi formulir di bawah ini dengan data yang sebenarnya.</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('parent.leave-requests.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Pilih Siswa -->
                    <div>
                        <label for="student_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Pilih Siswa / Anak <span class="text-rose-500">*</span>
                        </label>
                        <select name="student_id" id="student_id" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all" 
                                required>
                            <option value="">-- Pilih Anak yang Diajukan Izin --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} (Kelas: {{ $student->schoolClass->name ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('student_id')" class="mt-1.5" />
                    </div>

                    <!-- Rentang Tanggal -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                Tanggal Mulai <span class="text-rose-500">*</span>
                            </label>
                            <input id="start_date" type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" 
                                   class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all" 
                                   required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-1.5" />
                        </div>
                        <div>
                            <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                Tanggal Selesai <span class="text-rose-500">*</span>
                            </label>
                            <input id="end_date" type="date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" 
                                   class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all" 
                                   required />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-1.5" />
                        </div>
                    </div>

                    <!-- Tipe Pengajuan -->
                    <div>
                        <label for="type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Kategori Izin <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" id="type" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all" 
                                required>
                            <option value="sakit" {{ old('type') == 'sakit' ? 'selected' : '' }}>Sakit (Disertai istirahat / periksa dokter)</option>
                            <option value="izin" {{ old('type') == 'izin' ? 'selected' : '' }}>Izin (Acara keluarga / urusan mendesak)</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
                    </div>

                    <!-- Alasan / Keterangan -->
                    <div>
                        <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Alasan & Keterangan Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason" name="reason" rows="4" 
                                  class="w-full text-xs rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all" 
                                  placeholder="Jelaskan alasan izin / sakit secara rinci..." 
                                  required>{{ old('reason') }}</textarea>
                        <x-input-error :messages="$errors->get('reason')" class="mt-1.5" />
                    </div>

                    <!-- Lampiran File -->
                    <div>
                        <label for="attachment" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Lampiran Dokumen <span class="text-slate-400 font-normal normal-case">(Opsional: Foto surat dokter / surat izin)</span>
                        </label>
                        <div class="relative">
                            <input id="attachment" name="attachment" type="file" 
                                   class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-950/60 dark:file:text-sky-300 hover:file:bg-sky-100 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-2xl p-2 bg-slate-50 dark:bg-slate-850" />
                        </div>
                        <x-input-error :messages="$errors->get('attachment')" class="mt-1.5" />
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="bg-slate-50/75 dark:bg-slate-850/50 px-6 sm:px-8 py-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('parent.leave-requests.index') }}" 
                       class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                        Batal
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                        <span class="material-icons text-base">send</span>
                        <span>Kirim Permohonan Izin</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>