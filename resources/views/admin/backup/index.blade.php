<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Backup', 'url' => route('admin.backup.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Backup & Restore') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
                        <div>
                             <h3 class="text-lg font-medium">Cadangan Database</h3>
                             <p class="text-sm text-gray-500 dark:text-gray-400">Buat cadangan database secara berkala untuk keamanan data.</p>
                        </div>
                        <form action="{{ route('admin.backup.manualbackup') }}" method="POST">
                            @csrf
                            <x-primary-button type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Buat Backup Baru
                            </x-primary-button>
                        </form>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                     @if (session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div>
                    @endif

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama File</th>
                                    <th scope="col" class="px-6 py-3">Ukuran</th>
                                    <th scope="col" class="px-6 py-3">Tanggal Dibuat</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($backups as $backup)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="px-6 py-4 font-mono text-gray-900 dark:text-white">{{ $backup['name'] }}</td>
                                    <td class="px-6 py-4">{{ $backup['size'] }} KB</td>
                                    <td class="px-6 py-4">{{ $backup['date'] }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-4">
                                            <a href="{{ route('admin.backup.download', $backup['name']) }}" class="font-medium text-sky-600 dark:text-sky-500 hover:underline">Unduh</a>
                                            <form action="{{ route('admin.backup.delete', $backup['name']) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus file backup ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="px-6 py-4 text-center">Belum ada file backup.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
