<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

echo "=== ALL TEACHERS ===\n";
foreach (DB::table('teachers')->get() as $t) {
    echo "ID: {$t->id} | Name: {$t->name} | NIP: {$t->nip}\n";
}
