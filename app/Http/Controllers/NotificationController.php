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
    public function markAsRead(Request $request, Notification $notification)
    {
        // Pastikan notifikasi ini milik pengguna yang sedang login (IDOR defense)
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil ditandai sudah dibaca.',
                'notification' => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'is_read' => (bool) $notification->is_read,
                ]
            ]);
        }

        return redirect()->back();
    }

    /**
     * Menandai semua notifikasi milik pengguna saat ini sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $updatedCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.',
                'updated_count' => $updatedCount
            ]);
        }

        return redirect()->back();
    }
}
