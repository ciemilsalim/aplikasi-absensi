<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Pengajuan Izin', 'url' => route('admin.leave_requests.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Pengajuan Izin & Sakit') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Bagian Pengajuan yang Perlu Diproses -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-6">Pengajuan Perlu Diproses ({{ $pendingRequests->count() }})</h3>
                    
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                    @if (session('error'))
                         <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div>
                    @endif

                    <div class="relative overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                           <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Siswa</th>
                                    <th scope="col" class="px-6 py-3">Tanggal</th>
                                    <th scope="col" class="px-6 py-3">Detail</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pendingRequests as $request)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $request->student->name }} <br><span class="text-xs text-gray-500">Kelas: {{ $request->student->schoolClass->name ?? '-' }}</span></td>
                                    <td class="px-6 py-4">{{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}</td>
                                    <td class="px-6 py-4"><p class="font-semibold capitalize">{{ $request->type }}</p><p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $request->reason }}</p>@if($request->attachment)<a href="{{ asset('storage/' . $request->attachment) }}" target="_blank" class="text-xs text-sky-600 hover:underline">Lihat Lampiran</a>@endif</td>
                                    {{-- PERBAIKAN: Menambahkan 'relative' agar pop-up Tolak berada di posisi yang benar --}}
                                    <td class="px-6 py-4 relative">
                                        <div class="flex items-center justify-center gap-2" x-data="{ showRejectForm: false }">
                                            <form action="{{ route('admin.leave_requests.approve', $request) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui pengajuan ini?');">@csrf<button type="submit" class="font-medium text-green-600 dark:text-green-500 hover:underline">Setujui</button></form>
                                            <button @click="showRejectForm = !showRejectForm" class="font-medium text-red-600 dark:text-red-500 hover:underline">Tolak</button>
                                            <div x-show="showRejectForm" @click.away="showRejectForm = false" class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-700 p-4 rounded-lg shadow-lg border dark:border-slate-600 z-10" style="display: none;"><form action="{{ route('admin.leave_requests.reject', $request) }}" method="POST">@csrf<label for="rejection_reason_{{ $request->id }}" class="text-sm font-medium">Alasan Penolakan</label><x-text-input type="text" name="rejection_reason" id="rejection_reason_{{ $request->id }}" class="w-full mt-1 text-sm" required/><div class="flex justify-end gap-2 mt-2"><button type="button" @click="showRejectForm = false" class="text-xs">Batal</button><x-primary-button type="submit" class="!py-1 !px-2 !text-xs">Kirim</x-primary-button></div></form></div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="px-6 py-4 text-center">Tidak ada pengajuan yang perlu diproses.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bagian Riwayat Pengajuan -->
             <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-6">Riwayat Pengajuan</h3>
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                           <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Siswa</th>
                                    <th scope="col" class="px-6 py-3">Tanggal</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Diproses Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($processedRequests as $request)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $request->student->name }}</td>
                                    <td class="px-6 py-4">{{ $request->start_date->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        @if ($request->status == 'approved')<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-xs font-semibold px-2.5 py-0.5 rounded-full">Disetujui</span>@elseif ($request->status == 'rejected')<span class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 text-xs font-semibold px-2.5 py-0.5 rounded-full">Ditolak</span>@endif
                                    </td>
                                    <td class="px-6 py-4">{{ $request->approver->name ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="px-6 py-4 text-center">Belum ada riwayat pengajuan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $processedRequests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
