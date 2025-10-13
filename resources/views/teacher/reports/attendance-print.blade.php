<table>
    <thead>
        <tr>
            <th colspan="2" rowspan="2"></th> 
            <th colspan="{{ count($period) + 4 }}"><strong>{{ $settings['school_name'] ?? 'NAMA SEKOLAH' }}</strong></th>
        </tr>
        <tr>
            <th colspan="{{ count($period) + 4 }}">{{ $settings['school_address'] ?? 'Alamat Sekolah' }}</th>
        </tr>
        <tr></tr>
        <tr>
            <th colspan="{{ count($period) + 6 }}"><strong>LAPORAN KEHADIRAN SISWA</strong></th>
        </tr>
        <tr>
            <th colspan="{{ count($period) + 6 }}"><strong>KELAS: {{ $class->name }} - BULAN: {{ $selectedDate->translatedFormat('F Y') }}</strong></th>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <th rowspan="2" style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;"><strong>No.</strong></th>
            <th rowspan="2" style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;"><strong>Nama Siswa</strong></th>
            <th colspan="{{ count($period) }}" style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;"><strong>Tanggal</strong></th>
            <th colspan="4" style="background-color: #e0e0e0; font-weight: bold; border: 1px solid #000;"><strong>Rekapitulasi</strong></th>
        </tr>
        <tr>
            @foreach ($period as $date)
                <th style="background-color: #f2f2f2; font-weight: bold; border: 1px solid #000;"><strong>{{ $date->format('d') }}</strong></th>
            @endforeach
            <th style="background-color: #e0e0e0; font-weight: bold; border: 1px solid #000;"><strong>H</strong></th>
            <th style="background-color: #e0e0e0; font-weight: bold; border: 1px solid #000;"><strong>S</strong></th>
            <th style="background-color: #e0e0e0; font-weight: bold; border: 1px solid #000;"><strong>I</strong></th>
            <th style="background-color: #e0e0e0; font-weight: bold; border: 1px solid #000;"><strong>A</strong></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $index => $student)
            <tr>
                <td style="border: 1px solid #000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000;">{{ $student->name }}</td>
                @foreach ($period as $date)
                    @php
                        $dateString = $date->format('Y-m-d');
                        $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        $statusText = '';

                        switch ($status) {
                            case 'tepat_waktu': $statusText = 'H'; break;
                            case 'terlambat': $statusText = 'T'; break;
                            case 'izin': $statusText = 'I'; break;
                            case 'sakit': $statusText = 'S'; break;
                            case 'alpa': $statusText = 'A'; break;
                        }
                    @endphp
                    <td style="border: 1px solid #000;">{{ $statusText }}</td>
                @endforeach
                {{-- Sel Rekapitulasi --}}
                @php
                    $summary = $attendanceSummary[$student->id];
                @endphp
                <td style="border: 1px solid #000; font-weight: bold;">{{ $summary['hadir'] }}</td>
                <td style="border: 1px solid #000; font-weight: bold;">{{ $summary['sakit'] }}</td>
                <td style="border: 1px solid #000; font-weight: bold;">{{ $summary['izin'] }}</td>
                <td style="border: 1px solid #000; font-weight: bold;">{{ $summary['alpa'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

