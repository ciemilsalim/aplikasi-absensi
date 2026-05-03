<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Anggota Ekstrakurikuler') }}: {{ $extracurricular->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @if (session('success'))
                <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl relative flex items-center gap-3 shadow-sm" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Tambah Siswa Baru (Berdasarkan Kelas) -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-slate-700 p-8 h-fit">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6">Tambah Anggota Baru</h3>
                    
                    <form action="{{ route('admin.extracurriculars.assign_students', $extracurricular) }}" method="POST" class="space-y-6">
                        @csrf
                        <div x-data="{ selectedClass: '' }">
                            <x-input-label for="class_select" value="Pilih Kelas" />
                            <select id="class_select" x-model="selectedClass" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                <option value="">Pilih Kelas...</option>
                                @foreach($classes as $class)
                                    <option value="class-{{ $class->id }}">{{ $class->name }} ({{ $class->students->count() }} Siswa)</option>
                                @endforeach
                            </select>

                            <div class="mt-6 space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($classes as $class)
                                    <div x-show="selectedClass === 'class-{{ $class->id }}'" class="space-y-2">
                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Daftar Siswa {{ $class->name }}</p>
                                        @forelse($class->students->whereNotIn('id', $extracurricular->students->pluck('id')) as $student)
                                            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-50 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer transition-all">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="rounded-md border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-gray-700 dark:text-slate-300 truncate">{{ $student->name }}</p>
                                                    <p class="text-[10px] text-slate-400 font-mono">{{ $student->nis }}</p>
                                                </div>
                                            </label>
                                        @empty
                                            <p class="text-sm text-slate-500 italic p-4 text-center bg-slate-50 dark:bg-slate-700/50 rounded-xl">Semua siswa di kelas ini sudah terdaftar.</p>
                                        @endforelse
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <x-primary-button class="w-full justify-center py-4 rounded-xl shadow-lg shadow-blue-100 dark:shadow-none">
                            Tambahkan Siswa Terpilih
                        </x-primary-button>
                    </form>
                </div>

                <!-- Daftar Anggota Saat Ini -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-slate-700">
                    <div class="p-8">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Daftar Anggota</h3>
                                <p class="text-sm text-slate-500 mt-1">Total: {{ $extracurricular->students->count() }} Siswa terdaftar.</p>
                            </div>
                        </div>

                        <div class="relative overflow-x-auto border border-gray-100 dark:border-slate-700 rounded-2xl">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-slate-400">
                                <thead class="text-xs text-gray-700 dark:text-slate-300 uppercase bg-slate-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-4">Siswa</th>
                                        <th scope="col" class="px-6 py-4">Kelas</th>
                                        <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                    @forelse ($extracurricular->students as $student)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    @if($student->photo)
                                                        <img src="{{ asset('storage/' . $student->photo) }}" class="w-10 h-10 rounded-full object-cover shadow-sm">
                                                    @else
                                                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-600 flex items-center justify-center font-bold text-slate-400 text-sm">
                                                            {{ substr($student->name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <p class="font-bold text-gray-800 dark:text-white leading-tight">{{ $student->name }}</p>
                                                        <p class="text-xs font-mono text-slate-400 mt-0.5">{{ $student->nis }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                                    {{ $student->schoolClass->name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <form action="{{ route('admin.extracurriculars.remove_student', [$extracurricular, $student]) }}" method="POST" onsubmit="return confirm('Hapus siswa ini dari ekstrakurikuler?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors group">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-6 py-12 text-center text-slate-500 italic bg-slate-50 dark:bg-slate-700/10">
                                                Belum ada siswa yang terdaftar di ekstrakurikuler ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</x-app-layout>
