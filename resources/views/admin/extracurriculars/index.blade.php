<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Manajemen Ekstrakurikuler') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-slate-700">
                <div class="p-6 text-gray-900 dark:text-white">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Daftar Ekstrakurikuler</h3>
                            <p class="text-sm text-slate-500 mt-1">Kelola data kegiatan ekstrakurikuler sekolah.</p>
                        </div>
                        <a href="{{ route('admin.extracurriculars.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 dark:shadow-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Tambah Ekstrakurikuler
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3 shadow-sm" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse ($extracurriculars as $ekskul)
                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-6 border border-gray-100 dark:border-slate-600 hover:shadow-lg transition-all group">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl text-blue-600 dark:text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-4.625 3.352 4.67 4.67 0 0 1 .575-1.373 3.75 3.75 0 0 0-3.326 5.432A3.75 3.75 0 0 0 12 18Z" />
                                        </svg>
                                    </div>
                                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('admin.extracurriculars.edit', $ekskul) }}" class="p-2 text-blue-500 hover:bg-white dark:hover:bg-slate-600 rounded-lg shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.extracurriculars.destroy', $ekskul) }}" method="POST" onsubmit="return confirm('Hapus Ekskul ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="p-2 text-rose-500 hover:bg-white dark:hover:bg-slate-600 rounded-lg shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <h4 class="text-lg font-bold text-gray-800 dark:text-white">{{ $ekskul->name }}</h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">{{ $ekskul->description ?? 'Tidak ada deskripsi.' }}</p>
                                
                                <div class="mt-6 flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        @if($ekskul->coach && $ekskul->coach->photo)
                                            <img src="{{ asset('storage/' . $ekskul->coach->photo) }}" class="w-8 h-8 rounded-full object-cover border border-white dark:border-slate-600" alt="">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-xs font-bold text-slate-500">
                                                {{ substr($ekskul->coach->name ?? '?', 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] uppercase font-black text-slate-400 leading-none">Pembina</p>
                                        <p class="text-xs font-bold text-gray-700 dark:text-slate-300 truncate">{{ $ekskul->coach->name ?? 'Belum Ditentukan' }}</p>
                                    </div>
                                </div>

                                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-600 flex justify-between items-center">
                                    <div class="flex -space-x-2">
                                        @foreach($ekskul->students->take(4) as $student)
                                            @if($student->photo)
                                                <img src="{{ asset('storage/' . $student->photo) }}" class="w-7 h-7 rounded-full border-2 border-white dark:border-slate-700 object-cover" title="{{ $student->name }}">
                                            @else
                                                <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-600 border-2 border-white dark:border-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-400" title="{{ $student->name }}">
                                                    {{ substr($student->name, 0, 1) }}
                                                </div>
                                            @endif
                                        @endforeach
                                        @if($ekskul->students->count() > 4)
                                            <div class="w-7 h-7 rounded-full bg-blue-600 border-2 border-white dark:border-slate-700 flex items-center justify-center text-[10px] font-bold text-white">
                                                +{{ $ekskul->students->count() - 4 }}
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.extracurriculars.students', $ekskul) }}" class="text-xs font-bold text-blue-600 hover:underline">Kelola Siswa</a>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-12 text-center bg-slate-50 dark:bg-slate-700/30 rounded-3xl border border-dashed border-gray-300 dark:border-slate-600">
                                <p class="text-slate-500">Belum ada data ekstrakurikuler.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
