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
     */
    public function index(Conversation $conversation = null)
    {
        $user = Auth::user();
        $allConversations = $this->getConversationsForUser($user);
        $messages = collect();
        $activeConversation = null;
        $adminConversation = null;
        $adminUser = null; // Inisialisasi variabel adminUser

        if ($user->role === 'parent') {
            // PERBARUAN: Mengambil data admin untuk diteruskan ke view
            $adminUser = User::where('role', 'admin')->first();
            if ($adminUser) {
                $adminConversation = AdminConversation::firstOrCreate(
                    ['parent_id' => $user->parent->id, 'admin_id' => $adminUser->id]
                );
                
                $adminConversation->unread_messages_count = $adminConversation->messages()
                    ->where('user_id', '!=', $user->id)
                    ->whereNull('read_at')
                    ->count();
            }
        }

        if ($conversation && $conversation->exists) {
            $this->authorizeConversationAccess($conversation);
            $messages = $conversation->messages()->with('user')->get()->groupBy(function($message) {
                return $message->created_at->format('Y-m-d');
            });
            $conversation->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
            $activeConversation = $conversation;
        }

        return view('chat.index', [
            'conversations' => $allConversations,
            'adminConversation' => $adminConversation,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
            'adminUser' => $adminUser, // <-- Kirim data admin ke view
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
        $adminUser = User::where('role', 'admin')->first();
        if (!$adminUser) return redirect()->route('chat.index')->with('error', 'Tidak ada admin.');

        $adminConversation = AdminConversation::firstOrCreate(
            ['parent_id' => $parent->id, 'admin_id' => $adminUser->id]
        );
        
        $teacherConversations = $this->getConversationsForUser($user);
        
        $messages = $adminConversation->messages()->with('user')->get()->groupBy(function($message) {
            return $message->created_at->format('Y-m-d');
        });

        $adminConversation->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        return view('chat.index', [
            'conversations' => $teacherConversations,
            'adminConversation' => $adminConversation,
            'activeConversation' => $adminConversation,
            'messages' => $messages,
            'adminUser' => $adminUser,
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

        return Conversation::whereIn('id', $conversationIds)
            ->with(['student', 'teacher.user', 'parent.user'])
            ->addSelect(['*', 'last_message_at' => Message::select('created_at')
                ->whereColumn('conversation_id', 'conversations.id')
                ->orderByDesc('created_at')
                ->limit(1)
            ])
            ->withCount(['messages as unread_messages_count' => function ($query) {
                $query->where('user_id', '!=', Auth::id())->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->get();
    }

    private function authorizeConversationAccess(Conversation $conversation)
    {
        $user = Auth::user();
        if ($user->role === 'parent' && $conversation->parent_id !== $user->parent?->id) abort(403);
        if ($user->role === 'teacher' && $conversation->teacher_id !== $user->teacher?->id) abort(403);
    }
}

