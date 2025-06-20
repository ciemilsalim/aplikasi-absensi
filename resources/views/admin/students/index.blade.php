<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Data Siswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-slate-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap gap-2 justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Siswa</h3>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.students.import.form') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">Impor dari Excel</a>
                            <a href="{{ route('admin.students.create') }}" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">Tambah Siswa</a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 table-auto">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                    <th scope="col" class="px-6 py-3">NIS</th>
                                    <th scope="col" class="px-6 py-3">Kelas</th>
                                    <th scope="col" class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-normal text-gray-900 dark:text-gray-400 dark:bg-slate-800">
                                @forelse ($students as $student)
                                    <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-gray-400 whitespace-nowrap">{{ $student->name }}</th>
                                        <td class="px-6 py-4">{{ $student->nis }}</td>
                                        <td class="px-6 py-4">{{ $student->schoolClass->name ?? '-'}}</td>
                                        <td class="px-6 py-4 flex items-center space-x-3">
                                            <a href="{{ route('admin.students.edit', $student) }}" class="font-medium text-blue-600 hover:underline">Edit</a>
                                            <form action="{{ route('admin.students.destroy', $student) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 hover:underline">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="border-b">
                                        <td colspan="3" class="px-6 py-4 text-center">Tidak ada data siswa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                     <div class="mt-4">{{ $students->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
