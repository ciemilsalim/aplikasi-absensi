<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Menandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Notification $notification)
    {
        // Pastikan notifikasi ini milik pengguna yang sedang login
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return redirect()->back();
    }
}
