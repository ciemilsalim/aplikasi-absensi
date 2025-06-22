<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\WelcomeController; // Controller baru
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\SettingController; // Controller baru
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\ReportController; // Controller baru
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Middleware\ParentMiddleware;

// == RUTE PUBLIK ==
Route::get('/', [WelcomeController::class, 'index'])->name('welcome'); // Halaman utama baru
Route::get('/scanner', [AttendanceController::class, 'showScanner'])->name('scanner'); // Halaman pemindai
Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');


// == RUTE AUTENTIKASI & PENGALIHAN ==
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'admin') { return redirect()->route('admin.dashboard'); }
    if ($user->role === 'parent') { return redirect()->route('parent.dashboard'); }
    return redirect()->route('welcome');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// GRUP RUTE ADMIN
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rute Pengaturan BARU
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/students/import', [StudentController::class, 'showImportForm'])->name('students.import.form');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/qr', [StudentController::class, 'qr'])->name('students.qr');
    Route::resource('students', StudentController::class)->except(['show']);

    // Rute Manajemen Kelas BARU
    Route::get('classes/{school_class}/assign', [SchoolClassController::class, 'showAssignForm'])->name('classes.assign');
    Route::post('classes/assign-students', [SchoolClassController::class, 'assignStudents'])->name('classes.assign.students');
    Route::resource('classes', SchoolClassController::class);

    // Rute Laporan BARU
    Route::get('/reports', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');

    // Rute Manajemen Orang Tua BARU
    Route::resource('parents', ParentController::class);
});

// GRUP RUTE ORANG TUA (BARU)
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
});


// Rute Profil Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
