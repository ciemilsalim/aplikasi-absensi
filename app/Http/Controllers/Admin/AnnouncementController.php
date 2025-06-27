<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Menampilkan daftar semua pengumuman.
     */
    public function index()
    {
        $announcements = Announcement::latest('published_at')->paginate(10);
        return view('admin.announcements.index', compact('announcements'));
    }

    /**
     * Menampilkan form untuk membuat pengumuman baru.
     */
    public function create()
    {
        return view('admin.announcements.create');
    }

    /**
     * Menyimpan pengumuman baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_now' => 'nullable|boolean',
        ]);

        Announcement::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            // Jika checkbox 'Publish Now' dicentang, langsung isi tanggal publikasi
            'published_at' => $request->has('publish_now') ? now() : null,
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk mengedit pengumuman.
     */
    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    /**
     * Memperbarui pengumuman yang sudah ada di database.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_now' => 'nullable|boolean',
        ]);

        $announcement->update([
            'title' => $request->title,
            'content' => $request->content,
            'published_at' => $request->has('publish_now') ? ($announcement->published_at ?? now()) : null,
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Menghapus pengumuman dari database.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman berhasil dihapus.');
    }
}
