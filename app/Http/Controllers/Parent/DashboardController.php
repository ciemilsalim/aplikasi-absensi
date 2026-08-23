<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
use App\Models\Notification;

class DashboardController extends Controller
{
    /**
     * Menampilkan dasbor untuk orang tua.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $parent = $user->parent;

        // Pastikan data parent ada sebelum melanjutkan
        if (!$parent) {
            try {
                $parent = \App\Models\ParentModel::firstOrCreate(
                    ['user_id' => $user->id],
                    ['name' => $user->name]
                );
            } catch (\Throwable $e) {
                return redirect()->route('profile.edit')->with('warning', 'Harap lengkapi data profil Anda terlebih dahulu.');
            }
        }

        $pendingRequests = collect();
        if ($parent) {
            try {
                $pendingRequests = $parent->studentRequests()
                    ->with('student.schoolClass')
                    ->where('status', 'pending')
                    ->get();
            } catch (\Throwable $e) {
                $pendingRequests = collect();
            }
        }

        $students = collect();
        if ($parent) {
            try {
                $students = $parent->students()->with([
                    'attendances' => function($query){
                        $query->orderByDesc('attendance_time')->take(10);
                    }, 
                    'schoolClass',
                    'extracurriculars',
                    'extracurricularAttendances' => function($query) {
                        $query->orderByDesc('attendance_date')->take(5);
                    }
                ])->get();
            } catch (\Throwable $e) {
                $students = collect();
            }
        }

        foreach ($students as $student) {
            try {
                $student->today_attendance = \App\Models\Attendance::where('student_id', $student->id)
                    ->whereDate('attendance_time', \Carbon\Carbon::today())
                    ->first();
            } catch (\Throwable $e) {
                $student->today_attendance = null;
            }
        }
        
        $announcements = collect();
        try {
            $announcements = Announcement::whereNotNull('published_at')
                                         ->where('published_at', '<=', now())
                                         ->latest('published_at')
                                         ->take(3)
                                         ->get();
        } catch (\Throwable $e) {
            $announcements = collect();
        }
        
        $unreadNotifications = collect();
        try {
            $unreadNotifications = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->latest()
                ->get();
        } catch (\Throwable $e) {
            $unreadNotifications = collect();
        }

        // Pengecekan hari libur akhir pekan dan tanggal merah / kalender pendidikan
        $today = \Carbon\Carbon::today();
        $isWeekend = $today->isWeekend();
        $isHoliday = false;
        $offDayReason = $isWeekend ? 'Libur Akhir Pekan (' . $today->translatedFormat('l') . ')' : null;

        try {
            $holidaysToday = \App\Models\Calendar::getHolidaysInRange($today, $today);
            $todayHoliday = $holidaysToday->first(function($h) use ($today) {
                return \App\Models\Calendar::isDateInHolidays($today, collect([$h]));
            });
            if ($todayHoliday) {
                $isHoliday = true;
                $offDayReason = $todayHoliday->title;
            }
        } catch (\Throwable $e) {
            // Abaikan jika kalender tidak tersedia
        }

        $isOffDay = $isWeekend || $isHoliday;

        return view('parent.dashboard', compact(
            'students', 
            'pendingRequests',
            'announcements', 
            'unreadNotifications',
            'isOffDay',
            'offDayReason',
            'isWeekend',
            'isHoliday'
        ));
    }

    /**
     * Menampilkan halaman panduan penggunaan aplikasi untuk orang tua.
     */
    public function guide()
    {
        return view('parent.guide');
    }
}
