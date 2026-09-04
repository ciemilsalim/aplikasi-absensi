<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use App\Models\SchoolClass;

// Test with explicit academic_year_id on the relation
$activeYearId = 1;
$classYear1 = Teacher::find(1)->hasOne(SchoolClass::class, 'teacher_id')->where('school_classes.academic_year_id', 1)->first();
echo "Year 1 Homeroom: " . ($classYear1 ? "ID {$classYear1->id} - {$classYear1->name} (Year: {$classYear1->academic_year_id})" : "NONE") . "\n";

$activeYearId = 2;
$classYear2 = Teacher::find(1)->hasOne(SchoolClass::class, 'teacher_id')->where('school_classes.academic_year_id', 2)->first();
echo "Year 2 Homeroom: " . ($classYear2 ? "ID {$classYear2->id} - {$classYear2->name} (Year: {$classYear2->academic_year_id})" : "NONE") . "\n";
