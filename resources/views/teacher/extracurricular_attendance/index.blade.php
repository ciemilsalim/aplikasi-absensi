<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Absensi Ekstrakurikuler') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-slate-700">
                <div class="p-8 text-gray-900 dark:text-white">
                    <div class="mb-10">
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Pilih Ekstrakurikuler</h3>
                        <p class="text-sm text-slate-500 mt-1">Anda terdaftar sebagai pembina untuk kegiatan di bawah ini.</p>
                    </div>

                    @if (session('success'))
                        <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3 shadow-sm" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-rose-100 border border-rose-400 text-rose-700 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-3 shadow-sm" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @forelse ($extracurriculars as $ekskul)
                            <div class="group relative bg-slate-50 dark:bg-slate-700/50 rounded-3xl p-8 border border-gray-100 dark:border-slate-600 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                                <div class="absolute top-0 right-0 p-6">
                                    <div class="w-12 h-12 bg-white dark:bg-slate-600 rounded-2xl flex items-center justify-center shadow-sm text-blue-600 dark:text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                        </svg>
                                    </div>
                                </div>
                                <h4 class="text-2xl font-black text-gray-800 dark:text-white pr-12">{{ $ekskul->name }}</h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 line-clamp-2">{{ $ekskul->description ?? 'Kelola absensi siswa untuk kegiatan ini.' }}</p>
                                
                                <div class="mt-8 flex items-center gap-4">
                                    <div class="p-4 bg-white dark:bg-slate-600 rounded-2xl border border-gray-100 dark:border-slate-500 flex-1">
                                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Total Siswa</p>
                                        <p class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $ekskul->students->count() }}</p>
                                    </div>
                                    <div class="flex-1">
                                        <form action="{{ route('teacher.extracurricular-attendance.report', $ekskul) }}" method="GET" target="_blank">
                                            <button type="submit" class="flex flex-col items-center justify-center w-full p-4 bg-white dark:bg-slate-600 rounded-2xl border border-gray-100 dark:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-500 transition-all group">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-slate-400 group-hover:text-blue-500 mb-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.844 2.4 12c1.914-1.12 4.145-1.76 6.523-1.76 1.157 0 2.27.153 3.327.437M19.8 19.8l-4.184-4.183a1.14 1.14 0 0 1-.778-.332 48.294 48.294 0 0 0-5.83-.498c-1.585-.233-2.708-1.626-2.708-3.228V6.741c0-1.602 1.123-2.995 2.707-3.228A48.397 48.397 0 0 0 12 3c2.392 0 4.744.175 7.043.513C20.627 3.746 21.75 5.14 21.75 6.741v6.018a3.228 3.228 0 0 1-2.707 3.228 48.3 48.3 0 0 0-4.184 1.14" />
                                                </svg>
                                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Cetak Rekap</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <a href="{{ route('teacher.extracurricular-attendance.create', $ekskul) }}" class="mt-6 flex items-center justify-center gap-3 w-full py-4 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 dark:shadow-none">
                                    Mulai Absensi
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        @empty
                            <div class="col-span-full py-20 text-center bg-slate-50 dark:bg-slate-700/30 rounded-[3rem] border border-dashed border-gray-300 dark:border-slate-600">
                                <div class="inline-flex p-6 bg-white dark:bg-slate-800 rounded-full shadow-sm mb-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12 text-slate-300">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Anda Belum Menjadi Pembina</h3>
                                <p class="text-slate-500 mt-2">Silakan hubungi Admin untuk penugasan kegiatan ekstrakurikuler.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
