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

    // Rute Jurnal Mengajar
    Route::get('/teacher/journals', [JournalController::class, 'index']);
    Route::post('/teacher/journals', [JournalController::class, 'store']);
    Route::get('/teacher/journals/schedule/{scheduleId}', [JournalController::class, 'showBySchedule']);

    // Rute Pengumuman Sekolah
    Route::get('/teacher/announcements', [AnnouncementController::class, 'index']);
    Route::get('/teacher/announcements/{id}', [AnnouncementController::class, 'show']);

    // Rute Kalender Akademik
    Route::get('/teacher/calendar', [CalendarController::class, 'index']);

    // Rute Komunikasi / Chat
    Route::get('/teacher/chats', [ChatController::class, 'index']);
    Route::post('/teacher/chats/start', [ChatController::class, 'startConversation']);
    Route::get('/teacher/chats/{id}/messages', [ChatController::class, 'getMessages']);
    Route::post('/teacher/chats/{id}/send', [ChatController::class, 'sendMessage']);

    // Rute Absensi Mandiri Guru (Face & GPS)
    Route::get('/teacher/self-attendance/status', [TeacherSelfAttendanceController::class, 'getStatus']);
    Route::post('/teacher/self-attendance/clock-in', [TeacherSelfAttendanceController::class, 'store']);
    Route::post('/teacher/self-attendance/register-face', [TeacherSelfAttendanceController::class, 'registerFace']);

    // Rute Profil Guru
    Route::get('/teacher/profile', [ProfileController::class, 'show']);
    Route::put('/teacher/profile', [ProfileController::class, 'update']);

    // Rute Pengaturan & Profil Sekolah
    Route::get('/settings/gps', [SettingController::class, 'getGpsSettings']);
    Route::get('/settings/school-profile', [SettingController::class, 'getSchoolProfile']);
});
