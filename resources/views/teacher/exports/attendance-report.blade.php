{{-- 
    File ini hanya berisi struktur tabel HTML sederhana untuk konversi ke Excel.
    Tidak ada tag <html>, <head>, <style>, atau <body>.
--}}
<table>
    <thead>
        {{-- Baris Judul Laporan --}}
        <tr>
            <th colspan="{{ 2 + $period->count() + 4 }}" style="font-weight: bold; font-size: 16px; text-align: center;">
                LAPORAN KEHADIRAN SISWA
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + $period->count() + 4 }}" style="text-align: center;">
                Kelas: {{ $class->name }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + $period->count() + 4 }}" style="text-align: center;">
                Bulan: {{ $selectedDate->translatedFormat('F Y') }}
            </th>
        </tr>
        <tr>
            {{-- Baris kosong sebagai spasi --}}
        </tr>

        {{-- Header Utama Tabel --}}
        <tr>
            <th rowspan="2" style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">No.</th>
            <th rowspan="2" style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">Nama Siswa</th>
            <th colspan="{{ $period->count() }}" style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">Tanggal</th>
            <th colspan="4" style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">Rekapitulasi</th>
        </tr>
        <tr>
            {{-- Header Tanggal --}}
            @foreach ($period as $date)
                <th style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">{{ $date->format('d') }}</th>
            @endforeach
            {{-- Header Rekapitulasi --}}
            <th style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">H</th>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">S</th>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">I</th>
            <th style="font-weight: bold; border: 1px solid #000; background-color: #f2f2f2; text-align: center;">A</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($students as $student)
            <tr>
                <td style="border: 1px solid #000; text-align: center;">{{ $loop->iteration }}</td>
                <td style="border: 1px solid #000; text-align: left;">{{ $student->name }}</td>
                
                {{-- Data Absensi Harian --}}
                @foreach ($period as $date)
                    @php
                        $dateString = $date->format('Y-m-d');
                        $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        $statusText = '-'; // Default value
                        switch ($status) {
                            case 'tepat_waktu': $statusText = 'H'; break;
                            case 'terlambat': $statusText = 'T'; break;
                            case 'izin': $statusText = 'I'; break;
                            case 'sakit': $statusText = 'S'; break;
                            case 'alpa': $statusText = 'A'; break;
                        }
                    @endphp
                    <td style="border: 1px solid #000; text-align: center;">{{ $statusText }}</td>
                @endforeach

                {{-- Data Rekapitulasi --}}
                @php
                    $summary = $attendanceSummary[$student->id];
                @endphp
                <td style="border: 1px solid #000; text-align: center;">{{ $summary['hadir'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $summary['sakit'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $summary['izin'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $summary['alpa'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 2 + $period->count() + 4 }}" style="text-align: center;">Tidak ada data siswa di kelas ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

