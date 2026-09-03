<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\SubjectAttendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * Menampilkan daftar pengajuan orang tua, intervensi manual TU/Admin, dan riwayat.
     */
    public function index(Request $request)
    {
        // 1. Ambil pengajuan dari orang tua yang masih 'pending'
        $pendingRequests = LeaveRequest::where('status', 'pending')
            ->where(function($q) {
                $q->whereNull('submission_source')
                  ->orWhere('submission_source', 'aplikasi_ortu');
            })
            ->with(['student.schoolClass', 'parent'])
            ->oldest()
            ->get();

        // 2. Ambil daftar Intervensi Manual oleh Admin/TU (terbaru)
        $manualInterventionsQuery = LeaveRequest::where(function($q) {
                $q->whereNotNull('created_by')
                  ->orWhereNotIn('submission_source', ['aplikasi_ortu']);
            })
            ->with(['student.schoolClass', 'creator', 'approver'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $manualInterventionsQuery->whereHas('student', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $classId = $request->class_id;
            $manualInterventionsQuery->whereHas('student', function($q) use ($classId) {
                $q->where('school_class_id', $classId);
            });
        }

        if ($request->filled('type')) {
            $manualInterventionsQuery->where('type', $request->type);
        }

        $manualInterventions = $manualInterventionsQuery->paginate(15, ['*'], 'manual_page')->withQueryString();

        // 3. Ambil pengajuan ortu yang sudah diproses (disetujui/ditolak)
        $processedRequests = LeaveRequest::whereIn('status', ['approved', 'rejected'])
            ->where(function($q) {
                $q->whereNull('created_by')
                  ->where(function($sq) {
                      $sq->whereNull('submission_source')
                        ->orWhere('submission_source', 'aplikasi_ortu');
                  });
            })
            ->with(['student.schoolClass', 'parent', 'approver'])
            ->latest('updated_at')
            ->paginate(10, ['*'], 'processed_page')->withQueryString();

        // 4. Ambil semua kelas untuk form input cepat intervensi
        $classes = SchoolClass::with(['students' => function($q) {
            $q->orderBy('name', 'asc');
        }])->orderBy('name', 'asc')->get();

        return view('admin.leave_requests.index', compact(
            'pendingRequests', 
            'manualInterventions', 
            'processedRequests', 
            'classes'
        ));
    }

    /**
     * Mengambil daftar siswa berdasarkan ID kelas (untuk AJAX request mobile-friendly).
     */
    public function studentsByClass(Request $request)
    {
        $classId = $request->query('class_id');
        if (!$classId) {
            return response()->json(['students' => []]);
        }

        $students = Student::where('school_class_id', $classId)
            ->select('id', 'name', 'nisn', 'photo', 'gender')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'photo_url' => $student->photo_url,
                    'gender' => $student->gender,
                ];
            });

        return response()->json(['students' => $students]);
    }

    /**
     * Menyimpan intervensi izin/sakit manual dari Admin/TU untuk 1 atau beberapa siswa.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
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

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $createdCount = 0;
        foreach ($request->student_ids as $studentId) {
            $student = Student::with('parents')->find($studentId);
            if (!$student) continue;

            $parentId = $student->parents->first()?->id ?? null;

            $leaveRequest = LeaveRequest::create([
                'student_id' => $student->id,
                'parent_id' => $parentId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'type' => $request->type,
                'submission_source' => $request->submission_source,
                'reason' => $request->reason,
                'attachment' => $attachmentPath,
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            // Sinkronisasi otomatis ke Presensi Harian (Wali Kelas) dan Presensi Mapel (Guru Mapel)
            $this->syncAttendancesForLeave($leaveRequest);
            $createdCount++;
        }

        $sourceLabel = ucfirst($request->submission_source);
        return redirect()->route('admin.leave_requests.index')
            ->with('success', "Berhasil menginput intervensi {$request->type} via {$sourceLabel} untuk {$createdCount} siswa. Data telah otomatis tersinkron ke Wali Kelas & Guru Mapel.");
    }

    /**
     * Mendapatkan data intervensi untuk modal Edit (JSON).
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['student.schoolClass']);
        return response()->json([
            'id' => $leaveRequest->id,
            'student_id' => $leaveRequest->student_id,
            'student_name' => $leaveRequest->student->name,
            'class_name' => $leaveRequest->student->schoolClass->name ?? '-',
            'start_date' => $leaveRequest->start_date->format('Y-m-d'),
            'end_date' => $leaveRequest->end_date->format('Y-m-d'),
            'type' => $leaveRequest->type,
            'submission_source' => $leaveRequest->submission_source ?? 'whatsapp',
            'reason' => $leaveRequest->reason,
            'attachment_url' => $leaveRequest->attachment ? asset('storage/' . $leaveRequest->attachment) : null,
        ]);
    }

    /**
     * Memperbarui data intervensi izin manual dan menyinkronkan ulang presensi.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:sakit,izin',
            'submission_source' => 'required|in:whatsapp,telepon,surat,lisan,aplikasi_ortu,lainnya',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:5120',
        ]);

        // Revert presensi lama terlebih dahulu
        $this->revertAttendancesForLeave($leaveRequest);

        if ($request->hasFile('attachment')) {
            if ($leaveRequest->attachment && Storage::disk('public')->exists($leaveRequest->attachment)) {
                Storage::disk('public')->delete($leaveRequest->attachment);
            }
            $leaveRequest->attachment = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $leaveRequest->start_date = $request->start_date;
        $leaveRequest->end_date = $request->end_date;
        $leaveRequest->type = $request->type;
        $leaveRequest->submission_source = $request->submission_source;
        $leaveRequest->reason = $request->reason;
        $leaveRequest->status = 'approved';
        $leaveRequest->approved_by = Auth::id();
        $leaveRequest->save();

        // Sinkronisasi ulang dengan data yang baru
        $this->syncAttendancesForLeave($leaveRequest);

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
        // Netralkan data presensi yang pernah dibuat oleh izin ini
        $this->revertAttendancesForLeave($leaveRequest);

        if ($leaveRequest->attachment && Storage::disk('public')->exists($leaveRequest->attachment)) {
            Storage::disk('public')->delete($leaveRequest->attachment);
        }

        $studentName = $leaveRequest->student->name ?? 'Siswa';
        $leaveRequest->delete();

        return redirect()->route('admin.leave_requests.index')
            ->with('success', "Intervensi izin untuk {$studentName} berhasil dibatalkan. Catatan presensi telah dinetralkan.");
    }

    /**
     * Menyetujui pengajuan izin dari orang tua dan melakukan sinkronisasi otomatis.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $leaveRequest->status = 'approved';
        $leaveRequest->approved_by = Auth::id();
        $leaveRequest->save();

        // Sinkronisasi otomatis ke Presensi Harian & Jadwal Mapel
        $this->syncAttendancesForLeave($leaveRequest);

        return redirect()->route('admin.leave_requests.index')->with('success', 'Pengajuan izin orang tua berhasil disetujui.');
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

        return redirect()->route('admin.leave_requests.index')->with('success', 'Pengajuan izin berhasil ditolak.');
    }

    /**
     * Helper privat: Sinkronisasi presensi harian (Attendance) dan mapel (SubjectAttendance).
     */
    private function syncAttendancesForLeave(LeaveRequest $leaveRequest): void
    {
        $student = $leaveRequest->student;
        if (!$student) return;

        $classId = $student->school_class_id;

        // Ambil tahun ajaran dan semester aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeSemester = Semester::where('is_active', true)->first();
        $yearId = $activeYear?->id;
        $semesterId = $activeSemester?->id;

        $holidays = Calendar::getHolidaysInRange($leaveRequest->start_date, $leaveRequest->end_date);
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

        $sourceText = match($leaveRequest->submission_source) {
            'whatsapp' => 'WA Ortu',
            'telepon' => 'Telepon Ortu',
            'surat' => 'Surat',
            'lisan' => 'Lisan/Langsung',
            default => 'Izin Resmi'
        };

        foreach ($period as $date) {
            // Lewati hari akhir pekan dan hari libur kalender sekolah
            if ($date->isWeekend() || Calendar::isDateInHolidays($date, $holidays)) {
                continue;
            }

            $currentDate = $date->copy();

            // 1. Presensi Harian (Wali Kelas & Sistem Utama)
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'attendance_time' => $currentDate->startOfDay(),
                ],
                [
                    'status' => $leaveRequest->type,
                    'checkout_time' => null,
                    'academic_year_id' => $yearId,
                    'semester_id' => $semesterId,
                ]
            );

            // 2. Presensi Mapel (Guru Mata Pelajaran)
            if ($classId) {
                $dayOfWeek = $currentDate->isoWeekday(); // 1: Senin s/d 7: Minggu

                // Cari seluruh jadwal di kelas ini pada hari tersebut
                $schedules = Schedule::where('school_class_id', $classId)
                    ->where('day_of_week', $dayOfWeek)
                    ->get();

                // Juga gabungkan jadwal dari teaching assignment jika didefinisikan lewat relasi tersebut
                $assignmentSchedules = Schedule::whereHas('teachingAssignment', function($q) use ($classId) {
                        $q->where('school_class_id', $classId);
                    })
                    ->where('day_of_week', $dayOfWeek)
                    ->get();

                $allSchedules = $schedules->merge($assignmentSchedules)->unique('id');

                foreach ($allSchedules as $schedule) {
                    $teacherId = $schedule->teacher_id ?? $schedule->teachingAssignment?->teacher_id;
                    $sessionYearId = $schedule->academic_year_id ?? $yearId;
                    $sessionSemesterId = $schedule->semester_id ?? $semesterId;

                    $sessionTime = $currentDate->copy()->setTime(7, 30, 0);

                    // Cek jika sudah ada record pada tanggal tersebut
                    $existingAttendance = SubjectAttendance::where('schedule_id', $schedule->id)
                        ->where('student_id', $student->id)
                        ->whereDate('created_at', $currentDate)
                        ->first();

                    if ($existingAttendance) {
                        $existingAttendance->status = $leaveRequest->type;
                        $existingAttendance->notes = "[Intervensi {$sourceText}] " . $leaveRequest->reason;
                        $existingAttendance->teacher_id = $teacherId ?: $existingAttendance->teacher_id;
                        $existingAttendance->save();
                    } else {
                        SubjectAttendance::create([
                            'schedule_id' => $schedule->id,
                            'student_id' => $student->id,
                            'teacher_id' => $teacherId,
                            'status' => $leaveRequest->type,
                            'notes' => "[Intervensi {$sourceText}] " . $leaveRequest->reason,
                            'academic_year_id' => $sessionYearId,
                            'semester_id' => $sessionSemesterId,
                            'created_at' => $sessionTime,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Helper privat: Menghapus/menetralkan presensi harian & mapel saat izin dibatalkan/diedit.
     */
    private function revertAttendancesForLeave(LeaveRequest $leaveRequest): void
    {
        $studentId = $leaveRequest->student_id;
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

        foreach ($period as $date) {
            // Hapus presensi harian jika statusnya masih sama dengan jenis izin ini
            Attendance::where('student_id', $studentId)
                ->whereDate('attendance_time', $date)
                ->where('status', $leaveRequest->type)
                ->delete();

            // Hapus atau reset presensi mapel yang dibuat/diubah intervensi ini
            SubjectAttendance::where('student_id', $studentId)
                ->whereDate('created_at', $date)
                ->where('status', $leaveRequest->type)
                ->delete();
        }
    }
}
