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
        $parent = $user->parent;

        // Pastikan data parent ada sebelum melanjutkan
        if (!$parent) {
            // Arahkan ke halaman profil jika data parent belum lengkap
            return redirect()->route('profile.edit')->with('warning', 'Harap lengkapi data profil Anda terlebih dahulu.');
        }

        $pendingRequests = collect();
        if ($parent) {
            try {
                $pendingRequests = $parent->studentRequests()->with('student.schoolClass')->where('status', 'pending')->get();
            } catch (\Throwable $e) {
                $pendingRequests = collect();
            }
        }

        $students = $parent ? $parent->students()->with([
            'attendances' => function($query){
                $query->orderByDesc('attendance_time')->take(10);
            }, 
            'schoolClass',
            'extracurriculars',
            'extracurricularAttendances' => function($query) {
                $query->orderByDesc('attendance_date')->take(5);
            }
        ])->get() : collect();

        foreach ($students as $student) {
            $student->today_attendance = \App\Models\Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', \Carbon\Carbon::today())
                ->first();
        }
        
        // PERBAIKAN: Mengambil kembali 3 pengumuman terbaru yang sudah dipublikasikan
        $announcements = Announcement::whereNotNull('published_at')
                                     ->where('published_at', '<=', now())
                                     ->latest('published_at')
                                     ->take(3)
                                     ->get();
        
        // Ambil notifikasi yang belum dibaca & saring notifikasi kehadiran jika status siswa sudah ditetapkan oleh guru
        $unreadNotifications = Notification::where('user_id', $user->id)
                                           ->where('is_read', false)
                                           ->latest()
                                           ->get()
                                           ->reject(function ($notification) use ($students) {
                                               if (str_contains($notification->title, 'Informasi Kehadiran')) {
                                                   foreach ($students as $student) {
                                                       if (str_contains($notification->title, $student->name)) {
                                                           $todayAttendance = $student->attendances->first(function ($att) {
                                                               return \Carbon\Carbon::parse($att->attendance_time)->isToday();
                                                           });
                                                           
                                                           if ($todayAttendance && $todayAttendance->status !== 'alpa') {
                                                               $notification->update(['is_read' => true]);
                                                               return true;
                                                           }
                                                       }
                                                   }
                                               }
                                               return false;
                                           });

        // Pengecekan hari libur akhir pekan dan tanggal merah / kalender pendidikan
        $today = \Carbon\Carbon::today();
        $isWeekend = $today->isWeekend();
        $holidaysToday = \App\Models\Calendar::getHolidaysInRange($today, $today);
        $todayHoliday = $holidaysToday->first(function($h) use ($today) {
            return \App\Models\Calendar::isDateInHolidays($today, collect([$h]));
        });
        $isHoliday = $todayHoliday !== null;
        $isOffDay = $isWeekend || $isHoliday;
        $offDayReason = $todayHoliday ? $todayHoliday->title : ($isWeekend ? 'Libur Akhir Pekan (' . $today->translatedFormat('l') . ')' : null);

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
