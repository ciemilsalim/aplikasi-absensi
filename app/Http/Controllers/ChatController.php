<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\Teacher;
use App\Models\Conversation;
use App\Models\Message;

class ChatController extends Controller
{
    /**
     * Menampilkan halaman utama obrolan.
     * Metode ini sekarang menangani pengambilan daftar kontak dan pesan.
     */
    public function index(Conversation $conversation = null)
    {
        $user = Auth::user();
        $allConversations = $this->getConversationsForUser($user);
        $messages = collect();

        // Jika sebuah percakapan dipilih, ambil pesannya
        if ($conversation) {
            $this->authorizeConversationAccess($conversation);
            $messages = $conversation->messages()->with('user')->get();
            // Tandai pesan sebagai sudah dibaca
            $conversation->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
        }

        return view('chat.index', [
            'conversations' => $allConversations,
            'activeConversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Menyimpan pesan baru.
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($conversation);

        $request->validate(['body' => 'required|string']);

        $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        // Setelah mengirim pesan, kembali ke halaman obrolan yang sama
        return redirect()->route('chat.index', $conversation);
    }

    /**
     * Helper untuk mengambil daftar percakapan.
     */
    private function getConversationsForUser(User $user)
    {
        $conversationIds = [];

        if ($user->role === 'parent') {
            $parent = $user->parent;
            $students = $parent->students()->with('schoolClass.homeroomTeacher')->get();
            foreach ($students as $student) {
                if ($student->schoolClass && $student->schoolClass->homeroomTeacher) {
                    $conversation = Conversation::firstOrCreate(['parent_id' => $parent->id, 'teacher_id' => $student->schoolClass->homeroomTeacher->id, 'student_id' => $student->id]);
                    $conversationIds[] = $conversation->id;
                }
            }
        } elseif ($user->role === 'teacher' && $user->teacher?->homeroomClass) {
            $teacher = $user->teacher;
            $students = $teacher->homeroomClass->students()->with('parents')->get();
            foreach ($students as $student) {
                foreach ($student->parents as $parent) {
                    $conversation = Conversation::firstOrCreate(['parent_id' => $parent->id, 'teacher_id' => $teacher->id, 'student_id' => $student->id]);
                    $conversationIds[] = $conversation->id;
                }
            }
        }

        return Conversation::whereIn('id', $conversationIds)->with(['student', 'teacher.user', 'parent.user'])->get();
    }

    /**
     * Helper untuk otorisasi.
     */
    private function authorizeConversationAccess(Conversation $conversation)
    {
        $user = Auth::user();
        if ($user->role === 'parent' && $conversation->parent_id !== $user->parent->id) abort(403);
        if ($user->role === 'teacher' && $conversation->teacher_id !== $user->teacher->id) abort(403);
    }
}
