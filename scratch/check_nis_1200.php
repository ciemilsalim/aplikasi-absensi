<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ATTENDANCES FOR STUDENTS NIS 1200 - 1228 ===\n";

$students1200 = DB::table('students')->whereBetween('nis', ['1200', '1228'])->orderBy('nis')->get();

foreach ($students1200 as $s) {
    $att = DB::table('attendances')->where('student_id', $s->id)->get();
    $subAtt = DB::table('subject_attendances')->where('student_id', $s->id)->get();
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | AttCount: {$att->count()} | SubAttCount: {$subAtt->count()}\n";
    if ($att->count() > 0) {
        foreach ($att as $a) {
            echo "   -> Att: Time={$a->attendance_time}, Status={$a->status}, AY={$a->academic_year_id}, Sem={$a->semester_id}\n";
        }
    }
}
