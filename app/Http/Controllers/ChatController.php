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
use App\Models\AdminConversation;
use Illuminate\Support\Facades\Log;

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
        $activeConversation = null;
        $adminConversation = null;

        if ($user->role === 'parent') {
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $adminConversation = AdminConversation::firstOrCreate(
                    ['parent_id' => $user->parent->id, 'admin_id' => $admin->id]
                );
            }
        }

        if ($conversation && $conversation->exists) {
            $this->authorizeConversationAccess($conversation);
            $messages = $conversation->messages()->with('user')->get();
            $conversation->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
            $activeConversation = $conversation;
        }

        return view('chat.index', [
            'conversations' => $allConversations,
            'adminConversation' => $adminConversation,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Menyimpan pesan baru (Guru <-> Ortu).
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($conversation);
        $request->validate(['body' => 'required|string']);
        $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);
        return redirect()->route('chat.index', $conversation);
    }
    
    /**
     * Menampilkan halaman obrolan dengan admin.
     */
    public function showAdminChat()
    {
        $user = Auth::user();
        if ($user->role !== 'parent') abort(403);
        
        $parent = $user->parent;
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return redirect()->route('chat.index')->with('error', 'Tidak ada admin.');

        $adminConversation = AdminConversation::firstOrCreate(
            ['parent_id' => $parent->id, 'admin_id' => $admin->id]
        );
        
        $teacherConversations = $this->getConversationsForUser($user);
        $messages = $adminConversation->messages()->with('user')->get();
        $adminConversation->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        return view('chat.index', [
            'conversations' => $teacherConversations,
            'adminConversation' => $adminConversation,
            'activeConversation' => $adminConversation, // Set admin chat as active
            'messages' => $messages,
        ]);
    }

    /**
     * Menyimpan pesan dari orang tua ke admin.
     */
    public function storeAdminMessage(Request $request, AdminConversation $conversation)
    {
        if ($conversation->parent_id !== Auth::user()->parent?->id) abort(403);
        $request->validate(['body' => 'required|string']);
        $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);
        return redirect()->route('chat.admin');
    }

    private function getConversationsForUser(User $user)
    {
        $conversationIds = [];
        try {
            if ($user->role === 'parent') {
                $parent = $user->parent;
                if (!$parent) return collect();
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
            Log::error('Gagal mengambil percakapan: ' . $e->getMessage());
            return collect();
        }

        // PERBAIKAN: Menambahkan hitungan pesan yang belum dibaca
        return Conversation::whereIn('id', $conversationIds)
            ->with(['student', 'teacher.user', 'parent.user'])
            ->withCount(['messages as unread_messages_count' => function ($query) {
                $query->where('user_id', '!=', Auth::id())->whereNull('read_at');
            }])
            ->get();
    }

    private function authorizeConversationAccess(Conversation $conversation)
    {
        $user = Auth::user();
        if ($user->role === 'parent' && $conversation->parent_id !== $user->parent?->id) abort(403);
        if ($user->role === 'teacher' && $conversation->teacher_id !== $user->teacher?->id) abort(403);
    }
}
