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
     * Get list of conversations for the teacher.
     * We'll list all students in the homeroom class and their existing conversations.
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

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
     * Get messages for a specific conversation.
     */
    public function getMessages(Request $request, $id)
    {
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

    /**
     * Send a message.
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $conversation = Conversation::findOrFail($id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

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
            'student_id' => 'required|exists:students,id',
            'parent_id' => 'required|exists:parents,id',
        ]);

        $teacher = $request->user()->teacher;

        $conversation = Conversation::firstOrCreate([
            'student_id' => $request->student_id,
            'teacher_id' => $teacher->id,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
            ]
        ]);
    }
}
