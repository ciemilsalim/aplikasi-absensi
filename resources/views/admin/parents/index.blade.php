<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'], // Item tanpa link
            ['title' => 'Orang Tua', 'url' => route('admin.parents.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Akun Orang Tua</h3>
                        <a href="{{ route('admin.parents.create') }}" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                            Tambah Akun Orang Tua
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Orang Tua</th>
                                    <th scope="col" class="px-6 py-3">Email (Untuk Login)</th>
                                    <th scope="col" class="px-6 py-3">Nomor HP</th>
                                    <th scope="col" class="px-6 py-3">Jumlah Anak</th>
                                    <th scope="col" class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($parents as $parent)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $parent->name }}</td>
                                    <td class="px-6 py-4">{{ $parent->user->email }}</td>
                                    <td class="px-6 py-4">{{ $parent->phone_number }}</td>
                                    <td class="px-6 py-4">{{ $parent->students_count }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.parents.edit', $parent) }}" class="font-medium text-green-600 dark:text-green-500 hover:underline">Hubungkan Siswa</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="px-6 py-4 text-center">Belum ada data orang tua.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $parents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
