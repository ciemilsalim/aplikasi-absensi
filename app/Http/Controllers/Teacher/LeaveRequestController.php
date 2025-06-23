<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonPeriod;

class LeaveRequestController extends Controller
{
    /**
     * Menampilkan daftar pengajuan izin/sakit dari siswa perwalian.
     */
    public function index()
    {
        $teacher = Auth::user()->teacher;
        
        // Pastikan guru ini adalah wali kelas
        if (!$teacher || !$teacher->homeroomClass) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda bukan wali kelas.');
        }

        // Ambil ID siswa dari kelas perwalian
        $studentIds = $teacher->homeroomClass->students->pluck('id');

        // Ambil pengajuan hanya dari siswa-siswa tersebut
        $leaveRequests = LeaveRequest::whereIn('student_id', $studentIds)
                                     ->with(['student', 'parent', 'approver'])
                                     ->latest()
                                     ->paginate(10);
                                     
        return view('teacher.leave_requests.index', compact('leaveRequests'));
    }

    /**
     * Menyetujui pengajuan dan membuat catatan kehadiran.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        $this->authorizeAction($leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
        foreach ($period as $date) {
            Attendance::updateOrCreate(
                ['student_id' => $leaveRequest->student_id, 'attendance_time' => $date->startOfDay()],
                ['status' => $leaveRequest->type, 'checkout_time' => null]
            );
        }

        return redirect()->route('teacher.leave_requests.index')->with('success', 'Pengajuan berhasil disetujui.');
    }

    /**
     * Menolak pengajuan izin/sakit.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeAction($leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:255']);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('teacher.leave_requests.index')->with('success', 'Pengajuan berhasil ditolak.');
    }

    /**
     * Helper untuk otorisasi aksi.
     */
    private function authorizeAction(LeaveRequest $leaveRequest)
    {
        $teacher = Auth::user()->teacher;
        // Abort jika guru bukan wali kelas dari siswa yang mengajukan
        if (!$teacher || $teacher->homeroomClass?->id !== $leaveRequest->student->school_class_id) {
            abort(403, 'ANDA TIDAK BERHAK MEMPROSES PENGAJUAN INI.');
        }
    }
}
