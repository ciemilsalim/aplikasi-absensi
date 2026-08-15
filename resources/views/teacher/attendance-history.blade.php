<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('teacher.dashboard')],
                    ['title' => 'Riwayat Kehadiran', 'url' => route('teacher.attendance.history')]
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Riwayat & Rekap Kehadiran
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        Kelas {{ $class->name }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span class="material-icons text-xs text-sky-500">calendar_month</span>
                    <span>Periode: {{ $selectedDate->isoFormat('MMMM Y') }}</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div 
        x-data="{
            showModal: false,
            attendanceId: '',
            studentId: '',
            studentName: '',
            date: '',
            currentStatus: '',
            openModal(attendanceId, studentId, studentName, date, status) {
                this.attendanceId = attendanceId;
                this.studentId = studentId;
                this.studentName = studentName;
                this.date = date;
                this.currentStatus = status || 'hapus';
                this.showModal = true;
            }
        }"
        class="space-y-6"
    >
        <!-- Filter & Action Controls Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-5">
            <div class="flex flex-col lg:flex-row gap-4 justify-between items-stretch lg:items-center">
                
                <!-- Main Month Filter & Export Buttons -->
                <form method="GET" action="{{ route('teacher.attendance.history') }}" class="flex flex-wrap items-center gap-2.5">
                    <div>
                        <input type="month" name="month" id="month" value="{{ $selectedDate->format('Y-m') }}" 
                               class="text-xs font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-2.5 focus:ring-4 focus:ring-sky-500/15 focus:border-sky-500">
                    </div>
                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all active:scale-95">
                        <span class="material-icons text-sm">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('teacher.attendance.print', ['month' => $selectedDate->format('Y-m')]) }}" target="_blank" 
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors">
                        <span class="material-icons text-sm text-slate-500">print</span>
                        <span>Cetak Bulanan</span>
                    </a>
                    <a href="{{ route('teacher.attendance.export.excel', ['month' => $selectedDate->format('Y-m')]) }}" 
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900/40 font-bold text-xs transition-colors">
                        <span class="material-icons text-sm text-emerald-600">table_view</span>
                        <span>Excel</span>
                    </a>
                </form>
                
                <!-- Trimester Print -->
                <form method="GET" action="{{ route('teacher.attendance.print_trimester') }}" class="flex flex-wrap items-center gap-2 pt-3 lg:pt-0 lg:border-l lg:pl-5 border-slate-200 dark:border-slate-800" target="_blank">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">TW:</span>
                        <select name="trimester" id="trimester" 
                                class="text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-2 focus:ring-sky-500">
                            <option value="1">TW 1 (Jan-Mar)</option>
                            <option value="2">TW 2 (Apr-Jun)</option>
                            <option value="3">TW 3 (Jul-Sep)</option>
                            <option value="4">TW 4 (Okt-Des)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <input type="number" name="year" id="year" value="{{ date('Y') }}" 
                               class="w-18 text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-2 focus:ring-sky-500" required>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <select name="paper_size" id="paper_size" 
                                class="text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 p-2 focus:ring-sky-500"
                                title="Pilihan Ukuran Kertas Cetak">
                            <option value="a4">A4</option>
                            <option value="folio">Folio / F4</option>
                        </select>
                    </div>
                    <button type="submit" 
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-sm shadow-indigo-600/20 transition-all active:scale-95"
                            title="Cetak Rekap Kehadiran Triwulan (PDF Landscape)">
                        <span class="material-icons text-sm">print</span>
                        <span>Cetak TW</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Attendance Matrix Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sky-500 text-lg">calendar_view_month</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Matriks Kehadiran Harian Siswa</h3>
                </div>
                <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500">
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> H (Hadir)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> T (Terlambat)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> S (Sakit)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-sky-500"></span> I (Izin)</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> A (Alpa)</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border-collapse">
                    <thead class="bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th scope="col" class="sticky left-0 bg-slate-50 dark:bg-slate-850 px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300 z-10 w-52 min-w-52 max-w-52 shadow-[1px_0_0_0_rgba(226,232,240,1)] dark:shadow-[1px_0_0_0_rgba(51,65,85,1)]">
                                Nama Siswa
                            </th>
                            
                            @foreach ($period as $date)
                                <th scope="col" class="px-2.5 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 {{ $date->isSunday() ? 'bg-rose-50/50 dark:bg-rose-950/20 text-rose-500' : '' }}">
                                    {{ $date->format('d/m') }}
                                </th>
                            @endforeach

                            {{-- Kolom Rekapitulasi --}}
                            <th scope="col" class="bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-emerald-800 dark:text-emerald-200 uppercase" title="Hadir">H</th>
                            <th scope="col" class="bg-purple-50 dark:bg-purple-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-purple-800 dark:text-purple-200 uppercase" title="Sakit">S</th>
                            <th scope="col" class="bg-sky-50 dark:bg-sky-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-sky-800 dark:text-sky-200 uppercase" title="Izin">I</th>
                            <th scope="col" class="bg-rose-50 dark:bg-rose-950/60 px-2.5 py-3 text-center text-[10px] font-extrabold text-rose-800 dark:text-rose-200 uppercase" title="Alpa">A</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($students as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-5 py-3 font-bold text-slate-900 dark:text-slate-100 z-10 truncate w-52 min-w-52 max-w-52 shadow-[1px_0_0_0_rgba(226,232,240,1)] dark:shadow-[1px_0_0_0_rgba(51,65,85,1)]">
                                    {{ $student->name }}
                                </td>

                                @foreach ($period as $date)
                                    @php
                                        $dateString = $date->format('Y-m-d');
                                        $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                                        $attendanceId = $attendanceRecord ? $attendanceRecord->id : '';
                                        $badgeColor = 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500';
                                        $statusText = '-';
                                        
                                        $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($date, $selfStudyDays);

                                        if ($isSelfStudy) {
                                            $badgeColor = 'bg-sky-500 text-white shadow-2xs font-bold';
                                            $statusText = 'BM';
                                        } else {
                                            switch ($status) {
                                                case 'tepat_waktu': $badgeColor = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold'; $statusText = 'H'; break;
                                                case 'terlambat': $badgeColor = 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold'; $statusText = 'T'; break;
                                                case 'izin': $badgeColor = 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 font-bold'; $statusText = 'I'; break;
                                                case 'sakit': $badgeColor = 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold'; $statusText = 'S'; break;
                                                case 'alpa': $badgeColor = 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-bold'; $statusText = 'A'; break;
                                            }
                                        }
                                    @endphp
                                    <td class="px-2 py-2.5 text-center whitespace-nowrap {{ $date->isSunday() ? 'bg-rose-50/25 dark:bg-rose-950/10' : '' }}">
                                        <button 
                                            @click="openModal('{{ $attendanceId }}', '{{ $student->id }}', '{{ $student->name }}', '{{ $dateString }}', '{{ $status }}')"
                                            class="w-7 h-7 inline-flex items-center justify-center text-[11px] rounded-lg transition-transform hover:scale-110 active:scale-95 {{ $badgeColor }}"
                                            title="Klik untuk mengubah catatan absensi">
                                            {{ $statusText }}
                                        </button>
                                    </td>
                                @endforeach
                                
                                {{-- Sel Rekapitulasi --}}
                                @php
                                    $summary = $attendanceSummary[$student->id];
                                @endphp
                                <td class="bg-emerald-50/50 dark:bg-emerald-950/30 px-2 py-3 text-center font-bold text-emerald-800 dark:text-emerald-300">{{ $summary['hadir'] }}</td>
                                <td class="bg-purple-50/50 dark:bg-purple-950/30 px-2 py-3 text-center font-bold text-purple-800 dark:text-purple-300">{{ $summary['sakit'] }}</td>
                                <td class="bg-sky-50/50 dark:bg-sky-950/30 px-2 py-3 text-center font-bold text-sky-800 dark:text-sky-300">{{ $summary['izin'] }}</td>
                                <td class="bg-rose-50/50 dark:bg-rose-950/30 px-2 py-3 text-center font-bold text-rose-800 dark:text-rose-300">{{ $summary['alpa'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $period->count() + 5 }}" class="px-6 py-10 text-center text-xs text-slate-400 italic">
                                    Tidak ada data siswa di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal untuk Edit Kehadiran -->
        <div x-show="showModal" style="display: none;" @keydown.escape.window="showModal = false" 
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
            
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" 
                 class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transform transition-all p-6 sm:p-7">
                
                <form action="{{ route('teacher.attendance.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="attendance_id" :value="attendanceId">
                    <input type="hidden" name="student_id" :value="studentId">
                    <input type="hidden" name="date" :value="date">
                    
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                            <span class="material-icons text-xl">edit</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-title">
                                Koreksi Status Kehadiran
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Siswa: <strong x-text="studentName" class="text-slate-800 dark:text-slate-200"></strong>
                            </p>
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-200/60 dark:border-slate-800 mb-4">
                        <span class="text-xs text-slate-500 dark:text-slate-400 block mb-0.5">Tanggal Presensi:</span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white" 
                           x-text="new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></p>
                    </div>

                    <div class="space-y-2 mb-6">
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Pilih Status Baru
                        </label>
                        <select id="status" name="status" 
                                class="w-full text-xs font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">
                            <option value="tepat_waktu" :selected="currentStatus === 'tepat_waktu'">Hadir (Tepat Waktu)</option>
                            <option value="terlambat" :selected="currentStatus === 'terlambat'">Terlambat</option>
                            <option value="izin" :selected="currentStatus === 'izin'">Izin</option>
                            <option value="sakit" :selected="currentStatus === 'sakit'">Sakit</option>
                            <option value="alpa" :selected="currentStatus === 'alpa'">Alpa</option>
                            <option value="hapus" class="text-rose-600 font-bold">-- Kosongkan / Hapus Data --</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2.5">
                        <button @click="showModal = false" type="button" 
                                class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 rounded-xl shadow-md shadow-sky-600/20 transition-all active:scale-95">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>


