<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$nis9A = [
    '1200', '1201', '1202', '1203', '1204', '1205', '1206', '1207', '1208', '1209',
    '1210', '1211', '1212', '1213', '1214', '1215', '1216', '1217', '1218', '1219',
    '1220', '1221', '1222', '1223', '1224', '1225', '1226', '1227', '1228'
];

$names9A = [
    'ALINCHY VIONETHA ARSYAD', 'ANDHINI SAFITRI SULEMAN', 'ANGEL CHRISTY TAMAUKA',
    'Aprilia Nurfadila S. Hasan', 'ARGA HIDAYAH AR. USMAN', 'DAFA APRILIANTO S.KOTAE',
    'DIVA SALSABILA M.', 'FAIZULLAH ISLAMAY PUTRA RASYID', 'FANI RISKI FADILA I. KOROMPOT',
    'FAUZIA NUR AZIZAH', 'FAUZIA NUR HIDAYAH', 'JUWITA RAHMA S. LAUNA', 'Kenzi Kolibu',
    'M. ALIF NURUL AZMI', 'MOH. DANI J. MANTO', 'MOH. FATHUL SYAWAL', 'Moh.Fadli R.Adam',
    'MOHAMMAD AXEL', 'MUH. ALIF ZULKIFLY I. BAINGAN', 'Najwa Dayani', 'NAZAR RANTELILING',
    'NUR JANNA MADJID', 'NUR RAHMA SLAMET', 'RAFHA ADITYA Y. SULEMAN', 'Rehan Manggale',
    'SANDRINA S. MAR\'UNA', 'SIFAQ MA\'ARIF', 'SYAHIRA RAHMADANI', 'Velyca Gracia Lande'
];

$students = DB::table('students')
    ->where(function($q) use ($nis9A, $names9A) {
        $q->whereIn('nis', $nis9A)
          ->orWhereIn('name', $names9A)
          ->orWhereBetween('nis', ['1200', '1228']);
    })
    ->where('created_at', '<', '2026-07-01')
    ->orderBy('nis')
    ->get();

echo "Found 9A students count: " . $students->count() . "\n";
foreach ($students as $s) {
    echo "StID: {$s->id} | NIS: {$s->nis} | Name: {$s->name}\n";
}
