<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'], // Item tanpa link
            ['title' => 'Orang Tua', 'url' => route('admin.parents.index')],
            ['title' => 'Tambah Akun', 'url' => route('admin.parents.create')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tambah Akun Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.parents.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div>
                            <x-input-label for="name" :value="__('Nama Lengkap Orang Tua')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="phone_number" :value="__('Nomor HP (WhatsApp)')" />
                            <x-text-input id="phone_number" class="block mt-1 w-full" type="text" name="phone_number" :value="old('phone_number')" placeholder="Contoh: 081234567890" required />
                            <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                        </div>
                        <div>
                            <x-input-label for="email" :value="__('Email (Untuk Login)')" />
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
                        <a href="{{ route('admin.parents.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Simpan Akun</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
