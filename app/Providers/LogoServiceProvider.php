<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Models\LeaveRequest;
use App\Models\AdminMessage; // Impor model baru
use App\Models\Message; // Impor model baru
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;

class LogoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('app_settings', function ($app) {
            if ($app->runningInConsole()) { return collect(); }
            try { return Setting::pluck('value', 'key'); } 
            catch (QueryException $e) { return collect(); }
        });
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $settings = $this->app->make('app_settings');
            $user = Auth::user();
            
            $pendingLeaveRequestsCount = 0;
            $teacherPendingLeaveRequestsCount = 0;
            $totalUnreadMessagesCount = 0; // Variabel baru untuk total pesan belum dibaca

            if (Auth::check()) {
                try {
                    // Notifikasi untuk Admin
                    if ($user->role === 'admin') {
                        $pendingLeaveRequestsCount = LeaveRequest::where('status', 'pending')->count();
                        // Hitung pesan belum dibaca dari semua orang tua
                        $totalUnreadMessagesCount = AdminMessage::where('user_id', '!=', $user->id)->whereNull('read_at')->count();
                    }
                    // Notifikasi untuk Guru Wali Kelas
                    if ($user->role === 'teacher' && $user->teacher?->homeroomClass) {
                        $studentIds = $user->teacher->homeroomClass->students()->pluck('id');
                        $teacherPendingLeaveRequestsCount = LeaveRequest::whereIn('student_id', $studentIds)->where('status', 'pending')->count();
                        // Hitung pesan belum dibaca dari orang tua siswa perwalian
                        $totalUnreadMessagesCount = Message::whereHas('conversation', function ($query) {
                            $query->where('teacher_id', Auth::user()->teacher->id);
                        })->where('user_id', '!=', $user->id)->whereNull('read_at')->count();
                    }
                    // Notifikasi untuk Orang Tua
                    if ($user->role === 'parent' && $user->parent) {
                        // Hitung pesan dari wali kelas
                        $teacherMessages = Message::whereHas('conversation', function ($query) {
                            $query->where('parent_id', Auth::user()->parent->id);
                        })->where('user_id', '!=', $user->id)->whereNull('read_at')->count();
                        // Hitung pesan dari admin
                        $adminMessages = AdminMessage::whereHas('conversation', function ($query) {
                            $query->where('parent_id', Auth::user()->parent->id);
                        })->where('user_id', '!=', $user->id)->whereNull('read_at')->count();
                        $totalUnreadMessagesCount = $teacherMessages + $adminMessages;
                    }

                } catch (QueryException $e) {
                    // Biarkan 0 jika ada masalah database
                }
            }
            
            $view->with([
                'appLogoPath' => $settings->get('app_logo'),
                'appName' => $settings->get('school_name', config('app.name', 'AbsensiSiswa')),
                'darkModeEnabled' => $settings->get('dark_mode', 'off') === 'on',
                'pendingLeaveRequestsCount' => $pendingLeaveRequestsCount,
                'teacherPendingLeaveRequestsCount' => $teacherPendingLeaveRequestsCount,
                'totalUnreadMessagesCount' => $totalUnreadMessagesCount, // Kirim data notifikasi obrolan
            ]);
        });
    }
}
