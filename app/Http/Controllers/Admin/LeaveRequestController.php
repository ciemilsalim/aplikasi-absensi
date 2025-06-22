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
     * Menampilkan daftar semua pengajuan izin/sakit.
     */
    public function index()
    {
        $leaveRequests = LeaveRequest::with(['student.schoolClass', 'parent', 'approver'])
                                     ->latest()
                                     ->paginate(10);
                                     
        return view('admin.leave_requests.index', compact('leaveRequests'));
    }

    /**
     * Menyetujui pengajuan dan membuat catatan kehadiran.
     * INI ADALAH LOGIKA UTAMA YANG ANDA MINTA.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        // Pastikan hanya pengajuan yang 'pending' yang bisa diproses
        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        // 1. Update status pengajuan menjadi 'approved'
        $leaveRequest->status = 'approved';
        $leaveRequest->approved_by = Auth::id(); // Catat siapa yang menyetujui
        $leaveRequest->save();

        // 2. Buat perulangan tanggal dari tanggal mulai hingga tanggal selesai
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

        // 3. Untuk setiap tanggal, buat atau perbarui catatan di tabel 'attendances'
        foreach ($period as $date) {
            Attendance::updateOrCreate(
                [
                    // Cari absensi berdasarkan siswa dan tanggal
                    'student_id' => $leaveRequest->student_id,
                    'attendance_time' => $date->startOfDay(), // Gunakan awal hari sebagai penanda waktu
                ],
                [
                    // Atur statusnya menjadi 'izin' atau 'sakit'
                    'status' => $leaveRequest->type,
                    // Pastikan jam pulang dikosongkan jika ada
                    'checkout_time' => null, 
                ]
            );
        }

        return redirect()->route('admin.leave_requests.index')->with('success', 'Pengajuan berhasil disetujui dan data kehadiran telah diperbarui.');
    }

    /**
     * Menolak pengajuan izin/sakit.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        // Pastikan hanya pengajuan yang 'pending' yang bisa diproses
        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:255']);

        $leaveRequest->status = 'rejected';
        $leaveRequest->approved_by = Auth::id(); // Catat siapa yang menolak
        $leaveRequest->rejection_reason = $request->rejection_reason;
        $leaveRequest->save();

        return redirect()->route('admin.leave_requests.index')->with('success', 'Pengajuan berhasil ditolak.');
    }
}
