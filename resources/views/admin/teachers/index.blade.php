<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Guru', 'url' => route('admin.teachers.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Data Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Akun Guru</h3>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.teachers.import.form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                                Impor dari Excel
                            </a>
                            <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Tambah Guru
                            </a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Guru</th>
                                    <th scope="col" class="px-6 py-3">NIP</th>
                                    <th scope="col" class="px-6 py-3">Email Login</th>
                                    <th scope="col" class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($teachers as $teacher)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center">
                                            {{-- Indikator Status Online BARU --}}
                                            <span id="status-dot-{{ $teacher->user_id }}" class="h-2.5 w-2.5 rounded-full bg-gray-400 mr-3 transition-colors duration-500" title="Offline"></span>
                                            {{ $teacher->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ $teacher->nip ?? '-' }}</td>
                                    <td class="px-6 py-4">{{ $teacher->user->email }}</td>
                                    <td class="px-6 py-4 flex items-center space-x-3">
                                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                                        <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun guru ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="px-6 py-4 text-center">Belum ada data guru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $teachers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
      @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function fetchOnlineStatus() {
                fetch('{{ route('admin.teachers.online_status') }}')
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
                    .catch(error => console.error('Gagal mengambil status online guru:', error));
            }

            fetchOnlineStatus();
            setInterval(fetchOnlineStatus, 30000); 
        });
    </script>
    @endpush
</x-app-layout>