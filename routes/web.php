<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;

// Middleware
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ParentMiddleware;
use App\Http\Middleware\TeacherMiddleware;
use App\Http\Middleware\ScannerAccessMiddleware;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\AdminChatController;

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
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/about', [AboutController::class, 'index'])->name('about');

// == RUTE AUTENTIKASI & PENGALIHAN ==
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'admin')   { return redirect()->route('admin.dashboard'); }
    if ($user->role === 'parent')  { return redirect()->route('parent.dashboard'); }
    if ($user->role === 'teacher') { return redirect()->route('teacher.dashboard'); }
    return redirect()->route('welcome');
})->middleware(['auth', 'verified'])->name('dashboard');

// == GRUP RUTE YANG MEMERLUKAN LOGIN ==
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // PERBAIKAN: Rute spesifik diletakkan SEBELUM rute dengan parameter
    // Rute Obrolan Admin <-> Ortu (untuk Orang Tua)
    Route::get('/chat/admin', [ChatController::class, 'showAdminChat'])->name('chat.admin');
    Route::post('/chat/admin/{conversation}/messages', [ChatController::class, 'storeAdminMessage'])->name('chat.store_admin_message');

    // Rute Obrolan Guru <-> Ortu
    Route::get('/chat/{conversation?}', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.store_message');

    // Rute Notifikasi Internal
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

// == GRUP RUTE UNTUK AKSES PEMINDAI ==
Route::middleware(['auth', 'scanner.access'])->group(function () {
    Route::get('/scanner', [AttendanceController::class, 'showScanner'])->name('scanner');
    Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
});

// == GRUP RUTE ADMIN ==
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Laporan
    Route::get('/reports', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    
    // Pengaturan
    Route::get('/settings/identity', [SettingController::class, 'identity'])->name('settings.identity');
    Route::get('/settings/appearance', [SettingController::class, 'appearance'])->name('settings.appearance');
    Route::get('/settings/attendance', [SettingController::class, 'attendance'])->name('settings.attendance');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Pengajuan Izin
    Route::get('/leave-requests', [AdminLeaveRequestController::class, 'index'])->name('leave_requests.index');
    Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveRequestController::class, 'reject'])->name('leave_requests.reject');

    // Manajemen Data (CRUD)
    Route::get('classes/{school_class}/assign', [SchoolClassController::class, 'showAssignForm'])->name('classes.assign');
    Route::post('classes/assign-students', [SchoolClassController::class, 'assignStudents'])->name('classes.assign.students');
    Route::resource('classes', SchoolClassController::class);
    Route::resource('students', StudentController::class)->except(['show']);
    Route::resource('parents', ParentController::class);
    Route::resource('teachers', TeacherController::class);
    Route::resource('announcements', AnnouncementController::class);
    
    // Impor Data
    Route::get('/students/import', [StudentController::class, 'showImportForm'])->name('students.import.form');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/parents/import', [ParentController::class, 'showImportForm'])->name('parents.import.form');
    Route::post('/parents/import', [ParentController::class, 'import'])->name('parents.import');
    Route::get('/teachers/import', [TeacherController::class, 'showImportForm'])->name('teachers.import.form');
    Route::post('/teachers/import', [TeacherController::class, 'import'])->name('teachers.import');

    // Status Online
    Route::get('/parents/online-status', [ParentController::class, 'getOnlineStatus'])->name('parents.online_status');
    Route::get('/teachers/online-status', [TeacherController::class, 'getOnlineStatus'])->name('teachers.online_status');

    // Cetak QR
    Route::get('/students/qr', [StudentController::class, 'qr'])->name('students.qr');

    // Backup
    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup/create', [BackupController::class, 'create'])->name('backup.create');
    Route::get('/backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('/backup/delete/{filename}', [BackupController::class, 'delete'])->name('backup.delete');
    
    // Obrolan Admin
    Route::get('/chat/{selectedParent?}', [AdminChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/conversations/{conversation}', [AdminChatController::class, 'storeMessage'])->name('chat.store_message');
});

// == GRUP RUTE ORANG TUA ==
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
    Route::resource('leave-requests', ParentLeaveRequestController::class)->only(['index', 'create', 'store']);
});

// == GRUP RUTE GURU ==
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::post('/mark-attendance', [TeacherDashboardController::class, 'markAttendance'])->name('mark.attendance');
    Route::get('/leave-requests', [TeacherLeaveRequestController::class, 'index'])->name('leave_requests.index');
    Route::post('/leave-requests/{leaveRequest}/approve', [TeacherLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [TeacherLeaveRequestController::class, 'reject'])->name('leave_requests.reject');
});

require __DIR__.'/auth.php';
