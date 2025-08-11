<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Siswa Tidak Absen Pulang</title>
    <style>
        @page {
            size: A4;
            margin: 25px;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-container {
            display: table;
            width: 100%;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .logo {
            display: table-cell;
            width: 90px;
            vertical-align: middle;
        }
        .logo img {
            width: 75px;
            height: auto;
        }
        .school-info {
            display: table-cell;
            vertical-align: middle;
        }
        .school-info h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #0d6efd;
        }
        .school-info p {
            margin: 0;
            font-size: 12px;
            color: #555;
        }
        .report-title h2 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .report-title p {
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 12px;
        }
        main {
            padding-bottom: 60px; 
        }
        table {
            width: 100%;
            border-collapse: collapse;
            /* PERUBAHAN: Menghapus table-layout: fixed agar lebar kolom otomatis */
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        thead th {
            background-color: #e9ecef;
            color: #212529;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            border-bottom: 2px solid #adb5bd;
            border-top: 1px solid #dee2e6;
        }
        /* PERUBAHAN: Menambahkan class untuk kontrol kolom */
        .text-center {
            text-align: center;
        }
        .no-wrap {
            white-space: nowrap; /* Mencegah teks pindah baris */
        }
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: #6c757d;
        }
        .footer {
            position: fixed;
            bottom: -25px;
            left: 0px;
            right: 0px;
            height: 50px;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #ddd;
            padding: 10px 25px 0 25px;
            box-sizing: border-box;
            background-color: white;
        }
        .footer .page-info {
            float: right;
        }
        .footer .app-info {
            float: left;
        }
    </style>
</head>
<body>

    <footer class="footer">
        <div class="app-info">
            {{ $appName }} | Dicetak oleh: {{ $userRole }}
        </div>
        <div class="page-info">
            Tanggal Cetak: {{ $printDate }}
        </div>
    </footer>

    <header class="header-container">
        <div class="logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo Sekolah">
            @endif
        </div>
        <div class="school-info">
            <h1>{{ strtoupper($schoolName ?? 'NAMA SEKOLAH') }}</h1>
            <p>{{ $schoolAddress ?? 'Alamat Sekolah' }}</p>
        </div>
    </header>

    <main>
        <div class="report-title">
            <h2>LAPORAN SISWA TIDAK ABSEN PULANG</h2>
            <p>Periode: <strong>{{ $startDate }} s/d {{ $endDate }}</strong></p>
        </div>

        <table>
            <thead>
                <tr>
                    <!-- PERUBAHAN: Menghapus style inline dan menggunakan class -->
                    <th class="text-center no-wrap">No</th>
                    <th>Nama Siswa</th>
                    <th class="no-wrap">NIS</th>
                    <th>Kelas</th>
                    <th class="no-wrap">Tanggal</th>
                    <th class="text-center no-wrap">Jam Masuk</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                    <tr>
                        <td class="text-center no-wrap">{{ $loop->iteration }}</td>
                        <td>{{ $attendance->student->name ?? 'N/A' }}</td>
                        <td class="no-wrap">{{ $attendance->student->nis ?? '-' }}</td>
                        <td>{{ $attendance->student->schoolClass->name ?? 'Belum ada kelas' }}</td>
                        <td class="no-wrap">{{ \Carbon\Carbon::parse($attendance->attendance_time)->translatedFormat('d M Y') }}</td>
                        <td class="text-center no-wrap">{{ \Carbon\Carbon::parse($attendance->attendance_time)->format('H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="no-data">Tidak ada data siswa yang tidak absen pulang pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>

</body>
</html>
