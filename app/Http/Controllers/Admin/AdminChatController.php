<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminConversation;
use App\Models\ParentModel;
use App\Models\AdminMessage; // Pastikan model ini diimpor
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

        // Mengambil parent dengan subquery untuk mendapatkan waktu pesan terakhir
        // dan mengurutkannya berdasarkan waktu tersebut.
        $parents = ParentModel::with('user')
            ->whereHas('user')
            ->addSelect(['*', 'last_message_at' => AdminMessage::select('admin_messages.created_at')
                ->from('admin_messages')
                ->join('admin_conversations', 'admin_conversations.id', '=', 'admin_messages.admin_conversation_id')
                ->whereColumn('admin_conversations.parent_id', 'parents.id')
                ->orderByDesc('admin_messages.created_at')
                ->limit(1)
            ])
            ->orderByDesc('last_message_at') // Urutkan berdasarkan waktu pesan terakhir
            ->get();

        // Menambahkan hitungan pesan yang belum dibaca
        $parents->each(function ($parent) use ($adminId) {
            $conversation = AdminConversation::firstOrCreate(
                ['parent_id' => $parent->id, 'admin_id' => $adminId]
            );
            $parent->unread_messages_count = $conversation->messages()
                ->where('user_id', '!=', $adminId)
                ->whereNull('read_at')
                ->count();
        });
        
        $messages = collect();
        $activeConversation = null;

        if ($selectedParent && $selectedParent->exists) {
            $activeConversation = AdminConversation::firstOrCreate(
                ['parent_id' => $selectedParent->id, 'admin_id' => $adminId]
            );
            // PERBARUAN: Mengelompokkan pesan berdasarkan tanggal
            $messages = $activeConversation->messages()->with('user')->get()->groupBy(function($message) {
                return $message->created_at->format('Y-m-d');
            });
            $activeConversation->messages()->where('user_id', '!=', $adminId)->whereNull('read_at')->update(['read_at' => now()]);
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

        // Pastikan admin yang sedang login adalah bagian dari percakapan ini
        if ($conversation->admin_id !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        // Buat pesan baru
        $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        // Kembali ke halaman obrolan dengan orang tua yang sama
        return redirect()->route('admin.chat.index', ['selectedParent' => $conversation->parent_id]);
    }
}
