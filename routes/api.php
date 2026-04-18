<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\SettingController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Rute Absensi untuk Wali Kelas
    Route::get('/teacher/students', [TeacherAttendanceController::class, 'getHomeroomStudents']);
    Route::post('/teacher/attendance/scan', [TeacherAttendanceController::class, 'scanQr']);

    // Rute Pengaturan & Profil Sekolah
    Route::get('/settings/gps', [SettingController::class, 'getGpsSettings']);
    Route::get('/settings/school-profile', [SettingController::class, 'getSchoolProfile']);
});
