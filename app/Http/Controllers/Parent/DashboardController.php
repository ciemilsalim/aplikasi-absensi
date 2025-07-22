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

        $students = $parent->students()->with(['attendances' => function($query){
            $query->latest()->take(5);
        }, 'schoolClass'])->get();
        
        // PERBAIKAN: Mengambil kembali 3 pengumuman terbaru yang sudah dipublikasikan
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
