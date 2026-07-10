<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = App\Models\Semester::where('is_active', true)->first();
if($s) {
    $tables = ['school_classes', 'teaching_assignments', 'schedules', 'extracurriculars'];
    foreach($tables as $t) {
        Illuminate\Support\Facades\DB::table($t)
            ->whereNull('semester_id')
            ->update(['semester_id' => $s->id, 'academic_year_id' => $s->academic_year_id]);
    }
    
    $students = Illuminate\Support\Facades\DB::table('students')->whereNotNull('school_class_id')->get();
    foreach($students as $st) {
        Illuminate\Support\Facades\DB::table('class_student')->updateOrInsert(
            ['student_id' => $st->id, 'semester_id' => $s->id],
            ['school_class_id' => $st->school_class_id, 'academic_year_id' => $s->academic_year_id, 'created_at' => now(), 'updated_at' => now()]
        );
    }
    echo "Backfill done! Processed " . count($students) . " students.\n";
} else {
    echo "No active semester found.\n";
}
