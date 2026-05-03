<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                {{ __('Form Absensi') }}: {{ $extracurricular->name }}
            </h2>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-lg border border-blue-200">{{ $activeYear->name }}</span>
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200">{{ $activeSemester->name }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('teacher.extracurricular-attendance.store', $extracurricular) }}" method="POST">
                @csrf
                
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-3xl border border-gray-100 dark:border-slate-700">
                    <div class="p-8 border-b border-gray-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-700/50 flex flex-wrap justify-between items-center gap-4">
                        <div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-white">Daftar Kehadiran Siswa</h3>
                            <p class="text-sm text-slate-500">Silakan pilih status kehadiran untuk setiap siswa.</p>
                        </div>
                        <div class="w-full sm:w-auto">
                            <x-input-label for="attendance_date" value="Tanggal Kegiatan" />
                            <x-text-input id="attendance_date" name="attendance_date" type="date" class="mt-1 block w-full rounded-xl" :value="$today" required />
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse($extracurricular->students as $student)
                            @php
                                $existing = $existingAttendances->get($student->id);
                            @endphp
                            <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-all">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="flex-shrink-0">
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" class="w-12 h-12 rounded-2xl object-cover shadow-sm">
                                        @else
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-600 flex items-center justify-center font-bold text-slate-400">
                                                {{ substr($student->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-800 dark:text-white truncate text-lg">{{ $student->name }}</p>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ $student->schoolClass->name ?? 'Tanpa Kelas' }} • {{ $student->nis }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach(['hadir', 'sakit', 'izin', 'alpa'] as $status)
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="{{ $status }}" class="hidden peer" {{ ($existing && $existing->status == $status) || (!$existing && $status == 'hadir') ? 'checked' : '' }} required>
                                            <div class="px-4 py-2 rounded-xl text-sm font-bold border transition-all 
                                                {{ $status == 'hadir' ? 'border-emerald-100 text-emerald-500 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500' : '' }}
                                                {{ $status == 'sakit' ? 'border-amber-100 text-amber-500 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500' : '' }}
                                                {{ $status == 'izin' ? 'border-blue-100 text-blue-500 peer-checked:bg-blue-500 peer-checked:text-white peer-checked:border-blue-500' : '' }}
                                                {{ $status == 'alpa' ? 'border-rose-100 text-rose-500 peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-500' : '' }}
                                                bg-white dark:bg-slate-900 group-hover:scale-105">
                                                {{ ucfirst($status) }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                
                                <div class="w-full md:w-48">
                                    <input type="text" name="attendances[{{ $student->id }}][notes]" class="w-full text-xs rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:ring-blue-500" placeholder="Catatan (opsional)..." value="{{ $existing->notes ?? '' }}">
                                </div>
                            </div>
                        @empty
                            <div class="p-20 text-center text-slate-500 italic">
                                Belum ada siswa yang terdaftar di ekstrakurikuler ini.
                            </div>
                        @endforelse
                    </div>

                    <div class="p-8 bg-slate-50 dark:bg-slate-700/50 flex justify-end gap-4 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('teacher.extracurricular-attendance.index') }}" class="px-6 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 transition-colors">Batal</a>
                        <x-primary-button class="px-10 py-4 rounded-2xl shadow-xl shadow-blue-100 dark:shadow-none bg-blue-600">
                            Simpan Seluruh Absensi
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
