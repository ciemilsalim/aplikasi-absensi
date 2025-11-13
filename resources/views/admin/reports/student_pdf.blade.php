<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Detail Kehadiran Siswa</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
        .header-table { width: 100%; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .school-info { text-align: center; }
        .school-info h1 { font-size: 18px; margin: 0; font-weight: bold; }
        .school-info p { font-size: 12px; margin: 2px 0; }
        .report-title { text-align: center; margin-top: 20px; margin-bottom: 15px; }
        .report-title h2 { font-size: 16px; margin: 0; text-decoration: underline; font-weight: bold; }
        .student-details { margin-bottom: 15px; }
        .student-details table { width: 50%; border-collapse: collapse; }
        .student-details td { padding: 2px 5px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { border: 1px solid #999; padding: 6px; text-align: left; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { text-align: right; font-size: 9px; margin-top: 30px; color: #777; position: fixed; bottom: 0; right: 0; }
        
        /* MODIFIKASI: CSS untuk blok tanda tangan */
        .signature-block {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid; /* Mencegah terpotong di halaman baru */
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
        }
        .signature-space {
            height: 60px; /* Ruang untuk tanda tangan */
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
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
        <h2>LAPORAN DETAIL KEHADIRAN SISWA</h2>
    </div>

    <div class="student-details">
        <table>
            <tr>
                <td style="width: 100px;"><strong>Nama Siswa</strong></td>
                <td>: {{ $student->name }}</td>
            </tr>
            <tr>
                <td><strong>NIS</strong></td>
                <td>: {{ $student->nis }}</td>
            </tr>
            <tr>
                <td><strong>Kelas</strong></td>
                <td>: {{ $student->schoolClass->name ?? '-' }}</td>
            </tr>
             <tr>
                <td><strong>Periode</strong></td>
                <td>: {{ $startDate }} - {{ $endDate }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Jam Masuk</th>
                <th class="text-center">Jam Pulang</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $index => $attendance)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $attendance->attendance_time->translatedFormat('d M Y') }}</td>
                    <td class="text-center">{{ in_array($attendance->status, ['izin', 'sakit', 'alpa']) ? '-' : $attendance->attendance_time->format('H:i:s') }}</td>
                    <td class="text-center">{{ $attendance->checkout_time ? $attendance->checkout_time->format('H:i:s') : '-' }}</td>
                    <td class="text-center" style="text-transform: capitalize;">{{ $attendance->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data kehadiran untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- MODIFIKASI: Blok Tanda Tangan -->
    <div class="signature-block">
        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    Kepala Sekolah
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $headmasterName }}</div>
                    {{-- MODIFIKASI: Tampilkan NIP Kepala Sekolah --}}
                    <div>NIP. {{ $headmasterNip }}</div>
                </td>
                <td>
                    {{-- Ganti 'Kota' dengan kota sekolah Anda & format tanggal --}}
                    Buol, {{ now()->translatedFormat('d F Y') }}<br> 
                    Wali Kelas
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $homeroomTeacherName }}</div>
                    {{-- MODIFIKASI: Tampilkan NIP Wali Kelas jika ada --}}
                    @if ($homeroomTeacherNip)
                        <div>NIP. {{ $homeroomTeacherNip }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <!-- Akhir Blok Tanda Tangan -->


    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }}
    </div>
</body>
</html>