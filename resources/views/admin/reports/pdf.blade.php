<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        .header-table { width: 100%; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .school-info { text-align: center; }
        .school-info h1 { font-size: 18px; margin: 0; font-weight: bold; }
        .school-info p { font-size: 12px; margin: 2px 0; }
        .report-title { text-align: center; margin-top: 20px; margin-bottom: 20px; }
        .report-title h2 { font-size: 16px; margin: 0; text-decoration: underline; font-weight: bold; }
        .report-title p { margin: 2px 0; font-size: 12px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { border: 1px solid #999; padding: 6px; text-align: left; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { text-align: right; font-size: 9px; margin-top: 30px; color: #777; position: fixed; bottom: 0; right: 0; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                @endif
            </td>
            <td class="school-info">
                <h1>{{ $schoolName }}</h1>
                <p>{{ $schoolAddress }}</p>
            </td>
            <td style="width: 80px;"></td>
        </tr>
    </table>

    <div class="report-title">
        <h2>REKAPITULASI KEHADIRAN SISWA</h2>
        <p>Kelas: {{ $className }} | Periode: {{ $monthName }}</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th>Nama Siswa</th>
                <th style="width: 15%;">NIS</th>
                <th class="text-center" style="width: 10%;">Hadir</th>
                <th class="text-center" style="width: 10%;">Sakit</th>
                <th class="text-center" style="width: 10%;">Izin</th>
                <th class="text-center" style="width: 10%;">Alpa</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportData as $index => $data)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $data->name }}</td>
                    <td>{{ $data->nis }}</td>
                    <td class="text-center">{{ $data->hadir }}</td>
                    <td class="text-center">{{ $data->sakit }}</td>
                    <td class="text-center">{{ $data->izin }}</td>
                    <td class="text-center">{{ $data->alpa }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data kehadiran untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }}
    </div>
</body>
</html>
