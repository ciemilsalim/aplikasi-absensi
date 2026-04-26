<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Student;
use App\Models\ParentModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatController extends Controller
{
    /**
     * Get list of conversations for the user (Teacher or Parent).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'parent') {
            return $this->indexForParent($user);
        }

        $teacher = $user->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan Wali Kelas.'
            ], 403);
        }

        $classId = $teacher->homeroomClass->id;

        // Ambil semua siswa di kelas binaan
        $students = Student::where('school_class_id', $classId)
            ->with(['parents', 'conversations' => function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            }])
            ->get()
            ->map(function ($student) use ($teacher) {
                $conversation = $student->conversations->first();
                $parent = $student->parents->first();

                return [
                    'type' => 'parent',
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                    'parent_id' => $parent ? $parent->id : null,
                    'parent_name' => $parent ? $parent->name : null,
                    'parent_photo' => $parent && $parent->photo ? asset('storage/' . $parent->photo) : null,
                    'conversation_id' => $conversation ? $conversation->id : null,
                    'last_message' => $conversation ? $conversation->messages()->latest()->first()?->body ?? '' : '',
                    'last_message_time' => $conversation ? ($conversation->messages()->latest()->first()?->created_at ?? $conversation->created_at)->format('H:i') : '',
                    'unread_count' => $conversation ? $conversation->messages()->where('user_id', '!=', $teacher->user_id)->whereNull('read_at')->count() : 0,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $students
        ]);
    }

    /**
     * Get chat contacts for parent role.
     */
    private function indexForParent($user)
    {
        $parent = $user->parent;
        $students = $parent->students()->with('schoolClass.homeroomTeacher.user')->get();
        
        $contacts = collect();

        // 1. Tambahkan Wali Kelas setiap anak
        foreach ($students as $student) {
            $teacher = $student->schoolClass?->homeroomTeacher;
            if ($teacher) {
                $conv = Conversation::where('parent_id', $parent->id)
                    ->where('teacher_id', $teacher->id)
                    ->where('student_id', $student->id)
                    ->first();

                $contacts->push([
                    'type' => 'teacher',
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->name,
                    'teacher_photo' => $teacher->photo ? asset('storage/' . $teacher->photo) : null,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'conversation_id' => $conv?->id,
                    'last_message' => $conv ? $conv->messages()->latest()->first()?->body ?? '' : '',
                    'last_message_time' => $conv ? ($conv->messages()->latest()->first()?->created_at ?? $conv->created_at)->format('H:i') : '',
                    'unread_count' => $conv ? $conv->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->count() : 0,
                ]);
            }
        }

        // 2. Tambahkan Admin
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $adminConv = \App\Models\AdminConversation::where('parent_id', $parent->id)
                ->where('admin_id', $admin->id)
                ->first();

            $contacts->push([
                'type' => 'admin',
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'admin_photo' => null, // Admin usually generic
                'conversation_id' => $adminConv?->id,
                'is_admin' => true,
                'last_message' => $adminConv ? \App\Models\AdminMessage::where('admin_conversation_id', $adminConv->id)->latest()->first()?->body ?? '' : '',
                'last_message_time' => $adminConv ? (\App\Models\AdminMessage::where('admin_conversation_id', $adminConv->id)->latest()->first()?->created_at ?? $adminConv->created_at)->format('H:i') : '',
                'unread_count' => $adminConv ? \App\Models\AdminMessage::where('admin_conversation_id', $adminConv->id)->where('user_id', '!=', $user->id)->whereNull('read_at')->count() : 0,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $contacts
        ]);
    }


    /**
     * Get messages for a specific conversation.
     */
    public function getMessages(Request $request, $id)
    {
        $isAdmin = $request->get('is_admin') === 'true';

        if ($isAdmin) {
            return $this->getAdminMessages($request, $id);
        }

        $conversation = Conversation::with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->findOrFail($id);

        // Tandai pesan sebagai terbaca
        $conversation->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json([
            'status' => 'success',
            'data' => $conversation->messages->map(function ($m) use ($request) {
                return [
                    'id' => $m->id,
                    'body' => $m->body,
                    'is_me' => $m->user_id == $request->user()->id,
                    'time' => $m->created_at->format('H:i'),
                    'read' => $m->read_at != null,
                ];
            })
        ]);
    }

    private function getAdminMessages(Request $request, $id)
    {
        $conversation = \App\Models\AdminConversation::findOrFail($id);
        
        $messages = \App\Models\AdminMessage::where('admin_conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Tandai sebagai terbaca
        \App\Models\AdminMessage::where('admin_conversation_id', $id)
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json([
            'status' => 'success',
            'data' => $messages->map(function ($m) use ($request) {
                return [
                    'id' => $m->id,
                    'body' => $m->body,
                    'is_me' => $m->user_id == $request->user()->id,
                    'time' => $m->created_at->format('H:i'),
                    'read' => $m->read_at != null,
                ];
            })
        ]);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $isAdmin = $request->get('is_admin') === 'true';

        if ($isAdmin) {
            $message = \App\Models\AdminMessage::create([
                'admin_conversation_id' => $id,
                'user_id' => $request->user()->id,
                'body' => $request->body,
            ]);
        } else {
            $conversation = Conversation::findOrFail($id);
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'body' => $request->body,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'is_me' => true,
                'time' => $message->created_at->format('H:i'),
                'read' => false,
            ]
        ]);
    }


    /**
     * Create or get conversation for a student.
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'parent_id' => 'required|exists:parents,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'admin_id' => 'nullable|exists:users,id',
        ]);

        if ($request->admin_id) {
            $conversation = \App\Models\AdminConversation::firstOrCreate([
                'parent_id' => $request->parent_id,
                'admin_id' => $request->admin_id,
            ]);
        } else {
            $conversation = Conversation::firstOrCreate([
                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id ?? $request->user()->teacher?->id,
                'parent_id' => $request->parent_id,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
            ]
        ]);
    }

}
