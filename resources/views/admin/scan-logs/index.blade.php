<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Monitoring Scan Absensi', 'url' => route('admin.scan-logs.index')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Monitoring Scan Absensi
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ===== KARTU STATISTIK HARI INI ===== --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {{-- Total Scan --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-5 flex items-center gap-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center">
                        <svg class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($todayStats->total ?? 0) }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total Scan Hari Ini</div>
                    </div>
                </div>

                {{-- Scan Masuk --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-5 flex items-center gap-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($todayStats->total_masuk ?? 0) }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Absen Masuk</div>
                    </div>
                </div>

                {{-- Scan Pulang --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-5 flex items-center gap-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($todayStats->total_pulang ?? 0) }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Absen Pulang</div>
                    </div>
                </div>

                {{-- Scan Gagal --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-5 flex items-center gap-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/40 flex items-center justify-center">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($todayStats->total_gagal ?? 0) }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Scan Gagal</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ===== TOP SCANNER HARI INI ===== --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                            </svg>
                            Top Scanner Hari Ini
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pengguna dengan scan terbanyak</p>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($topScanners as $i => $scanner)
                            <div class="flex items-center gap-3 p-4">
                                <div class="w-7 h-7 flex-shrink-0 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                       ($i === 1 ? 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' :
                                       ($i === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400')) }}">
                                    {{ $i + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-gray-800 dark:text-white truncate">{{ $scanner->user_name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        @php
                                            $roleLabels = ['admin' => 'Admin', 'operator' => 'Operator', 'satpam' => 'Satpam', 'teacher' => 'Guru', 'viewer' => 'Viewer'];
                                        @endphp
                                        {{ $roleLabels[$scanner->user_role] ?? ucfirst($scanner->user_role) }}
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <span class="text-sm font-bold text-sky-600 dark:text-sky-400">{{ $scanner->total_scan }}</span>
                                    <div class="text-xs text-slate-400">scan</div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Belum ada aktivitas scan hari ini.</div>
                        @endforelse
                    </div>
                </div>

                {{-- ===== TABEL LOG SCAN ===== --}}
                <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

                    {{-- Header & Filter --}}
                    <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                    Riwayat Log Scan
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $dateFrom->translatedFormat('d M Y') }} — {{ $dateTo->translatedFormat('d M Y') }}
                                    &bull; {{ $logs->total() }} entri
                                </p>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('admin.scan-logs.index') }}" id="filter-form">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                                {{-- Tanggal Dari --}}
                                <div>
                                    <label for="date_from" class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Dari Tanggal</label>
                                    <input type="date" id="date_from" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}"
                                        class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-sky-500 focus:border-sky-500">
                                </div>
                                {{-- Tanggal Sampai --}}
                                <div>
                                    <label for="date_to" class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo->format('Y-m-d') }}"
                                        class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-sky-500 focus:border-sky-500">
                                </div>
                                {{-- Filter User --}}
                                <div>
                                    <label for="user_id" class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Akun Scanner</label>
                                    <select id="user_id" name="user_id"
                                        class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-sky-500 focus:border-sky-500">
                                        <option value="">Semua Akun</option>
                                        @foreach ($scannerUsers as $su)
                                            <option value="{{ $su->user_id }}" {{ request('user_id') == $su->user_id ? 'selected' : '' }}>
                                                {{ $su->user_name }} ({{ ucfirst($su->user_role) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- Filter Tipe --}}
                                <div>
                                    <label for="scan_type" class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Tipe Scan</label>
                                    <select id="scan_type" name="scan_type"
                                        class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-sky-500 focus:border-sky-500">
                                        <option value="">Semua Tipe</option>
                                        <option value="masuk"  {{ request('scan_type') === 'masuk'  ? 'selected' : '' }}>Absen Masuk</option>
                                        <option value="pulang" {{ request('scan_type') === 'pulang' ? 'selected' : '' }}>Absen Pulang</option>
                                        <option value="gagal"  {{ request('scan_type') === 'gagal'  ? 'selected' : '' }}>Gagal / Ditolak</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-3">
                                {{-- Pencarian --}}
                                <div class="relative flex-1">
                                    <input type="text" name="search" placeholder="Cari nama akun, nama siswa, NIS..."
                                        value="{{ request('search') }}"
                                        class="w-full pl-10 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-sky-500 focus:border-sky-500">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <label for="per_page" class="sr-only">Per halaman</label>
                                    <select id="per_page" name="per_page" onchange="document.getElementById('filter-form').submit()"
                                        class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-sky-500 focus:border-sky-500">
                                        @foreach([15, 25, 50, 100] as $pp)
                                            <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}/hal</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                    </svg>
                                    Filter
                                </button>
                                @if(request()->hasAny(['date_from', 'date_to', 'user_id', 'scan_type', 'search']))
                                    <a href="{{ route('admin.scan-logs.index') }}"
                                        class="inline-flex items-center px-3 py-2 text-slate-500 dark:text-slate-400 text-sm font-medium rounded-md hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Tabel --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 whitespace-nowrap">Waktu Scan</th>
                                    <th class="px-4 py-3">Akun Scanner</th>
                                    <th class="px-4 py-3 hidden sm:table-cell">Role</th>
                                    <th class="px-4 py-3">Siswa</th>
                                    <th class="px-4 py-3">Hasil</th>
                                    <th class="px-4 py-3 hidden md:table-cell">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @forelse ($logs as $log)
                                    <tr class="bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        {{-- Waktu --}}
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="font-medium text-gray-900 dark:text-white text-xs">
                                                {{ $log->scanned_at->translatedFormat('D, d M Y') }}
                                            </div>
                                            <div class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
                                                {{ $log->scanned_at->format('H:i:s') }}
                                            </div>
                                        </td>

                                        {{-- Akun Scanner --}}
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $log->user_name }}</div>
                                            <div class="text-xs text-slate-400 sm:hidden">{{ ucfirst($log->user_role) }}</div>
                                        </td>

                                        {{-- Role --}}
                                        <td class="px-4 py-3 hidden sm:table-cell">
                                            @php
                                                $roleBadge = match(strtolower($log->user_role)) {
                                                    'admin'    => 'bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300',
                                                    'operator' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300',
                                                    'satpam'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300',
                                                    'teacher'  => 'bg-violet-100 text-violet-800 dark:bg-violet-900/60 dark:text-violet-300',
                                                    'viewer'   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    default    => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $roleBadge }}">
                                                {{ ucfirst($log->user_role) }}
                                            </span>
                                        </td>

                                        {{-- Siswa --}}
                                        <td class="px-4 py-3">
                                            @if($log->student_name)
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $log->student_name }}</div>
                                                @if($log->student_nis)
                                                    <div class="text-xs text-slate-400">NIS: {{ $log->student_nis }}</div>
                                                @endif
                                            @else
                                                <span class="text-slate-400 text-xs italic">Tidak diketahui</span>
                                            @endif
                                        </td>

                                        {{-- Hasil Scan --}}
                                        <td class="px-4 py-3">
                                            @php
                                                $typeBadge = match($log->scan_type) {
                                                    'masuk'  => ['bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300', '✓ Masuk'],
                                                    'pulang' => ['bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300',   '✓ Pulang'],
                                                    'gagal'  => ['bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300',   '✗ Gagal'],
                                                    default  => ['bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',   $log->scan_type],
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $typeBadge[0] }}">
                                                {{ $typeBadge[1] }}
                                            </span>
                                        </td>

                                        {{-- Keterangan --}}
                                        <td class="px-4 py-3 hidden md:table-cell text-xs text-slate-500 dark:text-slate-400">
                                            {{ $log->failure_reason ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center">
                                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                                <svg class="w-10 h-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                                                </svg>
                                                <p class="text-sm font-medium">Tidak ada log scan ditemukan</p>
                                                <p class="text-xs">Coba ubah filter atau rentang tanggal.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginasi --}}
                    @if($logs->hasPages())
                        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                            {{ $logs->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
