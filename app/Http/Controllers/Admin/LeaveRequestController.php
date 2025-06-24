<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonPeriod;

class LeaveRequestController extends Controller
{
    /**
     * Menampilkan daftar pengajuan yang perlu diproses dan riwayatnya.
     */
    public function index()
    {
        // 1. Ambil pengajuan yang masih 'pending'
        $pendingRequests = LeaveRequest::where('status', 'pending')
                                     ->with(['student.schoolClass', 'parent'])
                                     ->oldest() // Proses yang paling lama masuk lebih dulu
                                     ->get();

        // 2. Ambil pengajuan yang sudah diproses (disetujui/ditolak) sebagai riwayat
        $processedRequests = LeaveRequest::whereIn('status', ['approved', 'rejected'])
                                     ->with(['student.schoolClass', 'parent', 'approver'])
                                     ->latest('updated_at') // Tampilkan yang terbaru diproses
                                     ->paginate(10);
                                     
        return view('admin.leave_requests.index', compact('pendingRequests', 'processedRequests'));
    }

    /**
     * Menyetujui pengajuan dan membuat catatan kehadiran.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $leaveRequest->status = 'approved';
        $leaveRequest->approved_by = Auth::id();
        $leaveRequest->save();

        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
        foreach ($period as $date) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $leaveRequest->student_id,
                    'attendance_time' => $date->startOfDay(),
                ],
                [ 'status' => $leaveRequest->type, 'checkout_time' => null ]
            );
        }

        return redirect()->route('admin.leave_requests.index')->with('success', 'Pengajuan berhasil disetujui.');
    }

    /**
     * Menolak pengajuan izin/sakit.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:255']);

        $leaveRequest->status = 'rejected';
        $leaveRequest->approved_by = Auth::id();
        $leaveRequest->rejection_reason = $request->rejection_reason;
        $leaveRequest->save();

        return redirect()->route('admin.leave_requests.index')->with('success', 'Pengajuan berhasil ditolak.');
    }
}
