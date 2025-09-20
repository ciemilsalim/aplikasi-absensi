<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminConversation;
use App\Models\ParentModel;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminChatController extends Controller
{
    /**
     * Menampilkan halaman utama obrolan admin.
     *
     * @param ParentModel|null $selectedParent
     * @return \Illuminate\View\View
     */
    public function index(ParentModel $selectedParent = null)
    {
        $adminId = Auth::id();

        // Langkah 1: Pastikan semua orang tua memiliki entri percakapan.
        // Ini penting agar orang tua tanpa riwayat obrolan tetap muncul di daftar.
        $allParentIds = ParentModel::whereHas('user')->pluck('id');
        foreach ($allParentIds as $parentId) {
            AdminConversation::firstOrCreate(
                ['parent_id' => $parentId, 'admin_id' => $adminId]
            );
        }

        // Langkah 2: Ambil semua data secara efisien dengan Eager Loading untuk menghindari N+1 problem.
        $parents = ParentModel::with([
            'user', 
            // Muat relasi percakapan beserta semua pesannya
            'adminConversation.messages' => function ($query) {
                // Urutkan pesan di sini agar mudah mengambil yang terbaru nanti
                $query->orderBy('created_at', 'desc');
            }
        ])
        ->whereHas('user')
        ->get();

        // Langkah 3: Proses data di memori (bukan di database loop) untuk performa.
        $parents = $parents->map(function ($parent) use ($adminId) {
            if ($parent->adminConversation) {
                $messages = $parent->adminConversation->messages;
                
                // Hitung pesan belum dibaca dari data yang sudah dimuat
                $parent->unread_messages_count = $messages
                    ->where('user_id', '!=', $adminId)
                    ->whereNull('read_at')
                    ->count();
                
                // Ambil waktu pesan terakhir (pesan pertama karena sudah diurutkan desc)
                $lastMessage = $messages->first();
                $parent->last_message_at = $lastMessage ? $lastMessage->created_at : null;
            } else {
                // Fallback jika percakapan tidak ditemukan (seharusnya tidak terjadi)
                $parent->unread_messages_count = 0;
                $parent->last_message_at = null;
            }
            return $parent;
        })
        // Langkah 4: Urutkan hasil akhir berdasarkan waktu pesan.
        ->sortByDesc('last_message_at');
        
        $messages = collect();
        $activeConversation = null;

        if ($selectedParent && $selectedParent->exists) {
            // Ambil percakapan dari relasi yang sudah dimuat untuk efisiensi
            $activeConversation = $parents->firstWhere('id', $selectedParent->id)->adminConversation;
            
            if ($activeConversation) {
                $messages = $activeConversation->messages()->with('user')->get()->groupBy(function($message) {
                    return $message->created_at->format('Y-m-d');
                });
                $activeConversation->messages()->where('user_id', '!=', $adminId)->whereNull('read_at')->update(['read_at' => now()]);
            }
        }
        
        return view('admin.chat.index', compact('parents', 'selectedParent', 'activeConversation', 'messages'));
    }

    /**
     * Menyimpan balasan dari admin ke orang tua.
     *
     * @param Request $request
     * @param AdminConversation $conversation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeMessage(Request $request, AdminConversation $conversation)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        if ($conversation->admin_id !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        return redirect()->route('admin.chat.index', ['selectedParent' => $conversation->parent_id]);
    }
}

