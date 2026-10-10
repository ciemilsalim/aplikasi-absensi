<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AcademicPeriodController;

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
use App\Http\Controllers\Admin\ScanLogController;


// Parent Controller
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\LeaveRequestController as ParentLeaveRequestController;
use App\Http\Controllers\Parent\ParentOnboardingController;
use App\Http\Controllers\Admin\ParentVerificationController;

// Teacher Controller
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LeaveRequestController as TeacherLeaveRequestController;
use App\Http\Controllers\Teacher\SubjectAttendanceController;
use App\Http\Controllers\Teacher\TeachingJournalController;
use App\Http\Controllers\Teacher\StudentAnecdoteController;
use App\Http\Controllers\Admin\AdminTeachingJournalController;

/* |-------------------------------------------------------------------------- | Rute Web |-------------------------------------------------------------------------- */

// == RUTE PUBLIK ==
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/guide', [\App\Http\Controllers\GuideController::class, 'index'])->name('guide');
Route::view('/offline', 'offline')->name('offline'); // Endpoint PWA
Route::get('/sso/login', [\App\Http\Controllers\Auth\SsoLoginController::class, 'login'])->name('sso.login');
Route::get('/sso/lms', [\App\Http\Controllers\SSOController::class, 'redirectToLms'])->name('sso.lms');


// == RUTE AUTENTIKASI & PENGALIHAN ==
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasAnyRole(['kepala_sekolah', 'kepala sekolah', 'headmaster'])) {
        return redirect()->route('principal.dashboard');
    }
    if ($user->hasAnyRole(['admin', 'operator', 'satpam', 'viewer'])) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->hasRole('parent')) {
        return redirect()->route('parent.dashboard');
    }
    if ($user->hasRole('teacher')) {
        return redirect()->route('teacher.dashboard');
    }
    
    // Mencegah redirect loop untuk role yang tidak terdaftar (seperti 'user' default)
    auth()->logout();
    return redirect()->route('login')->withErrors(['role' => 'Peran Anda tidak dikenali oleh sistem absensi. Silakan hubungi administrator.']);
})->middleware(['auth', 'verified'])->name('dashboard');

// == GRUP RUTE KEPALA SEKOLAH (EXECUTIVE OVERVIEW) ==
Route::middleware(['auth', 'role:admin,operator,wakasek_kurikulum,wakasek kurikulum,waka_kurikulum,waka kurikulum,kepala_sekolah,kepala sekolah,headmaster,viewer'])->prefix('principal')->name('principal.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Principal\PrincipalDashboardController::class, 'index'])->name('dashboard');
});

// == GRUP RUTE YANG MEMERLUKAN LOGIN ==
Route::middleware('auth')->group(function () {
    Route::post('/set-academic-period', [AcademicPeriodController::class, 'setPeriod'])->name('set-academic-period');
    
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
});

// == GRUP RUTE UNTUK AKSES PEMINDAI ==
Route::middleware(['auth', 'scanner.access'])->group(function () {
    Route::get('/scanner', [AttendanceController::class, 'showScanner'])->name('scanner');
    Route::post('/attendance', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
    Route::post('/attendance/sync-offline', [AttendanceController::class, 'syncOffline'])->name('attendance.sync_offline');
    // Baru: Menyimpan pola wajah (descriptor) siswa dari scanner
    Route::post('/attendance/save-student-descriptor', [AttendanceController::class, 'saveStudentDescriptor'])->name('attendance.save_descriptor');

    // Rute Pemindai Izin Keluar/Kembali Siswa (Hanya untuk Satpam, Guru, Operator, Admin)
    Route::get('/permit-scanner', [\App\Http\Controllers\PermitController::class, 'showScanner'])->name('permit.scanner');
    Route::post('/permit-scanner/store', [\App\Http\Controllers\PermitController::class, 'storePermit'])->name('permit.store');
});

// == GRUP RUTE ADMIN ==
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Rute yang bisa diakses oleh Admin, Operator, Satpam, Kepala Sekolah, dan Viewer
    Route::middleware(['role:admin,operator,satpam,kepala_sekolah,kepala sekolah,headmaster,viewer'])->group(
        function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/reports', [ReportController::class, 'create'])->name('reports.create');
            Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
            Route::get('/reports/charts', [ReportController::class, 'charts'])->name('reports.charts');
            Route::post('/reports/charts/data', [ReportController::class, 'chartData'])->name('reports.charts.data');

            // Audit Trail Presensi (Akuntabilitas BOS, Kepala Sekolah & Inspektorat)
            Route::get('/audit-logs', [\App\Http\Controllers\Admin\AttendanceAuditLogController::class, 'index'])->name('audit_logs.index');
        }
    );

    // Pengajuan & Intervensi Izin Siswa - Pembacaan List (Dapat diakses oleh Admin, TU/Operator, dan Viewer)
    Route::middleware(['role:admin,operator,tu,tata_usaha,viewer'])->group(
        function () {
            Route::get('/leave-requests', [AdminLeaveRequestController::class, 'index'])->name('leave_requests.index');
            Route::get('/leave-requests/students-by-class', [AdminLeaveRequestController::class, 'studentsByClass'])->name('leave_requests.students_by_class');
        }
    );

    // Pengajuan & Intervensi Izin Siswa - Tindakan Mutasi Data (HANYA Admin & Tata Usaha / Operator)
    Route::middleware(['role:admin,operator,tu,tata_usaha'])->group(
        function () {
            Route::post('/leave-requests/manual', [AdminLeaveRequestController::class, 'storeManual'])->name('leave_requests.store_manual');
            Route::get('/leave-requests/{leaveRequest}/edit', [AdminLeaveRequestController::class, 'edit'])->name('leave_requests.edit');
            Route::put('/leave-requests/{leaveRequest}', [AdminLeaveRequestController::class, 'update'])->name('leave_requests.update');
            Route::delete('/leave-requests/{leaveRequest}', [AdminLeaveRequestController::class, 'destroy'])->name('leave_requests.destroy');
            Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leave_requests.approve');
            Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveRequestController::class, 'reject'])->name('leave_requests.reject');

            // Koreksi Presensi Langsung dengan Justifikasi Audit
            Route::post('/audit-logs/correct', [\App\Http\Controllers\Admin\AttendanceAuditLogController::class, 'correct'])->name('audit_logs.correct');
        }
    );

    // Rute yang HANYA bisa diakses oleh Admin (Tetap Aktif)
    Route::middleware(['role:admin'])->group(
        function () {
            // Obrolan Admin
            Route::get('/chat/{selectedParent?}', [AdminChatController::class, 'index'])->name('chat.index');
            Route::post('/chat/conversations/{conversation}', [AdminChatController::class, 'storeMessage'])->name('chat.store_message');

            // Laporan Guru
            Route::get('reports/teacher', [\App\Http\Controllers\Admin\TeacherReportController::class, 'index'])->name('reports.teacher.index');
            Route::get('reports/teacher/print', [\App\Http\Controllers\Admin\TeacherReportController::class, 'print'])->name('reports.teacher.print');
        }
    );

    // Rute utilitas & operasional lokal admin (Aktif & dapat diakses)
    Route::middleware(['role:admin'])->group(function () {
        // Monitoring Log Scan Absensi
        Route::get('/scan-logs', [ScanLogController::class, 'index'])->name('scan-logs.index');
        // Pengaturan Aplikasi
        Route::get('/settings/identity', [SettingController::class, 'identity'])->name('settings.identity');
        Route::get('/settings/appearance', [SettingController::class, 'appearance'])->name('settings.appearance');
        Route::get('/settings/attendance', [SettingController::class, 'attendance'])->name('settings.attendance');
        Route::get('/settings/whatsapp', [SettingController::class, 'whatsapp'])->name('settings.whatsapp');
        Route::post('/settings/whatsapp/test', [SettingController::class, 'testWhatsApp'])->name('settings.whatsapp.test');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Pengumuman
        Route::resource('announcements', AnnouncementController::class);

        // Kurikulum & Kalender Pendidikan
        Route::post('calendars/import', [\App\Http\Controllers\Admin\CalendarController::class, 'import'])->name('calendars.import');
        Route::get('calendars/list', [\App\Http\Controllers\Admin\CalendarController::class, 'list'])->name('calendars.list');
        Route::resource('calendars', \App\Http\Controllers\Admin\CalendarController::class)->except(['show', 'edit', 'create']);

        // Jadwal Pelajaran
        Route::get('schedules', [\App\Http\Controllers\Admin\ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('schedules/{school_class}', [\App\Http\Controllers\Admin\ScheduleController::class, 'show'])->name('schedules.show');
        Route::post('schedules/{school_class}', [\App\Http\Controllers\Admin\ScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('schedules/{schedule}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Backup Database Lokal
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

        // Cetak Kartu Siswa & QR Code (Utilitas Cetak Lokal)
        Route::get('/students/qr-cards', [StudentController::class, 'printQrCards'])->name('students.qr');
    });

    // Rute master data yang DIREDIRECT karena dikelola terpusat di SIPADA
    Route::middleware(['role:admin', 'sipada.redirect'])->group(
        function () {
            // Manajemen Data (CRUD)
            Route::get('classes/{school_class}/assign', [SchoolClassController::class, 'showAssignForm'])->name('classes.assign');
            Route::post('classes/assign-students', [SchoolClassController::class, 'assignStudents'])->name('classes.assign.students');
            Route::resource('classes', SchoolClassController::class)->except(['show']);
            Route::resource('students', StudentController::class)->except(['show']);
            Route::resource('parents', ParentController::class)->except(['show']);
            Route::resource('teachers', TeacherController::class)->except(['show']);
            Route::resource('users', UserController::class)->except(['show']);
            Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class)->except(['show']);
            Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk_destroy');
            Route::post('students/bulk-destroy', [StudentController::class, 'bulkDestroy'])->name('students.bulk_destroy');
            Route::post('students/bulk-promote', [StudentController::class, 'bulkPromote'])->name('students.bulk_promote');

            Route::get('classes/{school_class}/assign-teacher', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'index'])->name('classes.assign_teacher');
            Route::post('classes/{school_class}/assign-teacher', [\App\Http\Controllers\Admin\TeachingAssignmentController::class, 'store'])->name('classes.store_teacher_assignment');

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
        }
    );

    // Supervisi Jurnal Mengajar Guru & Verifikasi Klaim Ortu - Pembacaan List (Termasuk Viewer)
    Route::middleware(['role:admin,operator,wakasek_kurikulum,wakasek kurikulum,waka_kurikulum,waka kurikulum,kepala_sekolah,kepala sekolah,headmaster,viewer'])->group(
        function () {
            Route::get('/teaching-journals', [AdminTeachingJournalController::class, 'index'])->name('teaching_journals.index');
            Route::get('/teaching-journals/teacher/{teacher}', [AdminTeachingJournalController::class, 'show'])->name('teaching_journals.show');
            Route::get('/parent-verifications', [ParentVerificationController::class, 'index'])->name('parent_verification.index');
        }
    );

    // Supervisi Jurnal Mengajar & Verifikasi Klaim Ortu - Eksekusi Mutasi/Verifikasi (EKSKLUSIF Tanpa Viewer)
    Route::middleware(['role:admin,operator,wakasek_kurikulum,wakasek kurikulum,waka_kurikulum,waka kurikulum,kepala_sekolah,kepala sekolah,headmaster'])->group(
        function () {
            Route::post('/teaching-journals/{journal}/verify', [AdminTeachingJournalController::class, 'verify'])->name('teaching_journals.verify');
            Route::post('/teaching-journals/batch-verify', [AdminTeachingJournalController::class, 'batchVerify'])->name('teaching_journals.batch_verify');
            Route::post('/parent-verifications/{parentRequest}/approve', [ParentVerificationController::class, 'approve'])->name('parent_verification.approve');
            Route::post('/parent-verifications/{parentRequest}/reject', [ParentVerificationController::class, 'reject'])->name('parent_verification.reject');
        }
    );
});

// == GRUP RUTE ORANG TUA ==
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/guide', [ParentDashboardController::class, 'guide'])->name('guide');
    Route::resource('leave-requests', ParentLeaveRequestController::class)->only(['index', 'create', 'store']);

    // Onboarding Orang Tua Baru
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [ParentOnboardingController::class, 'index'])->name('index');
        Route::post('/step1', [ParentOnboardingController::class, 'updateContact'])->name('step1');
        Route::post('/verify-student', [ParentOnboardingController::class, 'verifyStudent'])->name('verify_student');
        Route::delete('/request/{studentId}', [ParentOnboardingController::class, 'removeRequest'])->name('remove_request');
        Route::post('/complete', [ParentOnboardingController::class, 'complete'])->name('complete');
    });

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
    
    // 5. Analitik Grafis Mapel
    Route::get('/subject-attendance/charts', [SubjectAttendanceController::class, 'charts'])->name('subject.attendance.charts');
    Route::post('/subject-attendance/charts/data', [SubjectAttendanceController::class, 'chartData'])->name('subject.attendance.charts.data');

    // == RUTE ABSENSI GURU (WAJAH & LOKASI) ==
    Route::get('/attendance/dashboard', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'index'])->name('attendance.dashboard');
    Route::get('/attendance/scanner', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'showScanner'])->name('attendance.scanner');
    Route::post('/attendance/store', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/register-face', [\App\Http\Controllers\Teacher\TeacherAttendanceController::class, 'registerFace'])->name('attendance.register_face');

    // == RUTE UNTUK ABSENSI EKSTRAKURIKULER ==
    Route::get('/extracurricular-attendance', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'index'])->name('extracurricular-attendance.index');
    Route::get('/extracurricular-attendance/{extracurricular}/create', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'create'])->name('extracurricular-attendance.create');
    Route::post('/extracurricular-attendance/{extracurricular}', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'store'])->name('extracurricular-attendance.store');
    Route::get('/extracurricular-attendance/{extracurricular}/scanner', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'showScanner'])->name('extracurricular-attendance.scanner');
    Route::post('/extracurricular-attendance/{extracurricular}/scan', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'storeScan'])->name('extracurricular-attendance.scan');
    Route::post('/extracurricular-attendance/{extracurricular}/mark-manual', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'markManual'])->name('extracurricular-attendance.mark_manual');
    Route::get('/extracurricular-attendance/{extracurricular}/report', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'report'])->name('extracurricular-attendance.report');
    Route::get('/extracurricular-attendance/{extracurricular}/print', [\App\Http\Controllers\Teacher\ExtracurricularAttendanceController::class, 'print'])->name('extracurricular-attendance.print');

    // == RUTE JURNAL MENGAJAR GURU MATA PELAJARAN (SMP NEGERI 1 BIAU) ==
    Route::get('/journals', [TeachingJournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/create', [TeachingJournalController::class, 'create'])->name('journals.create');
    Route::post('/journals', [TeachingJournalController::class, 'store'])->name('journals.store');
    Route::get('/journals/{journal}/edit', [TeachingJournalController::class, 'edit'])->name('journals.edit');
    Route::put('/journals/{journal}', [TeachingJournalController::class, 'update'])->name('journals.update');
    Route::delete('/journals/{journal}', [TeachingJournalController::class, 'destroy'])->name('journals.destroy');
    Route::get('/journals/weekly-report', [TeachingJournalController::class, 'weeklyReport'])->name('journals.weekly');
    Route::get('/journals/semester-report', [TeachingJournalController::class, 'semesterReport'])->name('journals.semester');
    Route::get('/journals/reflection', [TeachingJournalController::class, 'reflection'])->name('journals.reflection');
    Route::post('/journals/reflection', [TeachingJournalController::class, 'storeReflection'])->name('journals.reflection.store');
    Route::get('/journals/print-document', [TeachingJournalController::class, 'print'])->name('journals.print');
    Route::get('/journals/api/session-data', [TeachingJournalController::class, 'apiGetSessionData'])->name('journals.session_data');

    // == RUTE CATATAN ANEKDOT SISWA (AKADEMIK, KEHADIRAN, SIKAP) ==
    Route::get('/anecdotes/get-student', [StudentAnecdoteController::class, 'getForStudent'])->name('anecdotes.get_student');
    Route::post('/anecdotes/store-update', [StudentAnecdoteController::class, 'storeOrUpdate'])->name('anecdotes.store_update');
    Route::get('/anecdotes', [StudentAnecdoteController::class, 'index'])->name('anecdotes.index');
    Route::get('/anecdotes/print', [StudentAnecdoteController::class, 'print'])->name('anecdotes.print');
    Route::delete('/anecdotes/{anecdote}', [StudentAnecdoteController::class, 'destroy'])->name('anecdotes.destroy');
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
