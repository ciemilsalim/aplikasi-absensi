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
            return redirect()->route('teacher.dashboard')->with('error', 'Halaman ini hanya untuk wali kelas.');
        }

        // Ambil ID siswa dari kelas perwalian
        $studentIds = $teacher->homeroomClass->students->pluck('id');

        // 1. Ambil pengajuan yang masih 'pending' untuk siswa perwalian
        $pendingRequests = LeaveRequest::whereIn('student_id', $studentIds)
                                     ->where('status', 'pending')
                                     ->with(['student', 'parent'])
                                     ->oldest()
                                     ->get();

        // 2. Ambil pengajuan yang sudah diproses sebagai riwayat
        $processedRequests = LeaveRequest::whereIn('student_id', $studentIds)
                                     ->whereIn('status', ['approved', 'rejected'])
                                     ->with(['student', 'parent', 'approver'])
                                     ->latest('updated_at')
                                     ->paginate(10);
                                     
        return view('teacher.leave_requests.index', compact('pendingRequests', 'processedRequests'));
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
     * Helper untuk memastikan guru hanya bisa memproses siswa perwaliannya.
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
