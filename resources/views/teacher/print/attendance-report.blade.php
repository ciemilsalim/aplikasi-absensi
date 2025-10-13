<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Kehadiran - Kelas {{ $class->name }} - {{ $selectedDate->translatedFormat('F Y') }}</title>
    {{-- Mengimpor font Poppins dari Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif; /* Menggunakan font Poppins */
            margin: 20px;
            color: #333;
        }
        .header-container {
            display: flex;
            align-items: center;
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .logo {
            max-width: 75px;
            max-height: 75px;
            margin-right: 25px;
        }
        .kop-text {
            text-align: center;
            flex-grow: 1;
        }
        .kop-text h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .kop-text p {
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        .report-title {
            text-align: center;
            margin-bottom: 25px;
        }
        .report-title h2 {
            font-size: 16px;
            margin: 0;
            text-decoration: underline;
            font-weight: 600;
        }
        .report-info-container {
            margin-bottom: 20px;
            font-size: 13px;
        }
        .report-info {
            border-collapse: collapse;
        }
        .report-info td {
            border: none;
            padding: 4px 0;
        }
        .report-info td:first-child {
            padding-right: 10px;
            font-weight: 600;
        }
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 7px;
            text-align: center;
        }
        .main-table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .main-table td.student-name {
            text-align: left;
            padding-left: 10px;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            left: 20px;
            right: 20px;
            width: auto;
            font-size: 10px;
            color: #777;
        }
        .footer .app-name {
            float: left;
        }
        .footer .print-date {
            float: right;
        }

        @media print {
            body {
                margin: 0.5in;
            }
            .main-table th {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .footer {
                bottom: 0.25in;
                left: 0.5in;
                right: 0.5in;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="header-container">
        {{-- Tampilkan logo jika ada di pengaturan --}}
        @if(isset($settings['app_logo']) && $settings['app_logo'])
            <img src="{{ asset('storage/' . $settings['app_logo']) }}" alt="Logo Aplikasi" class="logo">
        @endif
        <div class="kop-text">
            <h1>{{ $settings['school_name'] ?? 'NAMA SEKOLAH' }}</h1>
            <p>{{ $settings['school_address'] ?? 'Alamat Sekolah' }}</p>
        </div>
    </div>

    <div class="report-title">
        <h2>LAPORAN KEHADIRAN SISWA</h2>
    </div>

    <div class="report-info-container">
        <table class="report-info">
            <tr>
                <td>Kelas</td>
                <td>: {{ $class->name }}</td>
            </tr>
            <tr>
                <td>Bulan</td>
                <td>: {{ $selectedDate->translatedFormat('F Y') }}</td>
            </tr>
             <tr>
                <td>Wali Kelas</td>
                <td>: {{ $class->homeroomTeacher->name ?? '-' }}</td>
            </tr>
        </table>
    </div>


    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Nama Siswa</th>
                <th colspan="{{ $period->count() }}">Tanggal</th>
            </tr>
            <tr>
                @foreach ($period as $date)
                    <th>{{ $date->format('d') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="student-name">{{ $student->name }}</td>
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
                        <td>{{ $statusText }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <span class="app-name">{{ $settings['app_name'] ?? config('app.name', 'Aplikasi Absensi') }}</span>
        <span class="print-date">Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</span>
    </div>


    <script type="text/javascript">
        // Otomatis membuka dialog print saat halaman dimuat
        window.onload = function() {
            window.print();
        }
    </script>

</body>
</html>

