@php
// Helper function untuk membuat link sortir
function sortable_link($title, $column, $sortBy, $sortDirection) {
    $direction = ($sortBy === $column && $sortDirection === 'asc') ? 'desc' : 'asc';
    $arrow = '';
    if ($sortBy === $column) {
        $arrow = $sortDirection === 'asc' ? '&#9650;' : '&#9660;'; // Panah atas atau bawah
    }
    // Menambahkan parameter filter yang sudah ada
    $queryParams = array_merge(request()->query(), ['sort_by' => $column, 'sort_direction' => $direction]);
    return '<a href="' . route('admin.users.index', $queryParams) . '" class="flex items-center gap-2">' . $title . ' <span class="text-sky-500">' . $arrow . '</span></a>';
}
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Manajemen Pengguna', 'url' => route('admin.users.index')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Pengguna') }}
        </h2>
    </x-slot>

    <div x-data="{ 
        showConfirmModal: false, 
        deleteUrl: '',
        selectedUsers: [],
        toggleAll(event) {
            const checkboxes = document.querySelectorAll('input[name=\'user_ids[]\']');
            this.selectedUsers = event.target.checked ? Array.from(checkboxes).map(cb => cb.value) : [];
        }
    }" class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Kartu Statistik bisa ditambahkan kembali di sini jika perlu --}}

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Semua Pengguna</h3>
                        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Tambah Pengguna Baru
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                     @if (session('error') || session('warning'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') ?? session('warning') }}</p></div>
                    @endif
                    
                    <!-- Form Pencarian & Filter -->
                    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            {{-- Menyimpan parameter sortir saat melakukan filter --}}
                            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                            <input type="hidden" name="sort_direction" value="{{ request('sort_direction') }}">

                            <div class="relative flex-grow">
                                <x-text-input type="text" name="search" placeholder="Cari berdasarkan nama atau email..." value="{{ request('search') }}" class="w-full pl-10"/>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <select name="role" class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Peran</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                                    <option value="teacher" {{ request('role') == 'teacher' ? 'selected' : '' }}>Guru</option>
                                    <option value="parent" {{ request('role') == 'parent' ? 'selected' : '' }}>Orang Tua</option>
                                </select>
                                
                                <div class="flex items-center gap-2">
                                    <label for="per_page" class="text-sm text-gray-500 dark:text-gray-400 flex-shrink-0">Tampilkan:</label>
                                    <select name="per_page" id="per_page" onchange="this.form.submit()" class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>

                                <x-primary-button type="submit">Filter</x-primary-button>
                            </div>
                        </div>
                    </form>

                    <!-- Tindakan Massal -->
                    <div x-show="selectedUsers.length > 0" class="mb-4 bg-slate-100 dark:bg-slate-700 p-4 rounded-lg flex items-center gap-4" style="display: none;">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><span x-text="selectedUsers.length"></span> pengguna dipilih</span>
                        <form action="{{ route('admin.users.bulk_destroy') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus semua pengguna yang dipilih?');">
                            @csrf
                            <template x-for="userId in selectedUsers" :key="userId">
                                <input type="hidden" name="user_ids[]" :value="userId">
                            </template>
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Hapus yang Dipilih</button>
                        </form>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="p-4"><input type="checkbox" @click="toggleAll($event)" class="rounded border-gray-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500"></th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Nama', 'name', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Email', 'email', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Peran', 'role', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Terakhir Dilihat', 'last_seen_at', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="p-4"><input type="checkbox" name="user_ids[]" x-model="selectedUsers" value="{{ $user->id }}" class="rounded border-gray-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500"></td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $user->name }}</td>
                                    <td class="px-6 py-4">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($user->role == 'admin') bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300
                                            @elseif($user->role == 'operator') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300
                                            @elseif($user->role == 'teacher') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300
                                            @else bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 @endif">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $isOnline = $user->last_seen_at && \Carbon\Carbon::parse($user->last_seen_at)->gt(now()->subMinutes(5));
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $isOnline ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $isOnline ? 'Online' : 'Offline' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        @if($user->last_seen_at)
                                            {{ \Carbon\Carbon::parse($user->last_seen_at)->diffForHumans() }}
                                        @else
                                            Belum pernah
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-4">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                                            <button type="button" 
                                                    @click="showConfirmModal = true; deleteUrl = '{{ route('admin.users.destroy', $user) }}'"
                                                    class="font-medium text-red-600 dark:text-red-500 hover:underline">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="px-6 py-4 text-center">Tidak ada data pengguna yang cocok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $users->appends(request()->query())->links() }}</div>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Hapus tidak diubah --}}
        <div x-show="showConfirmModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" style="display: none;">
            <div @click.away="showConfirmModal = false" x-show="showConfirmModal" x-transition class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-medium text-gray-900 dark:text-white">Hapus Pengguna?</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus akun ini? Tindakan ini tidak dapat diurungkan.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-center gap-4">
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">Ya, Hapus</x-danger-button>
                    </form>
                    <x-secondary-button @click="showConfirmModal = false">Batal</x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
