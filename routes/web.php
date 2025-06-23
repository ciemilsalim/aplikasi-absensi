<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProfileController;
// Middleware
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ParentMiddleware;
use App\Http\Middleware\TeacherMiddleware;
use App\Http\Middleware\ScannerAccessMiddleware; // Impor middleware baru
// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
// Parent Controller
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\LeaveRequestController as ParentLeaveRequestController;
// Teacher Controller
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LeaveRequestController as TeacherLeaveRequestController;




/*
|--------------------------------------------------------------------------
| Rute Web
|--------------------------------------------------------------------------
*/

// == RUTE PUBLIK ==
// Dapat diakses oleh siapa saja tanpa perlu login.
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// == RUTE AUTENTIKASI & PENGALIHAN ==
// Rute-rute ini menangani logika setelah login.
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'admin')   { return redirect()->route('admin.dashboard'); }
    if ($user->role === 'parent')  { return redirect()->route('parent.dashboard'); }
    if ($user->role === 'teacher') { return redirect()->route('teacher.dashboard'); }
    return redirect()->route('welcome');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// == GRUP RUTE ADMIN ==
// Semua rute di sini dilindungi dan hanya bisa diakses oleh admin.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Rute Dasbor Utama (Menampilkan rekap kehadiran)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rute Laporan
    Route::get('/reports', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    
    // Rute Pengaturan
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Rute Manajemen Kelas (dipecah dari resource untuk kejelasan)
    Route::get('/classes', [SchoolClassController::class, 'index'])->name('classes.index');
    Route::post('/classes', [SchoolClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{class}/edit', [SchoolClassController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{class}', [SchoolClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{class}', [SchoolClassController::class, 'destroy'])->name('classes.destroy');
    Route::get('classes/{class}/assign', [SchoolClassController::class, 'showAssignForm'])->name('classes.assign');
    Route::post('classes/assign-students', [SchoolClassController::class, 'assignStudents'])->name('classes.assign.students');
    
    // Rute Manajemen Siswa
    Route::get('/students/import', [StudentController::class, 'showImportForm'])->name('students.import.form');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/qr', [StudentController::class, 'qr'])->name('students.qr');
    Route::resource('students', StudentController::class)->parameters(['students' => 'student']);

    // Rute Manajemen Ortu
    Route::get('/parents/import', [ParentController::class, 'showImportForm'])->name('parents.import.form');
    Route::post('/parents/import', [ParentController::class, 'import'])->name('parents.import');
    Route::resource('parents', ParentController::class)->parameters(['parents' => 'parent']);

    // Rute Manajemen Guru
    Route::get('/teachers/import', [TeacherController::class, 'showImportForm'])->name('teachers.import.form');
    Route::post('/teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::resource('teachers', TeacherController::class)->parameters(['teachers' => 'teacher']);

    Route::get('/leave-requests', [AdminLeaveRequestController::class, 'index'])->name('leave_requests.index');
    Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveRequestController::class, 'reject'])->name('leave_requests.reject');
});

// GRUP RUTE ORANG TUA
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
    Route::resource('leave-requests', ParentLeaveRequestController::class)->only(['index', 'create', 'store']);
});

// == GRUP RUTE GURU ==
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    // Rute BARU untuk menandai kehadiran oleh wali kelas
    Route::post('/mark-attendance', [TeacherDashboardController::class, 'markAttendance'])->name('mark.attendance');

    // Rute Pengajuan Izin untuk Wali Kelas (BARU)
    Route::get('/leave-requests', [TeacherLeaveRequestController::class, 'index'])->name('leave_requests.index');
    Route::post('/leave-requests/{leaveRequest}/approve', [TeacherLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [TeacherLeaveRequestController::class, 'reject'])->name('leave_requests.reject');
});

// == GRUP RUTE UNTUK AKSES PEMINDAI (BARU) ==
Route::middleware(['auth', 'scanner.access'])->group(function () {
    Route::get('/scanner', [AttendanceController::class, 'showScanner'])->name('scanner');
    Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
});

// Mengimpor rute-rute autentikasi bawaan
require __DIR__.'/auth.php';
