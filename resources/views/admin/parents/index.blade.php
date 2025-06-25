<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Manajemen Ortu', 'url' => route('admin.parents.index')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Akun Orang Tua</h3>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.parents.import.form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Impor
                            </a>
                            <a href="{{ route('admin.parents.create') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                                Tambah Akun
                            </a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                    @if (session('import_errors'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Gagal Impor!</strong>
                            <ul class="mt-2 list-disc list-inside text-sm">
                                @foreach (session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="GET" action="{{ route('admin.parents.index') }}" class="mb-6">
                        <div class="relative">
                            <x-text-input type="text" name="search" placeholder="Cari berdasarkan nama atau email..." value="{{ request('search') }}" class="w-full pl-10"/>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            </div>
                        </div>
                    </form>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Orang Tua</th>
                                    <th scope="col" class="px-6 py-3">Email Login</th>
                                    <th scope="col" class="px-6 py-3">Nomor HP</th>
                                    <th scope="col" class="px-6 py-3 text-center">Jumlah Anak</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($parents as $parent)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center">
                                            <span id="status-dot-{{ $parent->user_id }}" class="h-2.5 w-2.5 rounded-full bg-gray-400 mr-3 transition-colors duration-500" title="Offline"></span>
                                            {{ $parent->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ $parent->user->email }}</td>
                                    <td class="px-6 py-4">{{ $parent->phone_number }}</td>
                                    <td class="px-6 py-4 text-center">{{ $parent->students_count }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-4">
                                            <a href="{{ route('admin.parents.edit', $parent) }}" class="font-medium text-green-600 dark:text-green-500 hover:underline">Hubungkan</a>
                                            <form action="{{ route('admin.parents.destroy', $parent) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus akun ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="px-6 py-4 text-center">Tidak ada data orang tua.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $parents->appends(request()->query())->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function fetchOnlineStatus() {
                fetch('{{ route('admin.parents.online_status') }}')
                    .then(response => response.json())
                    .then(onlineUserIds => {
                        document.querySelectorAll('[id^="status-dot-"]').forEach(dot => {
                            dot.classList.remove('bg-green-500');
                            dot.classList.add('bg-gray-400');
                            dot.title = 'Offline';
                        });

                        onlineUserIds.forEach(userId => {
                            const dot = document.getElementById(`status-dot-${userId}`);
                            if (dot) {
                                dot.classList.remove('bg-gray-400');
                                dot.classList.add('bg-green-500');
                                dot.title = 'Online';
                            }
                        });
                    })
                    .catch(error => console.error('Gagal mengambil status online:', error));
            }

            fetchOnlineStatus();
            setInterval(fetchOnlineStatus, 30000); 
        });
    </script>
    @endpush
</x-app-layout>
