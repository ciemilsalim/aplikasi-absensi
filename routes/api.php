<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\TeacherSelfAttendanceController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Rute Absensi untuk Wali Kelas
    Route::get('/teacher/students', [TeacherAttendanceController::class, 'getHomeroomStudents']);
    Route::post('/teacher/attendance/scan', [TeacherAttendanceController::class, 'scanQr']);
    Route::patch('/teacher/students/{studentId}/attendance/override', [TeacherAttendanceController::class, 'overrideAttendance']);

    // Rute Permohonan Izin Siswa (Wali Kelas)
    Route::get('/teacher/leave-requests', [LeaveRequestController::class, 'index']);
    Route::patch('/teacher/leave-requests/{id}/status', [LeaveRequestController::class, 'updateStatus']);

    // Rute Jadwal & Absensi Mapel
    Route::get('/teacher/schedules', [ScheduleController::class, 'index']);
    Route::get('/teacher/schedules/{id}/students', [ScheduleController::class, 'getStudents']);
    Route::post('/teacher/schedules/attendance', [ScheduleController::class, 'storeAttendance']);
    Route::get('/teacher/subject-attendance/history', [ScheduleController::class, 'getHistory']);
    Route::get('/teacher/subject-attendance/history/detail', [ScheduleController::class, 'getHistoryDetail']);
    Route::get('/teacher/subject-attendance/attention', [ScheduleController::class, 'getAttentionStudents']);


    // Rute Jurnal Mengajar
    Route::get('/teacher/journals', [JournalController::class, 'index']);
    Route::post('/teacher/journals', [JournalController::class, 'store']);
    Route::get('/teacher/journals/schedule/{scheduleId}', [JournalController::class, 'showBySchedule']);

    // Rute Pengumuman Sekolah
    Route::get('/teacher/announcements', [AnnouncementController::class, 'index']);
    Route::get('/teacher/announcements/{id}', [AnnouncementController::class, 'show']);

    // Rute Kalender Akademik
    Route::get('/teacher/calendar', [CalendarController::class, 'index']);

    // Rute Komunikasi / Chat (Umum untuk Guru & Ortu)
    Route::get('/chat', [ChatController::class, 'index']);
    Route::post('/chat/start', [ChatController::class, 'startConversation']);
    Route::get('/chat/{id}/messages', [ChatController::class, 'getMessages']);
    Route::post('/chat/{id}/send', [ChatController::class, 'sendMessage']);

    // Rute Absensi Mandiri Guru (Face & GPS)
    Route::get('/teacher/self-attendance/status', [TeacherSelfAttendanceController::class, 'getStatus']);
    Route::post('/teacher/self-attendance/clock-in', [TeacherSelfAttendanceController::class, 'store']);
    Route::post('/teacher/self-attendance/clock-out', [TeacherSelfAttendanceController::class, 'clockOut']);
    Route::post('/teacher/self-attendance/register-face', [TeacherSelfAttendanceController::class, 'registerFace']);
    Route::get('/teacher/self-attendance/history', [TeacherSelfAttendanceController::class, 'getHistory']);

    // Rute Profil Guru
    Route::get('/teacher/profile', [ProfileController::class, 'show']);
    Route::put('/teacher/profile', [ProfileController::class, 'update']);

    // == RUTE KHUSUS ORANG TUA ==
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Parent\ParentDashboardController::class, 'index']);
        
        // Data Siswa/Anak
        Route::get('/students/{student}/attendance', [\App\Http\Controllers\Api\Parent\ParentStudentController::class, 'attendance']);
        Route::get('/students/{student}/subject-attendance', [\App\Http\Controllers\Api\Parent\ParentStudentController::class, 'subjectAttendance']);
        Route::get('/students/{student}/schedule', [\App\Http\Controllers\Api\Parent\ParentStudentController::class, 'schedule']);
        Route::get('/students/{student}/journals', [\App\Http\Controllers\Api\Parent\ParentStudentController::class, 'journals']);
        Route::get('/students/{student}/notes', [\App\Http\Controllers\Api\Parent\ParentStudentController::class, 'notes']);

        // Perizinan
        Route::get('/leave-requests', [\App\Http\Controllers\Api\Parent\ParentLeaveRequestController::class, 'index']);
        Route::post('/leave-requests', [\App\Http\Controllers\Api\Parent\ParentLeaveRequestController::class, 'store']);
    });

    // Rute Pengaturan & Profil Sekolah
    Route::get('/settings/gps', [SettingController::class, 'getGpsSettings']);
    Route::get('/settings/school-profile', [SettingController::class, 'getSchoolProfile']);
});

