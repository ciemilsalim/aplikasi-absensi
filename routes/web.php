<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\SettingController; // Controller baru
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\ReportController; // Controller baru

// Rute Publik
Route::get('/', [AttendanceController::class, 'showScanner'])->name('scanner');
Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
Route::get('/students-list', [AttendanceController::class, 'showStudents'])->name('students.list');

// Pengalihan setelah login
Route::get('/dashboard', function () {
    if (auth()->check() && auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('scanner');
})->middleware(['auth', 'verified'])->name('dashboard');

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
});

// Rute Profil Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
