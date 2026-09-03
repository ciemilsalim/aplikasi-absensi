<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Pengajuan & Intervensi Izin', 'url' => route('admin.leave_requests.index')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1 flex items-center gap-2">
                    <span>Presensi Izin & Sakit Siswa</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Kelola permohonan izin orang tua dan intervensi manual langsung oleh Tata Usaha & Admin
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('open-manual-modal')" 
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-sky-500/20 active:scale-95 transition-all">
                    <span class="material-icons text-base sm:text-lg">add_circle</span>
                    <span>+ Input Izin Manual (TU/Admin)</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div x-data="{ 
            activeTab: '{{ request('manual_page') ? 'manual' : (request('processed_page') ? 'processed' : (isset($pendingRequests) && $pendingRequests->count() > 0 ? 'pending' : 'manual')) }}',
            isManualModalOpen: false,
            isEditModalOpen: false,
            editData: {
                id: null,
                student_name: '',
                class_name: '',
                start_date: '',
                end_date: '',
                type: 'sakit',
                submission_source: 'whatsapp',
                reason: '',
                attachment_url: null
            },
            selectedClassId: '',
            studentsInClass: [],
            selectedStudentIds: [],
            isLoadingStudents: false,
            quickDateRange(days) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                const todayStr = `${yyyy}-${mm}-${dd}`;
                
                const startInput = document.getElementById('manual_start_date');
                const endInput = document.getElementById('manual_end_date');
                if (startInput) startInput.value = todayStr;
                
                const target = new Date();
                target.setDate(today.getDate() + (days - 1));
                const tYyyy = target.getFullYear();
                const tMm = String(target.getMonth() + 1).padStart(2, '0');
                const tDd = String(target.getDate()).padStart(2, '0');
                if (endInput) endInput.value = `${tYyyy}-${tMm}-${tDd}`;
            },
            loadStudentsByClass(classId) {
                if (!classId) {
                    this.studentsInClass = [];
                    this.selectedStudentIds = [];
                    return;
                }
                this.isLoadingStudents = true;
                fetch(`{{ route('admin.leave_requests.students_by_class') }}?class_id=${classId}`)
                    .then(res => res.json())
                    .then(data => {
                        this.studentsInClass = data.students || [];
                        this.selectedStudentIds = [];
                        this.isLoadingStudents = false;
                    })
                    .catch(() => {
                        this.isLoadingStudents = false;
                    });
            },
            selectAllStudents() {
                if (this.selectedStudentIds.length === this.studentsInClass.length) {
                    this.selectedStudentIds = [];
                } else {
                    this.selectedStudentIds = this.studentsInClass.map(s => s.id);
                }
            },
            openEditModal(id) {
                fetch(`{{ url('/admin/leave-requests') }}/${id}/edit`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    this.editData = data;
                    this.isEditModalOpen = true;
                });
            }
        }" 
        @open-manual-modal.window="isManualModalOpen = true"
        class="space-y-5 pb-12">

        {{-- ALERT NOTIFICATIONS --}}
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs" role="alert">
                <span class="material-icons text-emerald-600 dark:text-emerald-400 text-lg sm:text-xl shrink-0">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs" role="alert">
                <span class="material-icons text-rose-600 dark:text-rose-400 text-lg sm:text-xl shrink-0">error_outline</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 text-xs font-semibold shadow-xs">
                <div class="font-bold flex items-center gap-1.5 mb-1 text-sm">
                    <span class="material-icons text-base">warning</span>
                    <span>Terdapat kesalahan pada input Anda:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 ml-2">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- SUMMARY STATS CARDS (MOBILE FIRST) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            {{-- Pending Card --}}
            <div @click="activeTab = 'pending'" 
                 class="cursor-pointer bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-2xl sm:rounded-3xl border transition-all duration-200 shadow-xs"
                 :class="activeTab === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-amber-400'">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Menunggu Ortu</span>
                    <span class="w-7 h-7 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-base">hourglass_top</span>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">{{ isset($pendingRequests) ? $pendingRequests->count() : 0 }}</span>
                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">Perlu tinjau</span>
                </div>
            </div>

            {{-- Manual Interventions Card --}}
            <div @click="activeTab = 'manual'" 
                 class="cursor-pointer bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-2xl sm:rounded-3xl border transition-all duration-200 shadow-xs"
                 :class="activeTab === 'manual' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-sky-400'">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Intervensi TU/Admin</span>
                    <span class="w-7 h-7 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <span class="material-icons text-base">bolt</span>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">{{ isset($manualInterventions) ? $manualInterventions->total() : 0 }}</span>
                    <span class="text-[10px] text-sky-600 dark:text-sky-400 font-semibold">Tersinkronisasi</span>
                </div>
            </div>

            {{-- Processed History Card --}}
            <div @click="activeTab = 'processed'" 
                 class="cursor-pointer bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-2xl sm:rounded-3xl border transition-all duration-200 shadow-xs"
                 :class="activeTab === 'processed' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-emerald-400'">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Riwayat Ortu</span>
                    <span class="w-7 h-7 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-base">task_alt</span>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">{{ isset($processedRequests) ? $processedRequests->total() : 0 }}</span>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Diproses</span>
                </div>
            </div>

            {{-- Direct Action Button Card --}}
            <div @click="isManualModalOpen = true" 
                 class="cursor-pointer bg-gradient-to-br from-indigo-500 to-sky-600 text-white p-3.5 sm:p-4 rounded-2xl sm:rounded-3xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-sky-100">Aksi Cepat</span>
                    <span class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center">
                        <span class="material-icons text-base">phone_in_talk</span>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xs sm:text-sm font-extrabold flex items-center gap-1">
                        <span>Input Izin WA/Telp</span>
                        <span class="material-icons text-sm">arrow_forward</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- MOBILE-FIRST NAVIGATION TABS --}}
        <div class="bg-slate-100 dark:bg-slate-850 p-1.5 rounded-2xl flex items-center gap-1 overflow-x-auto scrollbar-none border border-slate-200 dark:border-slate-800">
            <button type="button" @click="activeTab = 'manual'" 
                    class="flex-1 min-w-[140px] py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                    :class="activeTab === 'manual' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                <span class="material-icons text-base">bolt</span>
                <span>Intervensi Manual ({{ isset($manualInterventions) ? $manualInterventions->total() : 0 }})</span>
            </button>

            <button type="button" @click="activeTab = 'pending'" 
                    class="flex-1 min-w-[140px] py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 whitespace-nowrap relative"
                    :class="activeTab === 'pending' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                <span class="material-icons text-base">pending_actions</span>
                <span>Pengajuan Ortu</span>
                @if(isset($pendingRequests) && $pendingRequests->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-extrabold leading-none">{{ $pendingRequests->count() }}</span>
                @endif
            </button>

            <button type="button" @click="activeTab = 'processed'" 
                    class="flex-1 min-w-[140px] py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                    :class="activeTab === 'processed' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                <span class="material-icons text-base">history</span>
                <span>Riwayat Ortu Selesai ({{ isset($processedRequests) ? $processedRequests->total() : 0 }})</span>
            </button>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 1: DAFTAR INTERVENSI MANUAL TU & ADMIN                              --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'manual'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
            
            {{-- SEARCH & FILTER BAR (MOBILE FIRST) --}}
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <form action="{{ route('admin.leave_requests.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                    <input type="hidden" name="manual_page" value="1">
                    
                    {{-- Search Input --}}
                    <div class="sm:col-span-2 relative">
                        <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Cari nama siswa atau NISN..." 
                               class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/80 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 dark:text-white" />
                    </div>

                    {{-- Class Filter --}}
                    <div>
                        <select name="class_id" class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/80 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 dark:text-white">
                            <option value="">Semua Kelas</option>
                            @if(isset($classes))
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Submit & Reset --}}
                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-xs transition-colors flex items-center justify-center gap-1">
                            <span class="material-icons text-sm">filter_alt</span>
                            <span>Filter</span>
                        </button>
                        @if(request()->hasAny(['search', 'class_id', 'type']))
                            <a href="{{ route('admin.leave_requests.index') }}" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition-colors">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- LIST: MOBILE CARDS (Visible on mobile, hidden on tablet/desktop) --}}
            <div class="block sm:hidden space-y-3">
                @if(isset($manualInterventions))
                    @forelse ($manualInterventions as $item)
                        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-3">
                            {{-- Top row: Student & Class --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                        {{ strtoupper(substr($item->student?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-extrabold text-slate-900 dark:text-white text-xs truncate">{{ $item->student?->name ?? 'Siswa' }}</h4>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Kelas: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $item->student?->schoolClass?->name ?? '-' }}</span></p>
                                    </div>
                                </div>
                                
                                {{-- Type Badge --}}
                                <span class="px-2.5 py-1 rounded-xl text-[10px] font-extrabold uppercase shrink-0 {{ $item->type == 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900/60' }}">
                                    {{ $item->type }}
                                </span>
                            </div>

                            {{-- Middle row: Dates & Source --}}
                            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">
                                    <span class="material-icons text-xs text-slate-400">calendar_today</span>
                                    <span>
                                        {{ $item->start_date ? $item->start_date->format('d M Y') : '-' }} 
                                        @if($item->start_date && $item->end_date && !$item->start_date->isSameDay($item->end_date)) 
                                            s/d {{ $item->end_date->format('d M Y') }} 
                                        @endif
                                    </span>
                                </span>

                                {{-- Source Badge --}}
                                @php
                                    $source = $item->submission_source ?? 'whatsapp';
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase
                                    {{ $source == 'whatsapp' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : '' }}
                                    {{ $source == 'telepon' ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : '' }}
                                    {{ $source == 'surat' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : '' }}
                                    {{ $source == 'lisan' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : '' }}
                                    {{ !in_array($source, ['whatsapp','telepon','surat','lisan']) ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : '' }}
                                ">
                                    <span class="material-icons text-xs">
                                        {{ $source == 'whatsapp' ? 'chat' : ($source == 'telepon' ? 'call' : ($source == 'surat' ? 'description' : 'record_voice_over')) }}
                                    </span>
                                    <span>{{ ucfirst($source) }}</span>
                                </span>
                            </div>

                            {{-- Reason --}}
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-855 border border-slate-100 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300">
                                <p class="italic">"{{ $item->reason }}"</p>
                                @if($item->attachment)
                                    <a href="{{ asset('storage/' . $item->attachment) }}" target="_blank" 
                                       class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline mt-1.5">
                                        <span class="material-icons text-xs">attachment</span>
                                        <span>Lihat Foto / Surat Bukti</span>
                                    </a>
                                @endif
                            </div>

                            {{-- Footer: Staf Input & Action Buttons --}}
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                                <span class="text-[10px] text-slate-400">
                                    Diinput oleh: <b class="text-slate-600 dark:text-slate-300">{{ $item->creator?->name ?? ($item->approver?->name ?? 'Admin/TU') }}</b>
                                </span>

                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="openEditModal({{ $item->id }})" 
                                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-colors"
                                            title="Edit Intervensi">
                                        <span class="material-icons text-sm">edit</span>
                                    </button>
                                    
                                    <form action="{{ route('admin.leave_requests.destroy', $item) }}" method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin membatalkan izin siswa ini? Data presensi harian & mapel akan dinetralkan kembali.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition-colors"
                                                title="Batalkan & Hapus Izin">
                                            <span class="material-icons text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center">
                            <span class="material-icons text-4xl text-slate-300 dark:text-slate-600 mb-2">assignment_turned_in</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada data intervensi izin manual.</p>
                            <button type="button" @click="isManualModalOpen = true" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                                <span>+ Input Izin Sekarang</span>
                            </button>
                        </div>
                    @endforelse

                    {{-- Pagination for Mobile --}}
                    <div class="pt-2">
                        {{ $manualInterventions->links() }}
                    </div>
                @endif
            </div>

            {{-- DESKTOP / TABLET TABLE VIEW --}}
            <div class="hidden sm:block bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                        <thead class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Siswa & Kelas</th>
                                <th scope="col" class="px-5 py-3.5">Rentang Tanggal</th>
                                <th scope="col" class="px-5 py-3.5">Jenis & Sumber</th>
                                <th scope="col" class="px-5 py-3.5">Alasan / Keterangan</th>
                                <th scope="col" class="px-5 py-3.5">Petugas Input</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @if(isset($manualInterventions))
                                @forelse ($manualInterventions as $item)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                                {{ strtoupper(substr($item->student?->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-slate-900 dark:text-white text-xs">{{ $item->student?->name ?? 'Siswa' }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Kelas: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $item->student?->schoolClass?->name ?? '-' }}</span></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                            <span class="material-icons text-xs text-slate-400">date_range</span>
                                            <span>
                                                {{ $item->start_date ? $item->start_date->format('d M Y') : '-' }} 
                                                @if($item->start_date && $item->end_date && !$item->start_date->isSameDay($item->end_date)) 
                                                    - {{ $item->end_date->format('d M Y') }} 
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-block w-fit px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $item->type == 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300' }}">
                                                {{ $item->type }}
                                            </span>
                                            @php $source = $item->submission_source ?? 'whatsapp'; @endphp
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                                <span class="material-icons text-xs {{ $source == 'whatsapp' ? 'text-emerald-500' : ($source == 'telepon' ? 'text-sky-500' : 'text-amber-500') }}">
                                                    {{ $source == 'whatsapp' ? 'chat' : ($source == 'telepon' ? 'call' : ($source == 'surat' ? 'description' : 'record_voice_over')) }}
                                                </span>
                                                <span>{{ ucfirst($source) }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-xs">
                                        <p class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2">"{{ $item->reason }}"</p>
                                        @if($item->attachment)
                                            <a href="{{ asset('storage/' . $item->attachment) }}" target="_blank" 
                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline mt-0.5">
                                                <span class="material-icons text-xs">attachment</span>
                                                <span>Lampiran Bukti</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-400">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $item->creator?->name ?? ($item->approver?->name ?? 'Admin/TU') }}</span>
                                        <p class="text-[10px] text-slate-400">{{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button" @click="openEditModal({{ $item->id }})" 
                                                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1">
                                                <span class="material-icons text-xs">edit</span>
                                                <span>Edit</span>
                                            </button>

                                            <form action="{{ route('admin.leave_requests.destroy', $item) }}" method="POST" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin membatalkan izin siswa ini? Data presensi harian & mapel akan dinetralkan kembali.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-300 text-xs font-bold transition-all flex items-center gap-1">
                                                    <span class="material-icons text-xs">delete</span>
                                                    <span>Batal</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-xs text-slate-400 italic">
                                        Belum ada data intervensi izin manual.
                                    </td>
                                </tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Desktop --}}
                @if(isset($manualInterventions))
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        {{ $manualInterventions->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 2: PENGAJUAN MENUNGGU PERSETUJUAN DARI ORANG TUA                     --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'pending'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">pending_actions</span>
                            Permohonan Izin dari Orang Tua
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tinjau permohonan izin yang diajukan wali murid melalui aplikasi</p>
                    </div>
                </div>

                {{-- Mobile Cards for Pending Requests --}}
                <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
                    @if(isset($pendingRequests))
                        @forelse ($pendingRequests as $req)
                            <div class="p-4 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="font-extrabold text-slate-900 dark:text-white text-xs">{{ $req->student?->name ?? 'Siswa' }}</h4>
                                        <p class="text-[10px] text-slate-500">Kelas: <b class="text-slate-700 dark:text-slate-300">{{ $req->student?->schoolClass?->name ?? '-' }}</b> • Ortu: {{ $req->parent?->name ?? '-' }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $req->type == 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' }}">
                                        {{ $req->type }}
                                    </span>
                                </div>

                                <div class="text-[11px] text-slate-600 dark:text-slate-400">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">Rentang:</span> {{ $req->start_date ? $req->start_date->format('d M Y') : '-' }} - {{ $req->end_date ? $req->end_date->format('d M Y') : '-' }}
                                </div>

                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 text-xs text-slate-700 dark:text-slate-300">
                                    "{{ $req->reason }}"
                                    @if($req->attachment)
                                        <div class="mt-1">
                                            <a href="{{ asset('storage/' . $req->attachment) }}" target="_blank" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 underline">Lihat Lampiran</a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Approve & Reject Actions --}}
                                <div class="flex items-center gap-2 pt-1" x-data="{ showRejectInput: false }">
                                    <form action="{{ route('admin.leave_requests.approve', $req) }}" method="POST" class="flex-1" onsubmit="return confirm('Setujui izin ini?');">
                                        @csrf
                                        <button type="submit" class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs">
                                            Setujui
                                        </button>
                                    </form>
                                    <button type="button" @click="showRejectInput = !showRejectInput" class="py-2 px-3 rounded-xl bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200">
                                        Tolak
                                    </button>
                                    
                                    <div x-show="showRejectInput" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                                        <div class="bg-white dark:bg-slate-850 p-5 rounded-2xl max-w-sm w-full shadow-2xl">
                                            <h4 class="text-xs font-bold text-slate-900 dark:text-white mb-2">Tolak Izin {{ $req->student?->name ?? 'Siswa' }}</h4>
                                            <form action="{{ route('admin.leave_requests.reject', $req) }}" method="POST">
                                                @csrf
                                                <input type="text" name="rejection_reason" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-white" placeholder="Alasan penolakan..." required />
                                                <div class="flex justify-end gap-2 mt-3">
                                                    <button type="button" @click="showRejectInput = false" class="px-3 py-1.5 text-xs text-slate-500">Batal</button>
                                                    <button type="submit" class="px-3 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-bold">Kirim Tolak</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-slate-400 italic">
                                Tidak ada pengajuan izin yang menunggu persetujuan.
                            </div>
                        @endforelse
                    @endif
                </div>

                {{-- Desktop Table for Pending Requests --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                        <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">Siswa & Kelas</th>
                                <th scope="col" class="px-6 py-3.5">Rentang Tanggal</th>
                                <th scope="col" class="px-6 py-3.5">Keterangan & Bukti</th>
                                <th scope="col" class="px-6 py-3.5 text-center">Keputusan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @if(isset($pendingRequests))
                                @forelse ($pendingRequests as $req)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-300 font-bold flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-900/40">
                                                {{ strtoupper(substr($req->student?->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $req->student?->name ?? 'Siswa' }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Kelas: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $req->student?->schoolClass?->name ?? '-' }}</span></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                            <span class="material-icons text-xs text-slate-400">date_range</span>
                                            <span>{{ $req->start_date ? $req->start_date->format('d M Y') : '-' }} - {{ $req->end_date ? $req->end_date->format('d M Y') : '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $req->type == 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' }}">
                                                {{ $req->type }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm">{{ $req->reason }}</p>
                                        @if($req->attachment)
                                            <a href="{{ asset('storage/' . $req->attachment) }}" target="_blank" 
                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline mt-1.5">
                                                <span class="material-icons text-xs">attachment</span>
                                                <span>Lihat Dokumen / Surat</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 relative text-center">
                                        <div class="flex items-center justify-center gap-2" x-data="{ showRejectForm: false }">
                                            <form action="{{ route('admin.leave_requests.approve', $req) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui pengajuan izin ini?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition-all active:scale-95">
                                                    <span class="material-icons text-sm">check</span>
                                                    <span>Setujui</span>
                                                </button>
                                            </form>
                                            
                                            <button @click="showRejectForm = !showRejectForm" 
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-900/40 text-xs font-bold transition-all active:scale-95">
                                                <span class="material-icons text-sm">close</span>
                                                <span>Tolak</span>
                                            </button>

                                            <div x-show="showRejectForm" @click.away="showRejectForm = false" 
                                                 class="absolute right-6 top-12 w-72 bg-white dark:bg-slate-850 p-4 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 z-30 text-left" 
                                                 style="display: none;" x-transition>
                                                <form action="{{ route('admin.leave_requests.reject', $req) }}" method="POST">
                                                    @csrf
                                                    <label class="text-xs font-bold text-slate-800 dark:text-white block mb-1">Alasan Penolakan</label>
                                                    <input type="text" name="rejection_reason" 
                                                           class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-white" 
                                                           placeholder="Tulis alasan singkat..." required />
                                                    <div class="flex justify-end gap-2 mt-3">
                                                        <button type="button" @click="showRejectForm = false" class="px-2.5 py-1 text-xs font-bold text-slate-500">Batal</button>
                                                        <button type="submit" class="px-3 py-1 rounded-lg bg-rose-600 text-white text-xs font-bold shadow-xs">Kirim Tolak</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-xs text-slate-400 italic">
                                        Tidak ada pengajuan izin yang sedang menunggu proses saat ini.
                                    </td>
                                </tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 3: RIWAYAT KEPUTUSAN ORTU SELESAI                                    --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'processed'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-icons text-sky-500 text-lg">history</span>
                        Riwayat Keputusan Izin Orang Tua
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-slate-600 dark:text-slate-300">
                        <thead class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/75 dark:bg-slate-850/50 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Nama Siswa</th>
                                <th scope="col" class="px-5 py-3.5">Tanggal Izin</th>
                                <th scope="col" class="px-5 py-3.5">Status Akhir</th>
                                <th scope="col" class="px-5 py-3.5">Diproses Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @if(isset($processedRequests))
                                @forelse ($processedRequests as $req)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                                        {{ $req->student?->name ?? 'Siswa' }}
                                    </td>
                                    <td class="px-5 py-3.5 font-semibold">
                                        {{ $req->start_date ? $req->start_date->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if ($req->status == 'approved')
                                            <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disetujui
                                            </span>
                                        @elseif ($req->status == 'rejected')
                                            <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $req->approver?->name ?? '-' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-xs text-slate-400 italic">
                                        Belum ada riwayat pengajuan izin orang tua yang diproses.
                                    </td>
                                </tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>

                @if(isset($processedRequests))
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        {{ $processedRequests->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL INPUT INTERVENSI MANUAL (MOBILE-FIRST DIALOG)                      --}}
        {{-- ========================================================================= --}}
        <div x-show="isManualModalOpen" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.away="isManualModalOpen = false" 
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                {{-- Modal Header --}}
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-sky-500/10 to-indigo-500/10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-sky-500 text-white flex items-center justify-center shadow-xs">
                            <span class="material-icons text-lg">edit_calendar</span>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">Input Izin / Sakit Manual</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Sinkron otomatis ke Wali Kelas & Guru Mapel</p>
                        </div>
                    </div>
                    <button type="button" @click="isManualModalOpen = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <span class="material-icons text-base">close</span>
                    </button>
                </div>

                {{-- Modal Body (Scrollable) --}}
                <form action="{{ route('admin.leave_requests.store_manual') }}" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                    @csrf

                    {{-- 1. Pilih Kelas & Siswa (Multi-Select) --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">
                            1. Pilih Kelas & Siswa <span class="text-rose-500">*</span>
                        </label>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <select x-model="selectedClassId" @change="loadStudentsByClass($event.target.value)" 
                                        class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                    <option value="">-- Pilih Kelas --</option>
                                    @if(isset($classes))
                                        @foreach($classes as $cls)
                                            <option value="{{ $cls->id }}">{{ $cls->name }} ({{ $cls->students->count() }} siswa)</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllStudents()" 
                                        :disabled="studentsInClass.length === 0"
                                        class="w-full py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold disabled:opacity-50 transition-colors">
                                    <span x-text="selectedStudentIds.length === studentsInClass.length && studentsInClass.length > 0 ? 'Batalkan Semua' : 'Pilih Semua di Kelas'"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Students Checkbox Container --}}
                        <div class="mt-2 rounded-2xl border border-slate-200 dark:border-slate-750 bg-slate-50/50 dark:bg-slate-850/50 p-3 max-h-44 overflow-y-auto">
                            <template x-if="isLoadingStudents">
                                <div class="py-6 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
                                    <span class="material-icons animate-spin text-base">refresh</span>
                                    <span>Memuat daftar siswa...</span>
                                </div>
                            </template>

                            <template x-if="!isLoadingStudents && studentsInClass.length === 0">
                                <div class="py-6 text-center text-xs text-slate-400">
                                    Silakan pilih kelas terlebih dahulu untuk melihat daftar siswa.
                                </div>
                            </template>

                            <div x-show="!isLoadingStudents && studentsInClass.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <template x-for="std in studentsInClass" :key="std.id">
                                    <label :class="selectedStudentIds.includes(std.id) ? 'bg-sky-50 dark:bg-sky-950/60 border-sky-300 dark:border-sky-800' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'" 
                                           class="flex items-center gap-2.5 p-2 rounded-xl border cursor-pointer hover:border-sky-400 transition-all text-xs">
                                        <input type="checkbox" name="student_ids[]" :value="std.id" x-model="selectedStudentIds" 
                                               class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500/20" />
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate" x-text="std.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Siswa terpilih: <b class="text-sky-600 dark:text-sky-400" x-text="selectedStudentIds.length"></b> siswa
                        </p>
                    </div>

                    {{-- 2. Jenis Izin & Sumber Laporan (Segmented Mobile Pills) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Jenis Izin --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">
                                2. Jenis Status <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2" x-data="{ type: 'sakit' }">
                                <label :class="type === 'sakit' ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'" 
                                       class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl cursor-pointer font-bold text-xs transition-all">
                                    <input type="radio" name="type" value="sakit" x-model="type" class="sr-only" checked />
                                    <span class="material-icons text-sm">local_hospital</span>
                                    <span>Sakit</span>
                                </label>
                                <label :class="type === 'izin' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'" 
                                       class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl cursor-pointer font-bold text-xs transition-all">
                                    <input type="radio" name="type" value="izin" x-model="type" class="sr-only" />
                                    <span class="material-icons text-sm">assignment</span>
                                    <span>Izin</span>
                                </label>
                            </div>
                        </div>

                        {{-- Sumber Laporan --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">
                                3. Media / Sumber Laporan <span class="text-rose-500">*</span>
                            </label>
                            <select name="submission_source" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500" required>
                                <option value="whatsapp">📱 Chat WhatsApp (WA)</option>
                                <option value="telepon">📞 Telepon Langsung</option>
                                <option value="surat">📄 Surat Fisik / Dokter</option>
                                <option value="lisan">🗣️ Lisan / Datang Langsung</option>
                                <option value="lainnya">📌 Lainnya</option>
                            </select>
                        </div>
                    </div>

                    {{-- 3. Rentang Tanggal --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">
                                4. Rentang Tanggal Izin <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="quickDateRange(1)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-bold hover:bg-sky-100 hover:text-sky-700">1 Hari</button>
                                <button type="button" @click="quickDateRange(2)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-bold hover:bg-sky-100 hover:text-sky-700">2 Hari</button>
                                <button type="button" @click="quickDateRange(3)" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-bold hover:bg-sky-100 hover:text-sky-700">3 Hari</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[10px] text-slate-400 block mb-0.5">Dari Tanggal</label>
                                <input type="date" name="start_date" id="manual_start_date" value="{{ date('Y-m-d') }}" 
                                       class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white" required />
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400 block mb-0.5">Sampai Tanggal</label>
                                <input type="date" name="end_date" id="manual_end_date" value="{{ date('Y-m-d') }}" 
                                       class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white" required />
                            </div>
                        </div>
                    </div>

                    {{-- 4. Keterangan / Alasan --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200">
                            5. Alasan / Keterangan Izin <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="reason" rows="2" 
                                  placeholder="Contoh: Demam dan flu, orang tua memberi kabar via chat WhatsApp..." 
                                  class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500" required></textarea>
                    </div>

                    {{-- 5. Upload Lampiran Bukti (Opsional) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            6. Foto / Bukti Dokumen (Opsional)
                        </label>
                        <input type="file" name="attachment" accept="image/*,.pdf" 
                               class="w-full text-xs p-2 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-950 dark:file:text-sky-300" />
                        <p class="text-[10px] text-slate-400">Screenshot WA / Foto surat dokter (Maksimal 5MB, format JPG, PNG, PDF)</p>
                    </div>

                    {{-- Modal Footer Actions --}}
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button" @click="isManualModalOpen = false" 
                                class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="selectedStudentIds.length === 0"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white text-xs font-extrabold shadow-md shadow-sky-500/20 disabled:opacity-50 active:scale-95 transition-all flex items-center gap-1.5">
                            <span class="material-icons text-sm">save</span>
                            <span>Simpan & Sinkronkan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL EDIT INTERVENSI MANUAL (MOBILE FIRST)                              --}}
        {{-- ========================================================================= --}}
        <div x-show="isEditModalOpen" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition>
            
            <div @click.away="isEditModalOpen = false" 
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                
                {{-- Edit Header --}}
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-850">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">Edit Intervensi Izin</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Siswa: <b class="text-slate-800 dark:text-white" x-text="editData.student_name"></b> (<span x-text="editData.class_name"></span>)
                        </p>
                    </div>
                    <button type="button" @click="isEditModalOpen = false" class="w-8 h-8 rounded-full bg-slate-200/60 dark:bg-slate-800 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                        <span class="material-icons text-base">close</span>
                    </button>
                </div>

                {{-- Edit Form --}}
                <form :action="`{{ url('/admin/leave-requests') }}/${editData.id}`" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Jenis & Sumber --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Jenis Status</label>
                            <select name="type" x-model="editData.type" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 dark:text-white" required>
                                <option value="sakit">Sakit</option>
                                <option value="izin">Izin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Sumber</label>
                            <select name="submission_source" x-model="editData.submission_source" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 dark:text-white" required>
                                <option value="whatsapp">WhatsApp (WA)</option>
                                <option value="telepon">Telepon</option>
                                <option value="surat">Surat Fisik</option>
                                <option value="lisan">Lisan / Datang</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" x-model="editData.start_date" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 dark:text-white" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" x-model="editData.end_date" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 dark:text-white" required />
                        </div>
                    </div>

                    {{-- Alasan --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Alasan / Keterangan</label>
                        <textarea name="reason" x-model="editData.reason" rows="3" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 dark:text-white" required></textarea>
                    </div>

                    {{-- Ganti Dokumen --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">Ganti Dokumen / Bukti (Opsional)</label>
                        <input type="file" name="attachment" class="w-full text-xs p-2 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700" />
                        <template x-if="editData.attachment_url">
                            <div class="mt-1 text-[11px]">
                                <a :href="editData.attachment_url" target="_blank" class="text-sky-600 font-bold underline">Lihat dokumen saat ini</a>
                            </div>
                        </template>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-xs">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
