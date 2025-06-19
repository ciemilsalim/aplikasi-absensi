<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\AdminMiddleware;

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

// Grup Rute untuk Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rute baru untuk halaman cetak QR
    Route::get('/students/qr', [StudentController::class, 'qr'])->name('students.qr');

    Route::resource('students', StudentController::class)->except(['show']);
});

// Rute Profil Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
