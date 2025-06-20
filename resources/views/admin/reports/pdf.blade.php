<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { text-align: right; font-size: 10px; margin-top: 20px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Laporan Kehadiran Siswa</h1>
            <p><strong>Kelas:</strong> {{ $className }}</p>
            <p><strong>Periode:</strong> {{ $monthName }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th>Nama Siswa</th>
                    <th width="15%">NIS</th>
                    <th class="text-center" width="12%">Jumlah Hadir</th>
                    <th class="text-center" width="12%">Tepat Waktu</th>
                    <th class="text-center" width="12%">Terlambat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reportData as $index => $data)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $data->name }}</td>
                        <td>{{ $data->nis }}</td>
                        <td class="text-center">{{ $data->hadir }}</td>
                        <td class="text-center">{{ $data->tepat_waktu }}</td>
                        <td class="text-center">{{ $data->terlambat }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data kehadiran untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            <p>Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }}</p>
        </div>
    </div>
</body>
</html>
