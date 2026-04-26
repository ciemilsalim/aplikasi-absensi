<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ParentLeaveRequestController extends Controller
{
    /**
     * Get leave request history for the parent.
     */
    public function index()
    {
        $parent = Auth::user()->parent;
        
        $requests = LeaveRequest::with(['student', 'approver'])
            ->where('parent_id', $parent->id)
            ->latest()
            ->get()
            ->map(function ($item) {
                $start = \Carbon\Carbon::parse($item->start_date);
                $end = \Carbon\Carbon::parse($item->end_date);
                $duration = $start->diffInDays($end) + 1;

                return [
                    'id' => $item->id,
                    'student_name' => $item->student->name,
                    'type' => $item->type,
                    'start_date' => $item->start_date->format('Y-m-d'),
                    'end_date' => $item->end_date->format('Y-m-d'),
                    'duration' => $duration,
                    'reason' => $item->reason,
                    'status' => $item->status,
                    'attachment_url' => $item->attachment ? asset('storage/' . $item->attachment) : null,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    /**
     * Submit a new leave request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:izin,sakit',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|image|max:2048',
        ]);

        $parent = Auth::user()->parent;
        $student = Student::find($request->student_id);

        // Verify relationship
        if (!$parent->students()->where('students.id', $student->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Siswa tidak terhubung dengan akun Anda.'
            ], 403);
        }

        $leaveRequest = new LeaveRequest();
        $leaveRequest->parent_id = $parent->id;
        $leaveRequest->student_id = $student->id;
        $leaveRequest->type = $request->type;
        $leaveRequest->start_date = $request->start_date;
        $leaveRequest->end_date = $request->end_date;
        $leaveRequest->reason = $request->reason;
        $leaveRequest->status = 'pending';

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('leave_attachments', 'public');
            $leaveRequest->attachment_path = $path;
        }

        $leaveRequest->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan izin berhasil dikirim.',
            'data' => $leaveRequest
        ]);
    }
}
