<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran Triwulan {{ $trimester }} Tahun {{ $year }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .header { display: flex; align-items: center; justify-content: center; padding-bottom: 5px; margin-bottom: 10px; }
        .header img { height: 60px; margin-right: 15px; }
        .header .text-center { text-align: center; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 11px; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 9px; vertical-align: middle; }
        th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
        .footer { margin-top: 30px; display: table; width: 100%; }
        .footer-left { display: table-cell; text-align: center; width: 50%; }
        .footer-right { display: table-cell; text-align: center; width: 50%; }
        .signature-line { font-weight: bold; text-decoration: underline; margin-top: 50px; }
    </style>
</head>
<body>

    <div class="header">
        <table style="border: none; width: 100%;">
            <tr>
                <td style="border: none; width: 15%; text-align: center;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo" style="height: 60px;">
                    @endif
                </td>
                <td style="border: none; width: 70%; text-align: center;">
                    <h1 style="margin: 0;">{{ $schoolName }}</h1>
                    <p style="margin: 2px 0;">{{ $schoolAddress }}</p>
                </td>
                <td style="border: none; width: 15%;"></td>
            </tr>
        </table>
        <div style="border-bottom: 2px solid #000; margin-top: 5px;"></div>
    </div>

    <div class="title">FORM VERIFIKASI KOMITMEN PENDIDIKAN - TRIWULAN {{ $trimester }} TAHUN {{ $year }}</div>
    <div style="margin-bottom: 5px; font-weight: bold;">KELAS: {{ $className }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">NO</th>
                <th rowspan="2" style="width: 8%;">NIK SISWA</th>
                <th rowspan="2" style="width: 8%;">NISN</th>
                <th rowspan="2" style="width: 15%;">NAMA SISWA</th>
                <th rowspan="2" style="width: 5%;">KELAS</th>
                @foreach($months as $m)
                    <th colspan="5">{{ $trimesterMap[$m]['name'] }}<br><span style="font-weight: normal; font-size: 8px;">Hari Efektif: {{ $trimesterMap[$m]['effective_days'] }}</span></th>
                @endforeach
                <th rowspan="2" style="width: 5%;">KET</th>
            </tr>
            <tr>
                @foreach($months as $m)
                    <th style="width: 4%;">ALPA</th>
                    <th style="width: 4%;">IZIN</th>
                    <th style="width: 4%;">SAKIT</th>
                    <th style="width: 4%;">JML</th>
                    <th style="width: 4%;">%</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>-</td>
                    <td>{{ $student->nis ?? '-' }}</td>
                    <td class="text-left">{{ $student->name }}</td>
                    <td>{{ explode(' ', $className)[0] ?? $className }}</td>
                    
                    @foreach($months as $m)
                        @php $mData = $student->monthly_data[$m]; @endphp
                        <td>{{ $mData['alpa'] == 0 ? '-' : $mData['alpa'] }}</td>
                        <td>{{ $mData['izin'] == 0 ? '-' : $mData['izin'] }}</td>
                        <td>{{ $mData['sakit'] == 0 ? '-' : $mData['sakit'] }}</td>
                        <td style="font-weight: bold;">{{ $mData['jml'] == 0 ? '-' : $mData['jml'] }}</td>
                        <td style="font-weight: bold;">{{ $mData['persen'] }}</td>
                    @endforeach
                    
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + (count($months)*5) + 1 }}">Tidak ada data siswa untuk kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-left">
            Mengetahui,<br>
            Kepala Sekolah<br>
            <br><br><br>
            <div class="signature-line">{{ $headmasterName ?? '_________________________' }}</div>
            <div>NIP. {{ $headmasterNip ?? '_________________________' }}</div>
        </div>
        <div class="footer-right">
            .................., {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
            Wali Kelas {{ $className }}<br>
            <br><br><br>
            <div class="signature-line">{{ $homeroomTeacherName ?? '_________________________' }}</div>
            <div>NIP. {{ $homeroomTeacherNip ?? '_________________________' }}</div>
        </div>
    </div>

    <div style="font-size: 8px; margin-top: 20px; color: #666; text-align: right;">
        * Rekap dicetak otomatis oleh Sistem SIASEK pada {{ $printDate }}. Oleh: {{ $userRole }}
    </div>

</body>
</html>
