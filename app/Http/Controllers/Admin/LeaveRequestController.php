<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Admin\AdminLeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected AdminLeaveRequestService $leaveService
    ) {}

    /**
     * Menampilkan daftar pengajuan orang tua, intervensi manual TU/Admin, dan riwayat.
     */
    public function index(Request $request)
    {
        $data = $this->leaveService->getIndexData($request->all());

        return view('admin.leave_requests.index', $data);
    }

    /**
     * Mengambil daftar siswa berdasarkan ID kelas (untuk AJAX request mobile-friendly).
     */
    public function studentsByClass(Request $request)
    {
        $classId = $request->query('class_id') ? (int) $request->query('class_id') : null;
        $students = $this->leaveService->getStudentsByClass($classId);

        return response()->json(['students' => $students]);
    }

    /**
     * Menyimpan intervensi izin/sakit manual dari Admin/TU untuk 1 atau beberapa siswa.
     */
    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:sakit,izin',
            'submission_source' => 'required|in:whatsapp,telepon,surat,lisan,lainnya',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:5120',
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa.',
            'student_ids.min' => 'Pilih minimal satu siswa.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'attachment.max' => 'Ukuran file lampiran maksimal 5MB.',
        ]);

        $createdCount = $this->leaveService->storeManualIntervention(
            $validated,
            $request->file('attachment'),
            Auth::id()
        );

        $sourceLabel = ucfirst($validated['submission_source']);
        return redirect()->route('admin.leave_requests.index')
            ->with('success', "Berhasil menginput intervensi {$validated['type']} via {$sourceLabel} untuk {$createdCount} siswa. Data telah otomatis tersinkron ke Wali Kelas & Guru Mapel.");
    }

    /**
     * Mendapatkan data intervensi untuk modal Edit (JSON).
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        return response()->json($this->leaveService->formatForEdit($leaveRequest));
    }

    /**
     * Memperbarui data intervensi izin manual dan menyinkronkan ulang presensi.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:sakit,izin',
            'submission_source' => 'required|in:whatsapp,telepon,surat,lisan,aplikasi_ortu,lainnya',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:5120',
        ]);

        $this->leaveService->updateManualIntervention(
            $leaveRequest,
            $validated,
            $request->file('attachment'),
            Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data intervensi izin berhasil diperbarui.']);
        }

        return redirect()->route('admin.leave_requests.index')
            ->with('success', 'Data intervensi izin berhasil diperbarui dan presensi telah disinkronkan ulang.');
    }

    /**
     * Menghapus/Membatalkan intervensi izin dan menetralkan kembali presensi siswa.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        $studentName = $this->leaveService->deleteIntervention($leaveRequest);

        return redirect()->route('admin.leave_requests.index')
            ->with('success', "Intervensi izin untuk {$studentName} berhasil dibatalkan. Catatan presensi telah dinetralkan.");
    }

    /**
     * Menyetujui pengajuan izin dari orang tua dan melakukan sinkronisasi otomatis.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        $approved = $this->leaveService->approveParentRequest($leaveRequest, Auth::id());

        if (!$approved) {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        return redirect()->route('admin.leave_requests.index')
            ->with('success', 'Pengajuan izin orang tua berhasil disetujui.');
    }

    /**
     * Menolak pengajuan izin/sakit.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $rejected = $this->leaveService->rejectParentRequest(
            $leaveRequest,
            $validated['rejection_reason'],
            Auth::id()
        );

        if (!$rejected) {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        return redirect()->route('admin.leave_requests.index')
            ->with('success', 'Pengajuan izin berhasil ditolak.');
    }
}
