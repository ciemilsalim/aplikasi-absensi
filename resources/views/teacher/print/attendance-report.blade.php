<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran Kelas {{ $class->name }} - {{ $selectedDate->translatedFormat('F Y') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 20px;
            font-size: 10px;
            color: #333;
        }
        .header-container {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            height: 75px; /* Memberi ruang untuk logo */
        }
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 70px;
            height: auto;
        }
        .header-text h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .header-text p {
            margin: 2px 0;
            font-size: 12px;
        }
        .document-title {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .document-subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #666;
            padding: 5px;
            text-align: center;
            font-size: 9px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .student-name {
            text-align: left;
            width: 150px;
        }
        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #555;
            display: flex;
            justify-content: space-between;
        }
        .recap-header {
            background-color: #e0e0e0;
        }
        .recap-cell {
            font-weight: bold;
        }
        @media print {
            body {
                margin: 10px;
            }
            .footer {
                position: fixed;
                bottom: 10px;
                width: 95%;
            }
            th {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
            }
            .recap-header {
                 background-color: #e0e0e0 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="header-container">
        @if(isset($settings['app_logo']) && $settings['app_logo'] && file_exists(storage_path('app/public/' . $settings['app_logo'])))
            @php
                $logoPath = storage_path('app/public/' . $settings['app_logo']);
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoSrc = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . $logoData;
            @endphp
            <img src="{{ $logoSrc }}" alt="Logo Sekolah" class="logo">
        @endif
        <div class="header-text">
            <h1>{{ $settings['school_name'] ?? 'Nama Sekolah' }}</h1>
            <p>{{ $settings['school_address'] ?? 'Alamat Sekolah' }}</p>
        </div>
    </div>

    <p class="document-title">Laporan Kehadiran Siswa</p>
    <p class="document-subtitle">Kelas: {{ $class->name }} | Bulan: {{ $selectedDate->translatedFormat('F Y') }}</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2" class="student-name">Nama Siswa</th>
                <th colspan="{{ $period->count() }}">Tanggal</th>
                <th colspan="4" class="recap-header">Rekapitulasi</th>
            </tr>
            <tr>
                @foreach ($period as $date)
                    <th>{{ $date->format('d') }}</th>
                @endforeach
                <th class="recap-header">H</th>
                <th class="recap-header">S</th>
                <th class="recap-header">I</th>
                <th class="recap-header">A</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="student-name">{{ $student->name }}</td>
                    @foreach ($period as $date)
                        @php
                            $dateString = $date->format('Y-m-d');
                            $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                            $status = $attendanceRecord ? $attendanceRecord->status : null;
                            $statusText = '-';
                            switch ($status) {
                                case 'tepat_waktu': $statusText = 'H'; break;
                                case 'terlambat': $statusText = 'T'; break;
                                case 'izin': $statusText = 'I'; break;
                                case 'sakit': $statusText = 'S'; break;
                                case 'alpa': $statusText = 'A'; break;
                            }
                        @endphp
                        <td>{{ $statusText }}</td>
                    @endforeach
                    {{-- Sel Rekapitulasi --}}
                    @php
                        $summary = $attendanceSummary[$student->id];
                    @endphp
                    <td class="recap-cell">{{ $summary['hadir'] }}</td>
                    <td class="recap-cell">{{ $summary['sakit'] }}</td>
                    <td class="recap-cell">{{ $summary['izin'] }}</td>
                    <td class="recap-cell">{{ $summary['alpa'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $period->count() + 6 }}" style="text-align: center;">Tidak ada data siswa di kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Aplikasi Absensi v1.0</span>
        <span>Dicetak pada: {{ now()->translatedFormat('d F Y H:i:s') }}</span>
    </div>

</body>
</html>

