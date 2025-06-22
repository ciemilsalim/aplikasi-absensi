<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Riwayat Izin/Sakit', 'url' => route('parent.leave-requests.index')],
            ['title' => 'Buat Pengajuan', 'url' => '#']
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Form Pengajuan Izin/Sakit</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('parent.leave-requests.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div>
                            <x-input-label for="student_id" :value="__('Pilih Siswa')" />
                            <select name="student_id" id="student_id" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm" required>
                                <option value="">-- Pilih Anak --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="start_date" :value="__('Tanggal Mulai')" />
                                <x-text-input id="start_date" type="date" name="start_date" :value="old('start_date')" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="end_date" :value="__('Tanggal Selesai')" />
                                <x-text-input id="end_date" type="date" name="end_date" :value="old('end_date')" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="type" :value="__('Tipe Pengajuan')" />
                            <select name="type" id="type" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm" required>
                                <option value="sakit" {{ old('type') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="izin" {{ old('type') == 'izin' ? 'selected' : '' }}>Izin</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div>
                             <x-input-label for="reason" :value="__('Alasan')" />
                             <textarea id="reason" name="reason" rows="4" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm" required>{{ old('reason') }}</textarea>
                             <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="attachment" :value="__('Lampiran (Opsional, misal: Surat Dokter)')" />
                            <x-text-input id="attachment" name="attachment" type="file" class="block w-full mt-1" />
                            <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('parent.leave-requests.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Kirim Pengajuan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>