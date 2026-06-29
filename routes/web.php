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
use App\Http\Controllers\Admin\UserController; // Controller baru


// Parent Controller
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\LeaveRequestController as ParentLeaveRequestController;

// Teacher Controller
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LeaveRequestController as TeacherLeaveRequestController;
use App\Http\Controllers\Teacher\SubjectAttendanceController;

/* |-------------------------------------------------------------------------- | Rute Web |-------------------------------------------------------------------------- */

// == RUTE PUBLIK ==
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::view('/offline', 'offline')->name('offline'); // Endpoint PWA
Route::get('/sso/login', [\App\Http\Controllers\Auth\SsoLoginController::class, 'login'])->name('sso.login');

// == RUTE AUTENTIKASI & PENGALIHAN ==
Route::get('/dashboard', function () {
    $user = auth()->user();
    if (in_array($user->role, ['admin', 'operator'])) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->role === 'parent') {
        return redirect()->route('parent.dashboard');
    }
    if ($user->role === 'teacher') {
        return redirect()->route('teacher.dashboard');
    }
    return redirect()->route('welcome');
})->middleware(['auth', 'verified'])->name('dashboard');

// == GRUP RUTE YANG MEMERLUKAN LOGIN ==
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rute Obrolan Admin <-> Ortu (untuk Orang Tua)
    Route::get('/chat/admin', [ChatController::class, 'showAdminChat'])->name('chat.admin');
    Route::post('/chat/admin/{conversation}/messages', [ChatController::class, 'storeAdminMessage'])->name('chat.store_admin_message');

    // Rute Obrolan Guru <-> Ortu
    Route::get('/chat/{conversation?}', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.store_message');

    // Rute Notifikasi Internal
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // == RUTE BARU UNTUK IZIN KELUAR/KEMBALI ==
    Route::get('/permit-scanner', [\App\Http\Controllers\PermitController::class, 'showScanner'])->name('permit.scanner');
    Route::post('/permit-scanner/store', [\App\Http\Controllers\PermitController::class, 'storePermit'])->name('permit.store');
    // =========================================
});

// == GRUP RUTE UNTUK AKSES PEMINDAI ==
Route::middleware(['auth', 'scanner.access'])->group(function () {
    Route::get('/scanner', [AttendanceController::class, 'showScanner'])->name('scanner');
    Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
    // Baru: Menyimpan pola wajah (descriptor) siswa dari scanner
    Route::post('/attendance/save-student-descriptor', [AttendanceController::class, 'saveStudentDescriptor'])->name('attendance.save_descriptor');
});

// == GRUP RUTE ADMIN ==
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Rute yang bisa diakses oleh Admin & Operator
    Route::middleware(['role:admin,operator'])->group(
        function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/reports', [ReportController::class, 'create'])->name('reports.create');
            Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
            Route::get('/reports/charts', [ReportController::class, 'charts'])->name('reports.charts');
            Route::post('/reports/charts/data', [ReportController::class, 'chartData'])->name('reports.charts.data');
        }
    );

    // Rute yang HANYA bisa diakses oleh Admin (Tetap Aktif)
    Route::middleware(['role:admin'])->group(
        function () {
            // Pengajuan Izin
            Route::get('/leave-requests', [AdminLeaveRequestController::class, 'index'])->name('leave_requests.index');
            Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
            Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveRequestController::class, 'reject'])->name('leave_requests.reject');

            // Obrolan Admin
            Route::get('/chat/{selectedParent?}', [AdminChatController::class, 'index'])->name('chat.index');
            Route::post('/chat/conversations/{conversation}', [AdminChatController::class, 'storeMessage'])->name('chat.store_message');

            // Laporan Guru
            Route::get('reports/teacher', [\App\Http\Controllers\Admin\TeacherReportController::class, 'index'])->name('reports.teacher.index');
            Route::get('reports/teacher/print', [\App\Http\Controllers\Admin\TeacherReportController::class, 'print'])->name('reports.teacher.print');
        }
    );

    // Rute yang HANYA bisa diakses oleh Admin dan DIREDIRECT karena terduplikasi di SIPADA
    Route::middleware(['role:admin', 'sipada.redirect'])->group(
        function () {
            // Pengaturan
            Route::get('/settings/identity', [SettingController::class, 'identity'])->name('settings.identity');
            Route::get('/settings/appearance', [SettingController::class, 'appearance'])->name('settings.appearance');
            Route::get('/settings/attendance', [SettingController::class, 'attendance'])->name('settings.attendance');
            Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

            // Manajemen Data (CRUD)
            Route::get('classes/{school_class}/assign', [SchoolClassController::class, 'showAssignForm'])->name('classes.assign');
            Route::post('classes/assign-students', [SchoolClassController::class, 'assignStudents'])->name('classes.assign.students');
            Route::resource('classes', SchoolClassController::class)->except(['show']);
            Route::resource('students', StudentController::class)->except(['show']);
            Route::resource('parents', ParentController::class)->except(['show']);
            Route::resource('teachers', TeacherController::class)->except(['show']);
            Route::resource('announcements', AnnouncementController::class);
            Route::resource('users', UserController::class)->except(['show']);
            Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class)->except(['show']);
            Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk_destroy');
            Route::post('students/bulk-destroy', [StudentController::class, 'bulkDestroy'])->name('students.bulk_destroy');
            Route::post('students/bulk-promote', [StudentController::class, 'bulkPromote'])->name('students.bulk_promote');

            Route::get('classes/{school_class}/assign-teacher', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'index'])->name('classes.assign_teacher');
            Route::post('classes/{school_class}/assign-teacher', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'store'])->name('classes.store_teacher_assignment');

            // Kurikulum
            Route::post('calendars/import', [\App\Http\Controllers\Admin\CalendarController::class, 'import'])->name('calendars.import');
            Route::get('calendars/list', [\App\Http\Controllers\Admin\CalendarController::class, 'list'])->name('calendars.list');
            Route::resource('calendars', \App\Http\Controllers\Admin\CalendarController::class)->except(['show', 'edit', 'create']);

            // Jadwal Pelajaran (Dipindahkan ke Kurikulum)
            Route::get('schedules', [\App\Http\Controllers\Admin\ScheduleController::class, 'index'])->name('schedules.index');
            Route::get('schedules/{school_class}', [\App\Http\Controllers\Admin\ScheduleController::class, 'show'])->name('schedules.show');
            Route::post('schedules/{school_class}', [\App\Http\Controllers\Admin\ScheduleController::class, 'store'])->name('schedules.store');
            Route::delete('schedules/{schedule}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroy'])->name('schedules.destroy');

            // Impor Data (Contoh, jika ada)
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

            // Manajemen Tahun Ajaran & Semester
            Route::get('/academic-periods', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'index'])->name('academic-periods.index');
            Route::post('/academic-periods/year', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'storeYear'])->name('academic-periods.year.store');
            Route::post('/academic-periods/semester', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'storeSemester'])->name('academic-periods.semester.store');
            Route::post('/academic-periods/year/{id}/activate', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'activateYear'])->name('academic-periods.year.activate');
            Route::post('/academic-periods/semester/{id}/activate', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'activateSemester'])->name('academic-periods.semester.activate');
            Route::delete('/academic-periods/year/{id}', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'destroyYear'])->name('academic-periods.year.destroy');
            Route::delete('/academic-periods/semester/{id}', [\App\Http\Controllers\Admin\AcademicPeriodController::class, 'destroySemester'])->name('academic-periods.semester.destroy');

            // Manajemen Ekstrakurikuler
            Route::resource('extracurriculars', \App\Http\Controllers\Admin\ExtracurricularController::class);
            Route::get('extracurriculars/{extracurricular}/students', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'students'])->name('extracurriculars.students');
            Route::post('extracurriculars/{extracurricular}/students', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'assignStudents'])->name('extracurriculars.assign_students');
            Route::delete('extracurriculars/{extracurricular}/students/{student}', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'removeStudent'])->name('extracurriculars.remove_student');
        }
    );
});

// == GRUP RUTE ORANG TUA ==
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
    Route::resource('leave-requests', ParentLeaveRequestController::class)->only(['index', 'create', 'store']);
});

// == GRUP RUTE GURU ==
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    Route::post('/mark-attendance', [TeacherDashboardController::class, 'updateAttendance'])->name('mark.attendance');

    Route::get('/attendance-history', [TeacherDashboardController::class, 'showAttendanceHistory'])->name('attendance.history');
    Route::post('/attendance-history/update', [TeacherDashboardController::class, 'updateAttendance'])->name('attendance.update');
    Route::get('/attendance/print', [TeacherDashboardController::class, 'printAttendance'])->name('attendance.print');
    
    // Rute cetak rekap triwulan guru
    Route::get('/attendance/print-trimester', [TeacherDashboardController::class, 'printTrimesterAttendance'])->name('attendance.print_trimester');

    // Rute untuk chart analisis visual guru
    Route::get('/attendance/charts', [TeacherDashboardController::class, 'charts'])->name('attendance.charts');
    Route::post('/attendance/charts/data', [TeacherDashboardController::class, 'chartData'])->name('attendance.charts.data');

    // RUTE BARU UNTUK EXPORT EXCEL
    Route::get('/attendance/export', [TeacherDashboardController::class, 'exportAttendanceExcel'])->name('attendance.export.excel');

    Route::get('/leave-requests', [TeacherLeaveRequestController::class, 'index'])->name('leave_requests.index');
    Route::post('/leave-requests/{leaveRequest}/approve', [TeacherLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [TeacherLeaveRequestController::class, 'reject'])->name('leave_requests.reject');

    Route::post('/notes/update', [TeacherDashboardController::class, 'updateNote'])->name('notes.update');
    
    // Baru: Wali kelas ganti foto murid
    Route::post('/students/{student}/update-photo', [TeacherDashboardController::class, 'updateStudentPhoto'])->name('students.update_photo');

    // == RUTE UNTUK ABSENSI MATA PELAJARAN ==
    Route::get('/subject-attendance/scanner/{schedule}', [SubjectAttendanceController::class, 'showScanner'])->name('subject.attendance.scanner');
    Route::post('/subject-attendance/store', [SubjectAttendanceController::class, 'store'])->name('subject.attendance.store');
    Route::get('/subject-attendance/history', [SubjectAttendanceController::class, 'showHistory'])->name('subject.attendance.history');
    Route::post('/subject-attendance/mark-manual', [SubjectAttendanceController::class, 'markManualAttendance'])->name('subject.attendance.mark_manual');

    // == RUTE BARU UNTUK REKAP DAN CETAK ABSENSI MAPEL (ALUR DIPERBARUI) ==
    // 1. Tampilkan form filter
    Route::get('/subject-attendance/report', [SubjectAttendanceController::class, 'showReportForm'])->name('subject.attendance.report');
    // 2. Tampilkan halaman preview (hasil dari form)
    Route::get('/subject-attendance/report/preview', [SubjectAttendanceController::class, 'showReportPreview'])->name('subject.attendance.preview');
    // 3. Proses update data dari halaman preview
    Route::post('/subject-attendance/report/update', [SubjectAttendanceController::class, 'updateReportAttendance'])->name('subject.attendance.update_report');
    // 4. Tampilkan halaman cetak (diakses dari halaman preview)
    Route::get('/subject-attendance/report/print', [SubjectAttendanceController::class, 'printReport'])->name('subject.attendance.print');

    // == RUTE ABSENSI GURU (WAJAH & LOKASI) ==
    Route::get('/attendance/dashboard', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'index'])->name('attendance.dashboard');
    Route::get('/attendance/scanner', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'showScanner'])->name('attendance.scanner');
    Route::post('/attendance/store', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/register-face', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'registerFace'])->name('attendance.register_face');

    // == RUTE UNTUK ABSENSI EKSTRAKURIKULER ==
    Route::get('/extracurricular-attendance', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'index'])->name('extracurricular-attendance.index');
    Route::get('/extracurricular-attendance/{extracurricular}/create', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'create'])->name('extracurricular-attendance.create');
    Route::post('/extracurricular-attendance/{extracurricular}', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'store'])->name('extracurricular-attendance.store');
    Route::get('/extracurricular-attendance/{extracurricular}/report', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'report'])->name('extracurricular-attendance.report');
});

// == PERBAIKAN: Utilitas perbaikan storage symlink untuk Hostinger / Shared Hosting ==
Route::get('/fix-storage-link', function () {
    // Pengamanan sederhana dengan token rahasia
    if (!auth()->check() && request('key') !== 'presensi123') {
        return response('Akses ditolak. Silakan tambahkan parameter key rahasia (contoh: /fix-storage-link?key=presensi123) atau masuk log terlebih dahulu.', 403);
    }

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    $publicPath = public_path();
    $storagePath = storage_path('app/public');
    
    $output = "<h3>Diagnostik Jalur Server:</h3>";
    $output .= "Document Root Server: <b>" . htmlspecialchars($documentRoot) . "</b><br>";
    $output .= "Laravel Public Path: <b>" . htmlspecialchars($publicPath) . "</b><br>";
    $output .= "Laravel Storage Path: <b>" . htmlspecialchars($storagePath) . "</b><br><br>";

    // Jalur-jalur target pembuatan symlink
    $targets = array_unique(array_filter([
        $publicPath . '/storage',
        $documentRoot ? ($documentRoot . '/storage') : null
    ]));

    foreach ($targets as $target) {
        $output .= "Memproses target: <b>" . htmlspecialchars($target) . "</b>... ";
        
        if (file_exists($target) || is_link($target)) {
            if (is_link($target) || !is_dir($target)) {
                if (@unlink($target)) {
                    $output .= "file/symlink lama berhasil dihapus. ";
                } else {
                    $output .= "gagal menghapus file/symlink lama. ";
                }
            } else {
                $backup = $target . '_backup_' . time();
                if (@rename($target, $backup)) {
                    $output .= "direktori lama diubah nama ke " . basename($backup) . ". ";
                } else {
                    $output .= "gagal mengubah nama direktori lama. ";
                }
            }
        }

        try {
            if (@symlink($storagePath, $target)) {
                $output .= "<span style='color: green;'>✔ Berhasil dibuat!</span><br>";
            } else {
                // Gunakan Laravel link library jika symlink() bawaan php gagal/restricted
                app()->make('files')->link($storagePath, $target);
                $output .= "<span style='color: green;'>✔ Berhasil dibuat via Laravel Filesystem!</span><br>";
            }
        } catch (\Throwable $e) {
            $output .= "<span style='color: red;'>❌ Gagal: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    }

    $output .= "<br><p>Silakan segarkan kembali laman website Anda. Jika tetap tidak tampil, pastikan berkas-berkas foto/logo telah diunggah ke direktori storage yang benar di server: <b>" . htmlspecialchars($storagePath) . "</b>.</p>";
    return response($output);
});

// == PERBAIKAN: Jalur cadangan jika storage symlink tidak berfungsi / dinonaktifkan di shared hosting ==
Route::get('/storage/{path}', function ($path) {
    // Bersihkan dari potensi peretasan direktori (directory traversal)
    $path = str_replace(['..', '\\'], '', $path);
    $filePath = storage_path('app/public/' . $path);

    if (!file_exists($filePath) || is_dir($filePath)) {
        abort(404);
    }

    $file = file_get_contents($filePath);
    
    // Deteksi MIME type secara aman jika extension fileinfo tidak aktif
    $type = null;
    if (function_exists('mime_content_type')) {
        $type = @mime_content_type($filePath);
    }
    
    if (!$type) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
        ];
        $type = $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    return response($file)->header('Content-Type', $type);
})->where('path', '.*');

require __DIR__ . '/auth.php';
