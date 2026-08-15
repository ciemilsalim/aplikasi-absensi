<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Laporan Mengajar', 'url' => route('teacher.subject.attendance.report')],
                    ['title' => 'Preview Matriks', 'url' => '#']
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Preview Rekap Presensi Mata Pelajaran
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.subject.attendance.report') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors">
                    <span class="material-icons text-sm text-slate-500">tune</span>
                    <span>Ubah Filter</span>
                </a>
                
                <a href="{{ route('teacher.subject.attendance.print', $requestInputs) }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all">
                    <span class="material-icons text-sm">picture_as_pdf</span>
                    <span>Cetak PDF</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{
        showModal: false,
        studentId: '',
        studentName: '',
        date: '',
        currentStatus: '',
        openModal(studentId, studentName, date, status) {
            this.studentId = studentId;
            this.studentName = studentName;
            this.date = date;
            this.currentStatus = status || 'hapus';
            this.showModal = true;
        }
    }">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            
            <!-- Summary Banner -->
            <div class="p-6 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                        <span class="material-icons">menu_book</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ $subjectInfo->name }} <span class="text-slate-400 font-normal">|</span> Kelas {{ $classInfo->name }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Periode: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $startDate->isoFormat('D MMMM YYYY') }} - {{ $endDate->isoFormat('D MMMM YYYY') }}</span>
                        </p>
                    </div>
                </div>

                <!-- Status Legend -->
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">H: Hadir</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">S: Sakit</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300">I: Izin</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">A: Alpa</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300">B: Bolos</span>
                </div>
            </div>

            <!-- Attendance Matrix Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300 border-collapse">
                    <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-850/30 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="sticky left-0 bg-white dark:bg-slate-900 px-6 py-3.5 z-20 shadow-sm min-w-[200px]">
                                Nama Siswa
                            </th>
                            @if(isset($period))
                                @foreach ($period as $date)
                                    <th scope="col" class="px-2.5 py-3 text-center min-w-[42px]">
                                        <div class="text-[11px] font-bold">{{ $date->format('d') }}</div>
                                        <div class="text-[9px] text-slate-400 font-normal">{{ $date->format('M') }}</div>
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($students as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-6 py-3.5 font-bold text-slate-900 dark:text-white z-10 shadow-sm">
                                    <div class="flex items-center gap-2.5 truncate">
                                        <div class="w-6 h-6 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 text-[10px] font-bold flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <span class="truncate">{{ $student->name }}</span>
                                    </div>
                                </td>
                                @if(isset($period))
                                    @foreach ($period as $date)
                                        @php
                                            $dateString = $date->format('Y-m-d');
                                            $status = $attendanceData[$student->id][$dateString] ?? null;
                                            
                                            $badgeClass = 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700';
                                            $statusText = '-';

                                            switch ($status) {
                                                case 'hadir': 
                                                    $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 ring-1 ring-emerald-500/20'; 
                                                    $statusText = 'H'; 
                                                    break;
                                                case 'sakit': 
                                                    $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 ring-1 ring-amber-500/20'; 
                                                    $statusText = 'S'; 
                                                    break;
                                                case 'izin': 
                                                    $badgeClass = 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 ring-1 ring-purple-500/20'; 
                                                    $statusText = 'I'; 
                                                    break;
                                                case 'alpa': 
                                                    $badgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 ring-1 ring-rose-500/20'; 
                                                    $statusText = 'A'; 
                                                    break;
                                                case 'bolos': 
                                                    $badgeClass = 'bg-orange-100 text-orange-800 dark:bg-orange-950/70 dark:text-orange-300 ring-1 ring-orange-500/20'; 
                                                    $statusText = 'B'; 
                                                    break;
                                            }
                                        @endphp
                                        <td class="px-1.5 py-2 text-center">
                                            <button 
                                                @click="openModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ $dateString }}', '{{ $status }}')"
                                                class="w-7 h-7 mx-auto inline-flex items-center justify-center font-bold text-[11px] rounded-lg transition-transform transform hover:scale-115 active:scale-95 cursor-pointer {{ $badgeClass }}"
                                                title="Klik untuk ubah status presensi"
                                            >
                                                {{ $statusText }}
                                            </button>
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($period) ? iterator_count($period) + 1 : 1 }}" class="px-6 py-8 text-center text-xs text-slate-400 italic">
                                    Tidak ada data siswa di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal untuk Edit Kehadiran -->
        <div x-show="showModal" style="display: none;" @keydown.escape.window="showModal = false" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <!-- Backdrop -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-200" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-150" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                     @click="showModal = false"></div>

                <!-- Modal Dialog -->
                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-200" 
                     x-transition:enter-start="opacity-0 scale-95" 
                     x-transition:enter-end="opacity-100 scale-100" 
                     x-transition:leave="ease-in duration-150" 
                     x-transition:leave-start="opacity-100 scale-100" 
                     x-transition:leave-end="opacity-0 scale-95" 
                     class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md p-6 z-10">
                    
                    <form action="{{ route('teacher.subject.attendance.update_report') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_id" :value="studentId">
                        <input type="hidden" name="date" :value="date">
                        <input type="hidden" name="school_class_id" value="{{ $classInfo->id }}">
                        <input type="hidden" name="subject_id" value="{{ $subjectInfo->id }}">
                        
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-sky-500 text-lg">edit_calendar</span>
                                Ubah Status Kehadiran
                            </h3>
                            <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                                <span class="material-icons text-lg">close</span>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 text-xs space-y-1">
                                <div class="text-slate-500 dark:text-slate-400">Siswa: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="studentName"></span></div>
                                <div class="text-slate-500 dark:text-slate-400">Tanggal: <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></span></div>
                            </div>

                            <div>
                                <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Pilih Status Baru <span class="text-rose-500">*</span>
                                </label>
                                <select id="status" name="status" 
                                        class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                                    <option value="hadir" :selected="currentStatus === 'hadir'">Hadir (H)</option>
                                    <option value="sakit" :selected="currentStatus === 'sakit'">Sakit (S)</option>
                                    <option value="izin" :selected="currentStatus === 'izin'">Izin (I)</option>
                                    <option value="alpa" :selected="currentStatus === 'alpa'">Alpa (A)</option>
                                    <option value="bolos" :selected="currentStatus === 'bolos'">Bolos (B)</option>
                                    <option value="hapus" class="text-rose-600 font-bold">-- Kosongkan / Hapus Record --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                            <button @click="showModal = false" type="button" 
                                    class="px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


