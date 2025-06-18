<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\ProfileController; // Ditambahkan
use App\Http\Middleware\AdminMiddleware;

// Rute Publik (dapat diakses siapa saja)
Route::get('/', [AttendanceController::class, 'showScanner'])->name('scanner');
Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
Route::get('/students', [AttendanceController::class, 'showStudents'])->name('students.list');


// Rute Dasbor bawaan Breeze
Route::get('/dashboard', function () {
    // Arahkan ke dasbor admin jika role-nya admin, jika tidak, arahkan ke scanner
    if (auth()->check() && auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    // Jika bukan admin atau belum login, arahkan ke halaman scanner
    return redirect()->route('scanner');
})->middleware(['auth', 'verified'])->name('dashboard');


// Grup Rute untuk Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dasbor Admin
    Route::get('/dashboard', [StudentController::class, 'index'])->name('dashboard');
    
    // Rute untuk mengelola siswa (CRUD)
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
});

// Rute Profil Pengguna (Ditambahkan untuk memperbaiki error)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Mengimpor rute autentikasi dari Breeze
require __DIR__.'/auth.php';
