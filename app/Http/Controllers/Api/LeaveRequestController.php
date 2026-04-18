<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveRequestController extends Controller
{
    /**
     * Get leave requests for the logged-in teacher's homeroom class.
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan Wali Kelas dari kelas manapun.'
            ], 403);
        }

        $classId = $teacher->homeroomClass->id;

        $requests = LeaveRequest::whereHas('student', function ($query) use ($classId) {
                $query->where('school_class_id', $classId);
            })
            ->with(['student:id,name,nis,unique_id'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $start = Carbon::parse($item->start_date);
                $end = Carbon::parse($item->end_date);
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
                    'can_be_processed' => ($duration <= 3 && $item->status == 'pending'),
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    /**
     * Update the status of a leave request (Approve/Reject).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Permohonan ini sudah diproses sebelumnya.'
            ], 422);
        }

        // VALIDASI ATURAN 3 HARI
        $start = Carbon::parse($leaveRequest->start_date);
        $end = Carbon::parse($leaveRequest->end_date);
        $duration = $start->diffInDays($end) + 1;

        if ($duration > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Izin lebih dari 3 hari harus diproses langsung melalui Kepala Sekolah.'
            ], 403);
        }

        $leaveRequest->status = $request->status;
        $leaveRequest->approved_by = $request->user()->id;
        
        if ($request->status == 'rejected') {
            $leaveRequest->rejection_reason = $request->rejection_reason;
        }

        $leaveRequest->save();

        // JIKA DISETUJUI, UPDATE TABEL ATTENDANCE
        if ($request->status == 'approved') {
            $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

            foreach ($period as $date) {
                // Update atau Create data absensi untuk tiap tanggal dalam rentang izin
                Attendance::updateOrCreate(
                    [
                        'student_id' => $leaveRequest->student_id,
                        'attendance_time' => $date->startOfDay(), // Laravel handles date comparison for unique constraints usually
                    ],
                    [
                        'status' => $leaveRequest->type, // 'sakit' atau 'izin'
                        'attendance_time' => $date->setTime(7, 0, 0), // Set default time
                    ]
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan berhasil ' . ($request->status == 'approved' ? 'disetujui' : 'ditolak') . '.',
            'data' => $leaveRequest
        ]);
    }
}
