<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use Carbon\Carbon;

class AnnouncementController extends Controller
{
    /**
     * Get published announcements.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        
        $announcements = Announcement::where('published_at', '<=', Carbon::now())
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => $item->content,
                    'published_at' => $item->published_at->format('d M Y H:i'),
                    'author' => $item->user->name ?? 'Admin Sekolah',
                    'banner_url' => $item->banner_url,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }

    /**
     * Get announcement detail.
     */
    public function show($id)
    {
        $announcement = Announcement::with('user')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'published_at' => $announcement->published_at->format('d F Y H:i'),
                'author' => $announcement->user->name ?? 'Admin Sekolah',
                'banner_url' => $announcement->banner_url,
            ]
        ]);
    }
}
