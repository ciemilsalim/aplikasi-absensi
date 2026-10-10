<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Services\Permit\StudentDispensationsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DispensationsController extends Controller
{
    public function __construct(
        protected StudentDispensationsService $service
    ) {}

    /**
     * Menampilkan daftar surat e-dispensasi dan pass card siswa.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'date',
            'status',
            'permit_type',
            'school_class_id',
        ]);

        $query = $this->service->getDispensationsQuery($filters);
        $permits = $query->paginate(15)->withQueryString();
        $dispensations = $permits;

        $today = Carbon::today();
        $holidayReason = $this->service->getHolidayReason($today);
        $isEffectiveSchoolDay = $holidayReason === null;

        $classes = SchoolClass::orderBy('name')->get();
        $presentStudents = $this->service->getPresentStudentsToday();
        $students = $presentStudents; // Hanya siswa yang tercatat hadir hari ini

        $statistics = [
            'active_outside_count' => StudentPermit::whereDate('created_at', $today)
                ->where('status', 'approved')
                ->whereNull('time_in')
                ->count(),
            'early_leave_today' => StudentPermit::whereDate('created_at', $today)
                ->where('permit_type', 'pulang_cepat')
                ->count(),
            'temporary_outside_today' => StudentPermit::whereDate('created_at', $today)
                ->where('permit_type', 'keluar_sementara')
                ->count(),
            'total_dispensations_today' => StudentPermit::whereDate('created_at', $today)
                ->count(),
        ];

        return view('admin.dispensations.index', compact(
            'permits',
            'dispensations',
            'classes',
            'students',
            'presentStudents',
            'filters',
            'statistics',
            'holidayReason',
            'isEffectiveSchoolDay'
        ));
    }

    /**
     * Menyimpan penerbitan surat e-dispensasi baru oleh Guru Piket / Wali Kelas / Admin.
     */
    public function store(Request $request)
    {
        $today = Carbon::today();
        $holidayReason = $this->service->getHolidayReason($today);
        if ($holidayReason) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Izin keluar tidak dapat diterbitkan pada hari libur sekolah ({$holidayReason}). Fitur ini hanya berlaku pada hari efektif sekolah.");
        }

        if ($request->has('student_id') && !$request->has('student_ids')) {
            $request->merge(['student_ids' => [(int) $request->input('student_id')]]);
        }

        if ($request->filled('valid_until')) {
            $val = trim((string) $request->input('valid_until'));
            if (preg_match('/^\d{2}:\d{2}$/', $val)) {
                $request->merge(['valid_until' => Carbon::today()->toDateString() . ' ' . $val . ':00']);
            }
        }

        $validated = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'permit_type'   => 'required|in:pulang_cepat,keluar_sementara,dispensasi_lomba,tugas_sekolah,tugas_luar,lainnya',
            'reason'        => 'required|string|min:4|max:500',
            'valid_until'   => 'nullable|date',
            'notes'         => 'nullable|string|max:255',
            'notify_parent' => 'nullable',
        ]);

        $permits = $this->service->createDispensations($validated, $request->user());

        $firstCode = $permits->first()?->pass_code;
        $count = $permits->count();

        return redirect()->route('admin.dispensations.index')
            ->with('success', "E-Dispensasi berhasil diterbitkan untuk {$count} siswa. Kode Pass Gerbang: {$firstCode}. Siswa dapat langsung menuju pos satpam.");
    }

    /**
     * Menampilkan tiket pass card digital yang siap dicetak / ditunjukkan di gerbang.
     */
    public function print(StudentPermit $dispensation)
    {
        $dispensation->load(['student.schoolClass', 'approver', 'security', 'attendance']);
        $dispensation->ensurePassCode();

        $permitDate = $dispensation->created_at ? $dispensation->created_at->copy()->startOfDay() : Carbon::today();

        // 1. Validasi Hari Efektif Sekolah: Pass card tidak dapat dicetak untuk izin di hari libur
        $holidayReason = $this->service->getHolidayReason($permitDate);
        if ($holidayReason) {
            return redirect()->route('admin.dispensations.index')
                ->with('error', "Pass card izin keluar tidak dapat dicetak karena tanggal tersebut merupakan hari libur sekolah ({$holidayReason}).");
        }

        // 2. Validasi Kehadiran: Siswa HARUS tercatat hadir di hari yang sama
        $attendance = $dispensation->attendance ?: \App\Models\Attendance::where('student_id', $dispensation->student_id)
            ->whereDate('attendance_time', $permitDate)
            ->first();

        if (!$attendance || !in_array($attendance->status, ['tepat_waktu', 'terlambat', 'izin_keluar'])) {
            $studentName = $dispensation->student?->name ?? 'Siswa';
            return redirect()->route('admin.dispensations.index')
                ->with('error', "Pass card izin keluar tidak dapat dicetak karena {$studentName} tidak tercatat hadir di sekolah pada tanggal tersebut.");
        }

        return view('admin.dispensations.print', compact('dispensation'));
    }

    /**
     * Membatalkan surat e-dispensasi yang belum terpakai.
     */
    public function destroy(StudentPermit $dispensation)
    {
        if ($dispensation->status === 'checked_out') {
            return redirect()->back()->with('error', 'Dispensasi tidak dapat dibatalkan karena siswa sudah berada di luar gerbang sekolah.');
        }

        $dispensation->update(['status' => 'cancelled']);

        return redirect()->route('admin.dispensations.index')
            ->with('success', 'E-Dispensasi berhasil dibatalkan.');
    }
}
