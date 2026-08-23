<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentRequest;
use App\Models\Notification;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentVerificationController extends Controller
{
    /**
     * Tampilkan daftar pengajuan klaim siswa oleh orang tua (untuk Admin & Wali Kelas).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isTeacher = $user->role === 'teacher' || $user->hasRole('teacher');
        $teacher = $user->teacher;
        $homeroomClass = $teacher ? $teacher->homeroomClass : null;

        $statusFilter = $request->query('status', 'pending');
        $classFilter = $request->query('school_class_id');
        $search = $request->query('search');

        $query = ParentStudentRequest::with(['parent.user', 'student.schoolClass', 'verifier'])
            ->latest();

        // Jika Wali Kelas (bukan Admin), filter khusus untuk kelas bimbingannya atau tampilkan opsi kelasnya
        if ($isTeacher && $homeroomClass && !$user->hasAnyRole(['admin', 'operator'])) {
            $query->whereHas('student', function ($q) use ($homeroomClass) {
                $q->where('school_class_id', $homeroomClass->id);
            });
        } elseif ($classFilter) {
            $query->whereHas('student', function ($q) use ($classFilter) {
                $q->where('school_class_id', $classFilter);
            });
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('parent', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone_number', 'like', "%{$search}%");
                })->orWhereHas('student', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('nis', 'like', "%{$search}%");
                });
            });
        }

        $requests = $query->paginate(15)->withQueryString();
        $classes = SchoolClass::orderBy('name', 'asc')->get();

        // Hitung total pending untuk badge notifikasi
        $pendingCount = ParentStudentRequest::where('status', 'pending');
        if ($isTeacher && $homeroomClass && !$user->hasAnyRole(['admin', 'operator'])) {
            $pendingCount->whereHas('student', function ($q) use ($homeroomClass) {
                $q->where('school_class_id', $homeroomClass->id);
            });
        }
        $pendingCount = $pendingCount->count();

        return view('admin.parent_verification.index', compact(
            'requests', 
            'classes', 
            'statusFilter', 
            'classFilter', 
            'search', 
            'pendingCount',
            'homeroomClass'
        ));
    }

    /**
     * Setujui pengajuan klaim siswa oleh orang tua
     */
    public function approve(ParentStudentRequest $parentRequest)
    {
        $user = Auth::user();
        $teacher = $user->teacher;
        $homeroomClass = $teacher ? $teacher->homeroomClass : null;

        // Cek Otorisasi: Admin BISA menyetujui semua; Wali Kelas BISA menyetujui kelas bimbingannya
        $canApprove = $user->hasAnyRole(['admin', 'operator']) || ($homeroomClass && $parentRequest->student->school_class_id === $homeroomClass->id);

        if (!$canApprove) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menyetujui pengajuan siswa di luar kelas bimbingan Anda.');
        }

        // Setujui dan hubungkan ke tabel pivot parent_student
        $parentRequest->update([
            'status' => 'approved',
            'verified_by_user_id' => $user->id,
            'verified_at' => now(),
        ]);

        $parentRequest->parent->students()->syncWithoutDetaching([$parentRequest->student_id]);

        // Kirim Notifikasi Sistem ke akun Orang Tua
        if ($parentRequest->parent && $parentRequest->parent->user_id) {
            Notification::create([
                'user_id' => $parentRequest->parent->user_id,
                'title' => 'Verifikasi Anak Disetujui',
                'message' => "Pengajuan penghubungan anak {$parentRequest->student->name} ({$parentRequest->student->schoolClass?->name}) telah disetujui oleh pihak sekolah. Data presensi kini aktif penuh.",
                'is_read' => false,
            ]);
        }

        return redirect()->back()->with('success', "Pengajuan untuk siswa {$parentRequest->student->name} berhasil disetujui!");
    }

    /**
     * Tolak pengajuan klaim siswa oleh orang tua
     */
    public function reject(Request $request, ParentStudentRequest $parentRequest)
    {
        $user = Auth::user();
        $teacher = $user->teacher;
        $homeroomClass = $teacher ? $teacher->homeroomClass : null;

        $canReject = $user->hasAnyRole(['admin', 'operator']) || ($homeroomClass && $parentRequest->student->school_class_id === $homeroomClass->id);

        if (!$canReject) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menolak pengajuan siswa ini.');
        }

        $reason = $request->input('notes', 'Data pengajuan belum dapat diverifikasi oleh pihak sekolah.');

        $parentRequest->update([
            'status' => 'rejected',
            'notes' => $reason,
            'verified_by_user_id' => $user->id,
            'verified_at' => now(),
        ]);

        // Putuskan relasi jika sebelumnya sempat terhubung
        $parentRequest->parent->students()->detach($parentRequest->student_id);

        if ($parentRequest->parent && $parentRequest->parent->user_id) {
            Notification::create([
                'user_id' => $parentRequest->parent->user_id,
                'title' => 'Pengajuan Ditolak',
                'message' => "Pengajuan penghubungan anak {$parentRequest->student->name} ditolak. Alasan: {$reason}",
                'is_read' => false,
            ]);
        }

        return redirect()->back()->with('success', "Pengajuan untuk siswa {$parentRequest->student->name} telah ditolak.");
    }
}
