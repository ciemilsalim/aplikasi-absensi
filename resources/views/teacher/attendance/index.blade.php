<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Riwayat Absensi Saya
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daftar Kehadiran</h3>
                        <a href="{{ route('teacher.attendance.scanner') }}" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                            Buka Scanner Absensi
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Tanggal</th>
                                    <th scope="col" class="px-6 py-3">Waktu</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Lokasi</th>
                                    <th scope="col" class="px-6 py-3">Foto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                        <td class="px-6 py-4">
                                            {{ $attendance->created_at->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $attendance->created_at->format('H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 font-semibold rounded-full 
                                                @if($attendance->status == 'hadir') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                @elseif($attendance->status == 'izin') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                @elseif($attendance->status == 'sakit') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @endif">
                                                {{ ucfirst($attendance->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->latitude && $attendance->longitude)
                                                <a href="https://www.google.com/maps?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank" class="text-sky-600 hover:underline">
                                                    Lihat Peta
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->photo_evidence)
                                                <button onclick="showPhoto('{{ asset('storage/'.$attendance->photo_evidence) }}')" class="text-sky-600 hover:underline">
                                                    Lihat Foto
                                                </button>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                        <td colspan="5" class="px-6 py-4 text-center">Belum ada data absensi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Foto -->
    <div id="photo-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4" onclick="closePhoto()">
        <div class="relative max-w-lg w-full">
            <img id="modal-image" src="" alt="Bukti Absensi" class="w-full rounded-lg shadow-xl">
            <button onclick="closePhoto()" class="absolute top-2 right-2 bg-white rounded-full p-2 text-gray-800 hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        function showPhoto(url) {
            document.getElementById('modal-image').src = url;
            document.getElementById('photo-modal').classList.remove('hidden');
        }
        
        function closePhoto() {
            document.getElementById('photo-modal').classList.add('hidden');
        }
    </script>
    @endpush
</x-app-layout>
