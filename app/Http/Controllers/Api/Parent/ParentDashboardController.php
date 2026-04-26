<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class ParentDashboardController extends Controller
{
    /**
     * Get dashboard summary for parent.
     */
    public function index()
    {
        $user = Auth::user();
        $parent = $user->parent;

        if (!$parent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data orang tua tidak ditemukan.'
            ], 404);
        }

        // Get children with latest attendance
        $students = $parent->students()->with(['schoolClass', 'attendances' => function($query) {
            $query->whereDate('attendance_time', now()->toDateString());
        }])->get();

        $students->transform(function ($student) {
            $student->photo_url = $student->photo ? asset('storage/' . $student->photo) : null;
            return $student;
        });

        // Latest announcements
        $announcements = Announcement::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->take(5)
            ->get();

        // Unread notifications
        $unreadNotificationsCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'parent' => $parent,
                'students' => $students,
                'announcements' => $announcements,
                'unread_notifications_count' => $unreadNotificationsCount,
            ]
        ]);
    }
}
