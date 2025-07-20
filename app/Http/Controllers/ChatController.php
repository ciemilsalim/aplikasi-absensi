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
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Menampilkan halaman utama obrolan.
     */
    public function index()
    {
        // Metode ini sekarang hanya bertugas menampilkan view utama.
        // Data akan diambil oleh JavaScript.
        return view('chat.index');
    }

    /**
     * Mengambil daftar percakapan (kontak) untuk pengguna yang sedang login.
     * Metode ini dipanggil oleh JavaScript (fetch).
     */
    public function getConversations()
    {
        $user = Auth::user();
        $conversationIds = [];

        try {
            if ($user->role === 'parent') {
                $parent = $user->parent;
                if (!$parent) {
                    Log::error('Data parent tidak ditemukan untuk user ID: ' . $user->id);
                    return response()->json([], 404); // Kirim respons kosong jika data tidak lengkap
                }

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
        } catch (\Exception $e) {
            Log::error('Gagal mengambil percakapan untuk user ID ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data percakapan.'], 500);
        }

        $conversations = Conversation::whereIn('id', $conversationIds)->with(['student', 'teacher.user', 'parent.user'])->get();
        return response()->json($conversations);
    }

    /**
     * Mengambil semua pesan dari satu percakapan.
     */
    public function getMessages(Conversation $conversation)
    {
        $this->authorizeConversationAccess($conversation);
        $conversation->messages()->where('user_id', '!=', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);
        $messages = $conversation->messages()->with('user')->get();
        return response()->json($messages);
    }

    /**
     * Menyimpan pesan baru.
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($conversation);
        $request->validate(['body' => 'required|string']);
        $message = $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);
        $message->load('user');
        return response()->json($message, 201);
    }

    /**
     * Helper untuk otorisasi akses ke percakapan.
     */
    private function authorizeConversationAccess(Conversation $conversation)
    {
        $user = Auth::user();
        if ($user->role === 'parent' && $conversation->parent_id !== $user->parent?->id) {
            abort(403);
        }
        if ($user->role === 'teacher' && $conversation->teacher_id !== $user->teacher?->id) {
            abort(403);
        }
    }
}
