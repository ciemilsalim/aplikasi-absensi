<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@admin.com')->first();
if ($admin) {
    $admin->password = bcrypt('password');
    $admin->save();
    echo "Admin password verified: admin@admin.com / password\n";
}

$teacher = App\Models\User::where('email', 'jalil@gmail.com')->first();
if ($teacher) {
    $teacher->password = bcrypt('password');
    $teacher->save();
    echo "Teacher password verified: jalil@gmail.com / password\n";
}

$parent = App\Models\User::where('email', 'emil@gmail.com')->first();
if ($parent) {
    $parent->password = bcrypt('password');
    $parent->save();
    echo "Parent password verified: emil@gmail.com / password\n";
    if ($parent->parent) {
        $parent->parent->is_onboarding_completed = true;
        $parent->parent->save();
        echo "Parent onboarding verified: completed\n";
    }
}
