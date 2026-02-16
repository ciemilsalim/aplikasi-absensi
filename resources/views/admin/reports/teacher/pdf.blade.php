<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi Guru</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .school-info { text-align: center; }
        .school-info h1 { font-size: 18px; margin: 0; font-weight: bold; text-transform: uppercase; }
        .school-info p { font-size: 12px; margin: 2px 0; }
        
        .report-title { text-align: center; margin-bottom: 20px; }
        .report-title h2 { font-size: 16px; margin: 0; text-decoration: underline; font-weight: bold; text-transform: uppercase; }
        .report-title h3 { font-size: 14px; margin: 5px 0; font-weight: normal; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid black; padding: 5px; text-align: center; }
        table.data th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
        
        .footer { margin-top: 30px; float: right; width: 250px; text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 80px; text-align: center;">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                @endif
            </td>
            <td class="school-info">
                <h1>{{ $schoolName }}</h1>
                <p>{{ $schoolAddress }}</p>
            </td>
            <td style="width: 80px;"></td> <!-- Spacer for centering -->
        </tr>
    </table>

    <div class="report-title">
        <h2>REKAPITULASI ABSENSI GURU</h2>
        <h3>Periode: {{ $monthName }}</h3>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th class="text-left">Nama Guru</th>
                <th class="text-left">NIP</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
                <th>% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recap as $index => $teacher)
                @php
                    $totalDays = count($dates);
                    $attendancePercentage = $totalDays > 0 ? round(($teacher['hadir'] / $totalDays) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $teacher['name'] }}</td>
                    <td class="text-left">{{ $teacher['nip'] ?? '-' }}</td>
                    <td>{{ $teacher['hadir'] }}</td>
                    <td>{{ $teacher['terlambat'] }}</td>
                    <td>{{ $teacher['sakit'] }}</td>
                    <td>{{ $teacher['izin'] }}</td>
                    <td>{{ $teacher['alpa'] }}</td>
                    <td>{{ $attendancePercentage }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Badung, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        <p>Kepala Sekolah,</p>
        <br><br><br><br>
        <p style="font-weight: bold; text-decoration: underline;">{{ $headmasterName }}</p>
        <p>NIP. {{ $headmasterNip }}</p>
    </div>
</body>
</html>
