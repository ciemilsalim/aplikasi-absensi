<?php
namespace App\Http\Controllers\Parent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
class DashboardController extends Controller {
    public function index() {
        $parent = Auth::user()->parent;
        $students = $parent->students()->with(['attendances' => function($query){
            $query->latest()->take(5);
        }, 'schoolClass'])->get();
        
        // Ambil 3 pengumuman terbaru yang sudah dipublikasikan
        $announcements = Announcement::whereNotNull('published_at')
                                     ->where('published_at', '<=', now())
                                     ->latest('published_at')
                                     ->take(3)
                                     ->get();

        return view('parent.dashboard', compact('students', 'announcements'));
    }
}
