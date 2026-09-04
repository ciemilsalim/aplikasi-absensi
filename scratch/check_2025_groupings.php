<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$all2025Students = DB::table('students')
    ->where('created_at', '<', '2026-07-01')
    ->where('nis', 'not like', 'TEST%')
    ->orderBy('id')
    ->get();

echo "Total 2025/2026 eligible students: " . $all2025Students->count() . "\n\n";

// Let's inspect their groupings
$groups = [
    'Cohort 9A (NIS 1200-1228)' => [],
    'Cohort 2024xxxx (NIS 20240001-20240050)' => [],
    'Cohort 7A / Early 2025 (NIS 1001-1401)' => [],
    'Cohort 7C / July 2025 (NIS 011/012/312)' => [],
    'Cohort X/XI 2025xxxx (NIS 20250101-20250310)' => [],
    'Others' => [],
];

foreach ($all2025Students as $s) {
    if (is_numeric($s->nis) && $s->nis >= 1200 && $s->nis <= 1228) {
        $groups['Cohort 9A (NIS 1200-1228)'][] = $s;
    } elseif (str_starts_with($s->nis, '2024')) {
        $groups['Cohort 2024xxxx (NIS 20240001-20240050)'][] = $s;
    } elseif (in_array($s->id, [1,2,3,4,5,6,43,44,45,72])) {
        $groups['Cohort 7A / Early 2025 (NIS 1001-1401)'][] = $s;
    } elseif (in_array($s->id, range(96, 124))) {
        $groups['Cohort 7C / July 2025 (NIS 011/012/312)'][] = $s;
    } elseif (str_starts_with($s->nis, '20250')) {
        $groups['Cohort X/XI 2025xxxx (NIS 20250101-20250310)'][] = $s;
    } else {
        $groups['Others'][] = $s;
    }
}

foreach ($groups as $name => $list) {
    echo "=== {$name} (Count: " . count($list) . ") ===\n";
    foreach (array_slice($list, 0, 3) as $s) {
        echo "  - StID: {$s->id} | NIS: {$s->nis} | {$s->name}\n";
    }
    if (count($list) > 3) {
        echo "  ... and " . (count($list) - 3) . " more.\n";
    }
    echo "\n";
}
