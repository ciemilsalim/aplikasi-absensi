<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Manajemen Tahun Ajaran & Semester') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('success'))
                <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl relative mb-4 flex items-center gap-3 shadow-sm" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-100 border border-rose-400 text-rose-700 px-4 py-3 rounded-xl relative mb-4 flex items-center gap-3 shadow-sm" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Form Tambah Tahun Ajaran -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl p-6 border border-gray-100 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Tambah Tahun Ajaran</h3>
                    <form action="{{ route('admin.academic-periods.year.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="year_name" value="Nama Tahun Ajaran" />
                            <x-text-input id="year_name" name="name" type="text" class="mt-1 block w-full" placeholder="Contoh: 2024/2025" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button class="w-full justify-center py-3 rounded-xl shadow-lg shadow-blue-100 dark:shadow-none">
                            Simpan Tahun Ajaran
                        </x-primary-button>
                    </form>
                </div>

                <!-- Daftar Tahun Ajaran & Semester -->
                <div class="md:col-span-2 space-y-6">
                    @forelse ($academicYears as $year)
                        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-slate-700">
                            <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-700/50">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">{{ $year->name }}</h3>
                                    @if($year->is_active)
                                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full uppercase tracking-wider border border-emerald-200">Aktif</span>
                                    @else
                                        <form action="{{ route('admin.academic-periods.year.activate', $year->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-slate-500 hover:text-blue-600 uppercase tracking-wider transition-colors">Aktifkan</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('admin.academic-periods.year.destroy', $year->id) }}" method="POST" onsubmit="return confirm('Hapus Tahun Ajaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach ($year->semesters as $semester)
                                        <div class="p-4 rounded-xl border {{ $semester->is_active ? 'border-blue-200 bg-blue-50/30 dark:bg-blue-900/10' : 'border-gray-100 dark:border-slate-700' }} flex justify-between items-center transition-all">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ $semester->name }}</p>
                                                @if($semester->is_active)
                                                    <span class="text-[10px] font-black text-blue-600 uppercase">Sedang Digunakan</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if(!$semester->is_active)
                                                    <form action="{{ route('admin.academic-periods.semester.activate', $semester->id) }}" method="POST">
                                                        @csrf
                                                        <button class="text-xs font-bold text-blue-500 hover:underline">Aktifkan</button>
                                                    </form>
                                                    <form action="{{ route('admin.academic-periods.semester.destroy', $semester->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="text-rose-400 hover:text-rose-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <!-- Form Tambah Semester -->
                                    <form action="{{ route('admin.academic-periods.semester.store') }}" method="POST" class="p-4 rounded-xl border border-dashed border-gray-300 dark:border-slate-600 flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="academic_year_id" value="{{ $year->id }}">
                                        <input type="text" name="name" class="bg-transparent border-none focus:ring-0 text-sm w-full dark:text-white" placeholder="Tambah Semester..." required>
                                        <button type="submit" class="p-1 bg-slate-100 dark:bg-slate-700 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-blue-600 hover:text-white transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl p-12 text-center border border-dashed border-gray-300 dark:border-slate-700">
                            <div class="inline-flex p-4 bg-slate-50 dark:bg-slate-700 rounded-full mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Belum Ada Data</h3>
                            <p class="text-slate-500">Silakan tambahkan Tahun Ajaran pertama Anda di sebelah kiri.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
