{{-- 
    File ini HANYA berisi struktur tabel HTML sederhana.
    Maatwebsite/Excel akan mengubah tabel ini menjadi file Excel.
--}}
<table>
    <thead>
        {{-- KOP DOKUMEN DINAMIS --}}
        <tr>
            <th colspan="{{ $period->count() + 2 }}" style="font-weight: bold; font-size: 16px; text-align: center;">
                {{-- Mengambil nama sekolah dari pengaturan, dengan fallback default --}}
                {{ $settings['school_name'] ?? 'NAMA SEKOLAH ANDA' }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ $period->count() + 2 }}" style="font-size: 12px; text-align: center;">
                {{ $settings['school_address'] ?? 'Alamat Sekolah Anda' }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ $period->count() + 2 }}" style="font-size: 12px; text-align: center;">
                {{-- Anda bisa menambahkan data lain seperti No. Telp atau Email jika ada di pengaturan --}}
                {{-- Contoh: Telp: {{ $settings['school_phone'] ?? '-' }} --}}
            </th>
        </tr>

        {{-- Garis Pemisah (akan terlihat seperti baris kosong di Excel) --}}
        <tr>
            <th colspan="{{ $period->count() + 2 }}" style="border-bottom: 2px solid #000000;"></th>
        </tr>
        <tr></tr>

        {{-- JUDUL LAPORAN --}}
        <tr>
            <th colspan="{{ $period->count() + 2 }}" style="font-weight: bold; font-size: 14px; text-align: center;">
                LAPORAN KEHADIRAN SISWA
            </th>
        </tr>
        <tr>
            <th colspan="{{ $period->count() + 2 }}" style="font-weight: bold; font-size: 12px; text-align: center;">
                KELAS: {{ $class->name }} - BULAN: {{ $selectedDate->translatedFormat('F Y') }}
            </th>
        </tr>
        <tr></tr>
        
        {{-- Header Tabel Data --}}
        <tr>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #d3d3d3;">No.</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #d3d3d3; min-width: 200px;">Nama Siswa</th>
            @foreach ($period as $date)
                <th style="font-weight: bold; border: 1px solid #000000; background-color: #d3d3d3; text-align: center;">{{ $date->format('d') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $index => $student)
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $student->name }}</td>
                @foreach ($period as $date)
                    @php
                        $dateString = $date->format('Y-m-d');
                        $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        $statusText = ''; // Dikosongkan agar sel terlihat bersih

                        switch ($status) {
                            case 'tepat_waktu': $statusText = 'H'; break;
                            case 'terlambat': $statusText = 'T'; break;
                            case 'izin': $statusText = 'I'; break;
                            case 'sakit': $statusText = 'S'; break;
                            case 'alpa': $statusText = 'A'; break;
                        }
                    @endphp
                    <td style="border: 1px solid #000000; text-align: center;">
                        {{ $statusText }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

