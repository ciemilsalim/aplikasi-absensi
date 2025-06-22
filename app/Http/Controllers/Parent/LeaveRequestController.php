<?php
namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    /**
     * Menampilkan riwayat pengajuan izin/sakit oleh orang tua.
     */
    public function index()
    {
        $parent = Auth::user()->parent;
        
        $leaveRequests = LeaveRequest::where('parent_id', $parent->id)
                                     ->with('student') // Muat relasi siswa
                                     ->latest()
                                     ->paginate(10);
                                     
        return view('parent.leave_requests.index', compact('leaveRequests'));
    }

    /**
     * Menampilkan form untuk membuat pengajuan baru.
     */
    public function create()
    {
        // Ambil daftar anak yang terhubung dengan orang tua yang sedang login
        $students = Auth::user()->parent->students;

        if ($students->isEmpty()) {
            return redirect()->route('parent.dashboard')->with('error', 'Tidak ada data siswa yang terhubung dengan akun Anda.');
        }

        return view('parent.leave_requests.create', compact('students'));
    }

    /**
     * Menyimpan pengajuan baru ke database.
     */
    public function store(Request $request)
    {
        $parent = Auth::user()->parent;
        $studentIds = $parent->students->pluck('id')->toArray();

        $request->validate([
            'student_id' => ['required', Rule::in($studentIds)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', Rule::in(['sakit', 'izin'])],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // Maks 2MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            // Simpan file di storage/app/public/attachments
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        LeaveRequest::create([
            'student_id' => $request->student_id,
            'parent_id' => $parent->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'type' => $request->type,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
        ]);

        return redirect()->route('parent.leave-requests.index')->with('success', 'Pengajuan berhasil dikirim dan sedang menunggu persetujuan.');
    }
}
