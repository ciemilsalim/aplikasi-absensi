<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Kehadiran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Form Filter & Pencarian -->
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium">Rekap Kehadiran</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Menampilkan data untuk tanggal: {{ $selectedDate->translatedFormat('l, d F Y') }}</p>
                        </div>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                            {{-- Dropdown Filter Kelas BARU --}}
                            <select name="school_class_id" class="w-full sm:w-auto border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                <option value="">Semua Kelas</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-text-input type="text" name="search" placeholder="Cari nama siswa..." value="{{ request('search') }}" class="w-48"/>
                            <x-text-input type="date" name="tanggal" id="tanggal" value="{{ $selectedDate->format('Y-m-d') }}" />
                            <x-primary-button type="submit">Cari</x-primary-button>
                        </form>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                    <th scope="col" class="px-6 py-3">Kelas</th> <!-- KOLOM BARU -->
                                    <th scope="col" class="px-6 py-3">Jam Masuk</th>
                                    <th scope="col" class="px-6 py-3">Jam Pulang</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $attendance->student->name ?? 'Siswa Dihapus' }}
                                        </th>
                                        <td class="px-6 py-4">
                                            {{-- Menampilkan nama kelas siswa --}}
                                            {{ $attendance->student->schoolClass->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                                {{ $attendance->attendance_time->format('H:i:s') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance->checkout_time)
                                                <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                                    {{ $attendance->checkout_time->format('H:i:s') }}
                                                </span>
                                            @else
                                                <span class="bg-gray-100 text-gray-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-slate-700 dark:text-gray-300">
                                                    -
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance->status === 'tepat_waktu')
                                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Tepat Waktu</span>
                                            @elseif ($attendance->status === 'terlambat')
                                                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">Terlambat</span>
                                            @else
                                                <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-700 dark:text-gray-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                        <td colspan="5" class="px-6 py-4 text-center">
                                            Tidak ada data kehadiran untuk tanggal dan pencarian yang dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $attendances->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
