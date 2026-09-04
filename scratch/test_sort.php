<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Semester;

$semesters = Semester::with('academicYear')->get()->sortBy([
    function ($a, $b) {
        $yearA = $a->academicYear?->name ?? '';
        $yearB = $b->academicYear?->name ?? '';
        return strcmp($yearA, $yearB);
    },
    function ($a, $b) {
        $semOrderA = str_contains(strtolower($a->name), 'ganjil') ? 1 : (str_contains(strtolower($a->name), 'genap') ? 2 : 3);
        $semOrderB = str_contains(strtolower($b->name), 'ganjil') ? 1 : (str_contains(strtolower($b->name), 'genap') ? 2 : 3);
        return $semOrderA <=> $semOrderB;
    }
])->values();

echo "=== SORTED SEMESTERS ===\n";
foreach ($semesters as $s) {
    echo "ID {$s->id}: TA. " . ($s->academicYear?->name ?? 'Tanpa Tahun') . " - {$s->name}\n";
}
