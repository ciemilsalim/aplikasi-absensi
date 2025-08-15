<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Guru', 'url' => route('admin.teachers.index')],
            ['title' => 'Tambah Baru', 'url' => '#']
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tambah Data Guru Baru</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.teachers.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div>
                            <x-input-label for="name" :value="__('Nama Lengkap Guru')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="nip" :value="__('NIP (Opsional)')" />
                            <x-text-input id="nip" class="block mt-1 w-full" type="text" name="nip" :value="old('nip')" />
                            <x-input-error class="mt-2" :messages="$errors->get('nip')" />
                        </div>
                         <div>
                            <x-input-label for="phone_number" :value="__('Nomor HP (Opsional)')" />
                            <x-text-input id="phone_number" class="block mt-1 w-full" type="text" name="phone_number" :value="old('phone_number')" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                        </div>

                        <!-- TAMBAHKAN BAGIAN INI -->
                        <div>
                            <x-input-label for="subjects" :value="__('Mata Pelajaran yang Diampu')" />
                            <select name="subjects[]" id="subjects" multiple class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm">
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ (collect(old('subjects'))->contains($subject->id)) ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tahan tombol Ctrl (atau Cmd di Mac) untuk memilih lebih dari satu.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('subjects')" />
                        </div>
                        <!-- AKHIR BAGIAN TAMBAHAN -->

                        <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                            <p class="text-base font-medium text-gray-900 dark:text-gray-100">Informasi Akun Login</p>
                        </div>
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.teachers.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Simpan Akun Guru</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
