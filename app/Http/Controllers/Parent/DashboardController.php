<?php
namespace App\Http\Controllers\Parent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
use App\Models\Notification; // Impor model Notification

class DashboardController extends Controller {
    public function index() {
        $user = Auth::user();
        $parent = $user->parent;
        $students = $parent->students()->with(['attendances' => function($query){
            $query->latest()->take(5);
        }, 'schoolClass'])->get();
        
        $announcements = Announcement::whereNotNull('published_at')
                                     ->where('published_at', '<=', now())
                                     ->latest('published_at')
                                     ->take(3)
                                     ->get();
        
        // Ambil notifikasi yang belum dibaca
        $unreadNotifications = Notification::where('user_id', $user->id)
                                           ->where('is_read', false)
                                           ->latest()
                                           ->get();

        return view('parent.dashboard', compact('students', 'announcements', 'unreadNotifications'));
    }
}
