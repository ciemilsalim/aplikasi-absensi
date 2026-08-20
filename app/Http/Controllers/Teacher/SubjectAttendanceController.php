<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectAttendance;
use App\Models\TeachingAssignment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Cocurricular;
use App\Models\Teacher;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SubjectAttendanceController extends Controller
{
    /**
     * Memeriksa otorisasi guru terhadap jadwal (baik reguler maupun kokurikuler).
     */
    private function isTeacherAuthorized(Schedule $schedule, Teacher $teacher): bool
    {
        if ($schedule->isCocurricular()) {
            if ($schedule->teacher_id === $teacher->id) {
                return true;
            }
            $schedule->loadMissing('cocurricular.teachers');
            return $schedule->cocurricular && $schedule->cocurricular->teachers->contains('id', $teacher->id);
        }

        $schedule->loadMissing('teachingAssignment');
        return $schedule->teachingAssignment && $schedule->teachingAssignment->teacher_id === $teacher->id;
    }

    /**
     * Mengambil ID kelas target dari jadwal.
     */
    private function getScheduleClassId(Schedule $schedule): ?int
    {
        if ($schedule->isCocurricular()) {
            return $schedule->school_class_id;
        }
        return $schedule->teachingAssignment?->school_class_id;
    }

    /**
     * Menampilkan halaman pemindai QR & Wajah untuk absensi mata pelajaran / kokurikuler.
     */
    public function showScanner(Request $request, Schedule $schedule)
    {
        $teacher = Auth::user()?->teacher;

        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        if (!$this->isTeacherAuthorized($schedule, $teacher)) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak berhak mengakses sesi presensi ini.');
        }

        $classId = $this->getScheduleClassId($schedule);
        if (!$classId) {
            return redirect()->route('teacher.dashboard')->with('error', 'Target kelas untuk jadwal ini tidak valid.');
        }

        $dateStr = $request->input('date');
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();

        $subjectAttendancesToday = SubjectAttendance::where('schedule_id', $schedule->id)
            ->whereDate('created_at', $selectedDate)
            ->with('student')
            ->get();

        $attendedStudents = $subjectAttendancesToday->where('status', 'hadir');
        $studentsOnLeave = $subjectAttendancesToday->whereIn('status', ['sakit', 'izin']);
        $studentIdsWithRecord = $subjectAttendancesToday->pluck('student_id');

        $studentsWithoutNotice = Student::where('school_class_id', $classId)
            ->whereNotIn('id', $studentIdsWithRecord)
            ->orderBy('name', 'asc')
            ->get();

        $studentsForFaceRecognition = Student::where('school_class_id', $classId)
            ->whereNotNull('photo')
            ->select('id', 'unique_id', 'name', 'photo', 'face_descriptor')
            ->get()
            ->map(function ($student) {
                return [
                    'unique_id' => $student->unique_id,
                    'name' => $student->name,
                    'photo_url' => asset('storage/' . $student->photo),
                    'face_descriptor' => $student->face_descriptor,
                ];
            });

        // Catatan Anekdot siswa pada jadwal dan tanggal ini (aman jika tabel belum dimigrasi)
        $anecdotesToday = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('student_anecdotes')) {
                $anecdotesToday = \App\Models\StudentAnecdote::where('schedule_id', $schedule->id)
                    ->whereDate('date', $selectedDate)
                    ->get()
                    ->keyBy('student_id');
            }
        } catch (\Throwable $e) {
            $anecdotesToday = collect();
        }

        return view('teacher.subject_attendance_scanner', compact(
            'schedule', 
            'attendedStudents', 
            'studentsOnLeave', 
            'studentsWithoutNotice', 
            'studentsForFaceRecognition', 
            'selectedDate',
            'anecdotesToday'
        ));
    }

    /**
     * Menyimpan data absensi dari hasil pemindaian QR / Wajah.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string',
            'schedule_id' => 'required|integer|exists:schedules,id',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $schedule = Schedule::with(['teachingAssignment', 'cocurricular.teachers', 'schoolClass'])->find($request->schedule_id);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan.'], 404);
        }

        if (!$this->isTeacherAuthorized($schedule, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $classId = $this->getScheduleClassId($schedule);
        
        $qrData = $request->student_unique_id;
        $parts = explode('-', $qrData, 2);
        
        if (count($parts) === 2) {
            $student = Student::where('nis', $parts[0])->where('unique_id', $parts[1])->first();
        } else {
            $student = Student::where('unique_id', $qrData)->orWhere('nis', $qrData)->first();
        }

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak ditemukan atau QR Code tidak valid.'], 404);
        }

        if ($student->school_class_id !== $classId) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak terdaftar di kelas ini.'], 422);
        }

        $dateStr = $request->input('date');
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $attendanceDateTime = $selectedDate->copy()->setTimeFrom(now());

        // Cek Akhir Pekan
        if ($selectedDate->isWeekend()) {
            return response()->json(['success' => false, 'message' => 'Absensi tidak dapat dilakukan pada akhir pekan.'], 422);
        }

        // Cek Hari Libur
        $holiday = \App\Models\Calendar::where('is_holiday', true)
            ->whereDate('start_date', '<=', $selectedDate)
            ->where(function ($query) use ($selectedDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $selectedDate);
            })->first();

        if ($holiday) {
            return response()->json(['success' => false, 'message' => 'Hari ini libur: ' . $holiday->title], 422);
        }

        // Cek Jam Kerja
        $settings = Setting::pluck('value', 'key');
        $jamMasuk = $settings->get('jam_masuk', '07:30');
        $jamPulang = $settings->get('jam_pulang_guru') ?: $settings->get('jam_pulang', '16:00');
        
        $now = now();
        $startTime = Carbon::createFromTimeString($jamMasuk)->subMinutes(30);
        $endTime = Carbon::createFromTimeString($jamPulang)->addHours(1);
        
        if (!$now->between($startTime, $endTime)) {
            return response()->json([
                'success' => false, 
                'message' => 'Absensi hanya dapat dilakukan pada jam sekolah/kerja (' . $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ').'
            ], 422);
        }

        $existingAttendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $selectedDate)
            ->first();

        if ($existingAttendance) {
            return response()->json(['success' => false, 'message' => 'Siswa sudah diabsen sebelumnya.'], 409);
        }

        $attendance = new SubjectAttendance([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'hadir',
        ]);
        $attendance->created_at = $attendanceDateTime;
        $attendance->updated_at = $attendanceDateTime;
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran ' . $student->name . ' berhasil dicatat.',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'status' => 'hadir',
                'time' => $attendance->created_at->format('H:i'),
                'photo_url' => $student->photo_url,
            ]
        ]);
    }

    /**
     * Menampilkan halaman riwayat absensi mata pelajaran & kokurikuler.
     */
    public function showHistory(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $cocurricularIds = $teacher->cocurriculars()->pluck('cocurriculars.id');

        $attendances = SubjectAttendance::with([
            'student', 
            'schedule.teachingAssignment.subject', 
            'schedule.teachingAssignment.schoolClass',
            'schedule.cocurricular',
            'schedule.schoolClass',
            'schedule.teacher'
        ])
            ->where(function ($q) use ($teacher, $cocurricularIds) {
                $q->where('teacher_id', $teacher->id)
                  ->orWhereHas('schedule', function ($sq) use ($teacher, $cocurricularIds) {
                      $sq->where('teacher_id', $teacher->id)
                         ->orWhereIn('cocurricular_id', $cocurricularIds);
                  });
            })
            ->whereDate('created_at', $selectedDate)
            ->get()
            ->groupBy('schedule_id');

        return view('teacher.subject_attendance_history', compact('attendances', 'selectedDate'));
    }

    /**
     * Menandai status siswa secara manual oleh guru mapel / fasilitator kokurikuler.
     */
    public function markManualAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'schedule_id' => 'required|exists:schedules,id',
            'status' => 'required|in:hadir,sakit,izin,alpa,bolos',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $student = Student::find($request->student_id);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $schedule = Schedule::with(['teachingAssignment', 'cocurricular.teachers', 'schoolClass'])->find($request->schedule_id);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan.'], 404);
        }

        if (!$this->isTeacherAuthorized($schedule, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $classId = $this->getScheduleClassId($schedule);
        if ($student->school_class_id !== $classId) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak terdaftar di kelas ini.'], 422);
        }
        
        $dateStr = $request->input('date');
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $attendanceDateTime = $selectedDate->copy()->setTimeFrom(now());

        if ($selectedDate->isWeekend()) {
            return response()->json(['success' => false, 'message' => 'Absensi tidak dapat dilakukan pada akhir pekan.'], 422);
        }

        $holiday = \App\Models\Calendar::where('is_holiday', true)
            ->whereDate('start_date', '<=', $selectedDate)
            ->where(function ($query) use ($selectedDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $selectedDate);
            })->first();

        if ($holiday) {
            return response()->json(['success' => false, 'message' => 'Hari ini libur: ' . $holiday->title], 422);
        }

        $settings = Setting::pluck('value', 'key');
        $jamMasuk = $settings->get('jam_masuk', '07:30');
        $jamPulang = $settings->get('jam_pulang_guru') ?: $settings->get('jam_pulang', '16:00');
        
        $now = now();
        $startTime = Carbon::createFromTimeString($jamMasuk)->subMinutes(30);
        $endTime = Carbon::createFromTimeString($jamPulang)->addHours(1);
        
        if (!$now->between($startTime, $endTime)) {
            return response()->json([
                'success' => false, 
                'message' => 'Absensi hanya dapat dilakukan pada jam sekolah/kerja (' . $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ').'
            ], 422);
        }

        $attendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $selectedDate)
            ->first();

        if ($attendance) {
            $attendance->update([
                'status' => $request->status,
                'teacher_id' => $teacher->id,
            ]);
        } else {
            $attendance = new SubjectAttendance([
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => $request->status,
            ]);
            $attendance->created_at = $attendanceDateTime;
            $attendance->updated_at = $attendanceDateTime;
            $attendance->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status ' . $student->name . ' berhasil diubah menjadi ' . ucfirst($request->status),
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'status' => $request->status,
                'time' => $attendanceDateTime->format('H:i'),
                'photo_url' => $student->photo_url,
            ]
        ]);
    }

    /**
     * Menampilkan halaman formulir untuk filter rekap absensi.
     */
    public function showReportForm()
    {
        $user = Auth::user();
        $isExecutive = $user->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster']);
        $teacher = $user->teacher;

        if (!$teacher && !$isExecutive) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        if ($isExecutive) {
            $classes = SchoolClass::orderBy('name', 'asc')->pluck('name', 'id');
            $subjects = Subject::orderBy('name', 'asc')->pluck('name', 'id');
            $cocurricularProjects = DB::table('cocurriculars')->pluck('title', 'id');
            $cocurricularClasses = SchoolClass::orderBy('name', 'asc')->pluck('name', 'id');
        } else {
            // Mapel Reguler
            $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->get();

            $classes = $assignments->pluck('schoolClass.name', 'schoolClass.id')->filter()->unique();
            $subjects = $assignments->pluck('subject.name', 'subject.id')->filter()->unique();

            // Kokurikuler
            $cocurricularIds = $teacher->cocurriculars()->pluck('cocurriculars.id');
            $cocurricularSchedules = Schedule::with(['cocurricular', 'schoolClass'])
                ->where('schedule_type', 'cocurricular')
                ->where(function ($query) use ($teacher, $cocurricularIds) {
                    $query->where('teacher_id', $teacher->id)
                          ->orWhereIn('cocurricular_id', $cocurricularIds);
                })
                ->get();

            $cocurricularProjects = $cocurricularSchedules->pluck('cocurricular.title', 'cocurricular.id')->filter()->unique();
            $cocurricularClasses = $cocurricularSchedules->pluck('schoolClass.name', 'schoolClass.id')->filter()->unique();
        }

        return view('teacher.report_form', compact(
            'classes', 
            'subjects', 
            'cocurricularProjects', 
            'cocurricularClasses'
        ));
    }

    /**
     * Menampilkan halaman preview rekap absensi yang bisa diedit.
     */
    public function showReportPreview(Request $request)
    {
        $activityType = $request->input('activity_type', 'regular');

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'cocurricular_id' => 'required|exists:cocurriculars,id',
            ]);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
            ]);
        }

        $user = Auth::user();
        $isExecutive = $user->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster']);
        $teacher = $user->teacher;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $schoolClassId = $request->school_class_id;

        $students = Student::where('school_class_id', $schoolClassId)->orderBy('name')->get();
        $classInfo = SchoolClass::find($schoolClassId);

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $cocurricularId = $request->cocurricular_id;
            $cocurricularInfo = Cocurricular::find($cocurricularId);
            $subjectInfo = null;

            $schedules = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $cocurricularId)
                ->where('school_class_id', $schoolClassId)
                ->get();

            if ($schedules->isEmpty()) {
                return back()->with('error', 'Jadwal kokurikuler tidak ditemukan untuk kombinasi kelas dan proyek ini.');
            }

            $scheduleIds = $schedules->pluck('id')->toArray();
            $scheduleDays = $schedules->pluck('day_of_week')->unique()->toArray();

            $attendances = SubjectAttendance::with('student')
                ->whereIn('schedule_id', $scheduleIds)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->get();
        } else {
            $subjectId = $request->subject_id;
            $subjectInfo = Subject::find($subjectId);
            $cocurricularInfo = null;

            $assignmentQuery = TeachingAssignment::where('school_class_id', $schoolClassId)
                ->where('subject_id', $subjectId);

            if (!$isExecutive && $teacher) {
                $assignmentQuery->where('teacher_id', $teacher->id);
            }

            $assignment = $assignmentQuery->first();

            if (!$assignment) {
                return back()->with('error', 'Jadwal mengajar tidak ditemukan untuk kombinasi ini.');
            }

            $scheduleDays = Schedule::where('teaching_assignment_id', $assignment->id)
                ->pluck('day_of_week')
                ->unique()
                ->toArray();

            $attendanceQuery = SubjectAttendance::with('student')
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->whereHas('schedule.teachingAssignment', function ($query) use ($schoolClassId, $subjectId) {
                    $query->where('school_class_id', $schoolClassId)
                        ->where('subject_id', $subjectId);
                });

            if (!$isExecutive && $teacher) {
                $attendanceQuery->where('teacher_id', $teacher->id);
            }

            $attendances = $attendanceQuery->get();
        }

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);

        $period = collect(CarbonPeriod::create($startDate, $endDate)->filter(function ($date) use ($scheduleDays, $holidays) {
            return in_array($date->dayOfWeekIso, $scheduleDays) && !\App\Models\Calendar::isDateInHolidays($date, $holidays);
        }));

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->created_at)->format('Y-m-d');
            $attendanceData[$attendance->student_id][$date] = $attendance->status;
        }

        $totalEffDays = $period->count();
        $attendanceSummary = [];
        $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalBolos = 0;

        foreach ($students as $student) {
            $h = 0; $s = 0; $i = 0; $a = 0; $b = 0;
            if (isset($attendanceData[$student->id])) {
                foreach ($period as $date) {
                    $dStr = $date->format('Y-m-d');
                    $st = $attendanceData[$student->id][$dStr] ?? null;
                    if ($st === 'hadir') $h++;
                    elseif ($st === 'sakit') $s++;
                    elseif ($st === 'izin') $i++;
                    elseif ($st === 'alpa') $a++;
                    elseif ($st === 'bolos') $b++;
                }
            }
            $attendanceSummary[$student->id] = [
                'hadir' => $h,
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
                'bolos' => $b,
                'persen' => $totalEffDays > 0 ? round(($h / $totalEffDays) * 100, 0) : 0,
            ];
            $totalHadir += $h;
            $totalSakit += $s;
            $totalIzin += $i;
            $totalAlpa += $a;
            $totalBolos += $b;
        }

        $totalPossible = count($students) * $totalEffDays;
        $classAvgPercent = $totalPossible > 0 ? round(($totalHadir / $totalPossible) * 100, 1) : 0;

        $requestInputs = $request->only(['start_date', 'end_date', 'school_class_id', 'subject_id', 'cocurricular_id', 'activity_type']);

        return view('teacher.report_preview', compact(
            'students',
            'period',
            'attendanceData',
            'attendanceSummary',
            'totalEffDays',
            'totalHadir',
            'totalSakit',
            'totalIzin',
            'totalAlpa',
            'totalBolos',
            'classAvgPercent',
            'classInfo',
            'subjectInfo',
            'cocurricularInfo',
            'startDate',
            'endDate',
            'requestInputs',
            'activityType'
        ));
    }

    /**
     * Memperbarui data kehadiran dari halaman preview rekap.
     */
    public function updateReportAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|string',
            'school_class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'cocurricular_id' => 'nullable|exists:cocurriculars,id',
        ]);

        $teacher = Auth::user()->teacher;
        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeekIso;

        if ($request->filled('cocurricular_id')) {
            $schedule = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $request->cocurricular_id)
                ->where('school_class_id', $request->school_class_id)
                ->where('day_of_week', $dayOfWeek)
                ->first();
        } else {
            $assignment = TeachingAssignment::where('teacher_id', $teacher->id)
                ->where('school_class_id', $request->school_class_id)
                ->where('subject_id', $request->subject_id)
                ->first();

            if (!$assignment) {
                return back()->with('error', 'Jadwal mengajar tidak ditemukan.');
            }

            $schedule = Schedule::where('teaching_assignment_id', $assignment->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();
        }

        if (!$schedule) {
            return back()->with('error', 'Tidak ada jadwal untuk hari yang dipilih. Data tidak dapat dibuat.');
        }

        $attendance = SubjectAttendance::where('student_id', $request->student_id)
            ->where('schedule_id', $schedule->id)
            ->whereDate('created_at', $date)
            ->first();

        if ($request->status === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return back()->with('success', 'Data kehadiran berhasil dihapus.');
            }
            return back()->with('info', 'Data kehadiran tidak ditemukan untuk dihapus.');
        }

        if ($attendance) {
            $attendance->update([
                'status' => $request->status,
                'teacher_id' => $teacher->id,
            ]);
        } else {
            SubjectAttendance::create([
                'student_id' => $request->student_id,
                'schedule_id' => $schedule->id,
                'status' => $request->status,
                'teacher_id' => $teacher->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        return back()->with('success', 'Data kehadiran berhasil diperbarui.');
    }

    /**
     * Menghasilkan dan menampilkan halaman cetak rekap absensi.
     */
    public function printReport(Request $request)
    {
        $activityType = $request->input('activity_type', 'regular');

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'cocurricular_id' => 'required|exists:cocurriculars,id',
            ]);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
            ]);
        }

        $teacher = Auth::user()->teacher;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $schoolClassId = $request->school_class_id;

        $students = Student::where('school_class_id', $schoolClassId)->orderBy('name')->get();
        $classInfo = SchoolClass::find($schoolClassId);

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $cocurricularId = $request->cocurricular_id;
            $cocurricularInfo = Cocurricular::find($cocurricularId);
            $subjectInfo = null;

            $schedules = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $cocurricularId)
                ->where('school_class_id', $schoolClassId)
                ->get();

            $scheduleIds = $schedules->pluck('id')->toArray();
            $scheduleDays = $schedules->pluck('day_of_week')->unique()->toArray();

            $attendances = SubjectAttendance::with('student')
                ->whereIn('schedule_id', $scheduleIds)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->get();
        } else {
            $subjectId = $request->subject_id;
            $subjectInfo = Subject::find($subjectId);
            $cocurricularInfo = null;

            $assignment = TeachingAssignment::where('teacher_id', $teacher->id)
                ->where('school_class_id', $schoolClassId)
                ->where('subject_id', $subjectId)
                ->first();

            if (!$assignment) {
                return back()->with('error', 'Jadwal mengajar tidak ditemukan untuk kombinasi ini.');
            }

            $scheduleDays = Schedule::where('teaching_assignment_id', $assignment->id)
                ->pluck('day_of_week')
                ->unique()
                ->toArray();

            $attendances = SubjectAttendance::with('student')
                ->where('teacher_id', $teacher->id)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->whereHas('schedule.teachingAssignment', function ($query) use ($schoolClassId, $subjectId) {
                    $query->where('school_class_id', $schoolClassId)
                        ->where('subject_id', $subjectId);
                })
                ->get();
        }

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);

        $period = collect(CarbonPeriod::create($startDate, $endDate)->filter(function ($date) use ($scheduleDays, $holidays) {
            return in_array($date->dayOfWeekIso, $scheduleDays) && !\App\Models\Calendar::isDateInHolidays($date, $holidays);
        }));

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->created_at)->format('Y-m-d');
            $attendanceData[$attendance->student_id][$date] = $attendance->status;
        }

        $settings = Setting::whereIn('key', [
            'app_logo', 'school_name', 'school_address', 'school_phone', 
            'school_email', 'school_headmaster_name', 'school_headmaster_nip'
        ])->get();
        
        $schoolIdentity = [
            'logo' => $settings->firstWhere('key', 'app_logo')->value ?? null,
            'name' => $settings->firstWhere('key', 'school_name')->value ?? null,
            'address' => $settings->firstWhere('key', 'school_address')->value ?? null,
            'phone' => $settings->firstWhere('key', 'school_phone')->value ?? null,
            'email' => $settings->firstWhere('key', 'school_email')->value ?? null,
            'headmaster_name' => $settings->firstWhere('key', 'school_headmaster_name')->value ?? null,
            'headmaster_nip' => $settings->firstWhere('key', 'school_headmaster_nip')->value ?? null,
        ];

        $requestInputs = $request->only(['start_date', 'end_date', 'school_class_id', 'subject_id', 'cocurricular_id', 'activity_type']);

        return view('teacher.report_print', compact(
            'students',
            'period',
            'attendanceData',
            'classInfo',
            'subjectInfo',
            'cocurricularInfo',
            'startDate',
            'endDate',
            'schoolIdentity',
            'teacher',
            'requestInputs',
            'activityType'
        ));
    }

    /**
     * Menampilkan halaman UI untuk Grafik Analitik Mapel & Kokurikuler.
     */
    public function charts()
    {
        $teacher = Auth::user()->teacher;

        $assignments = TeachingAssignment::with(['schoolClass.students', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass.name', 'schoolClass.id')->filter()->unique();
        $subjects = $assignments->pluck('subject.name', 'subject.id')->filter()->unique();
        
        $studentsMap = [];
        foreach ($assignments as $assignment) {
            if ($assignment->schoolClass) {
                $studentsMap[$assignment->schoolClass->id] = $assignment->schoolClass->students->select('id', 'name')->toArray();
            }
        }

        // Kokurikuler
        $cocurricularIds = $teacher->cocurriculars()->pluck('cocurriculars.id');
        $cocurricularSchedules = Schedule::with(['cocurricular', 'schoolClass.students'])
            ->where('schedule_type', 'cocurricular')
            ->where(function ($query) use ($teacher, $cocurricularIds) {
                $query->where('teacher_id', $teacher->id)
                      ->orWhereIn('cocurricular_id', $cocurricularIds);
            })
            ->get();

        $cocurricularProjects = $cocurricularSchedules->pluck('cocurricular.title', 'cocurricular.id')->filter()->unique();
        $cocurricularClasses = $cocurricularSchedules->pluck('schoolClass.name', 'schoolClass.id')->filter()->unique();

        foreach ($cocurricularSchedules as $sched) {
            if ($sched->schoolClass && !isset($studentsMap[$sched->schoolClass->id])) {
                $studentsMap[$sched->schoolClass->id] = $sched->schoolClass->students->select('id', 'name')->toArray();
            }
        }

        return view('teacher.subject_charts', compact(
            'classes', 
            'subjects', 
            'cocurricularProjects', 
            'cocurricularClasses', 
            'studentsMap'
        ));
    }

    /**
     * Memproses data dan mengembalikan JSON untuk Chart.js (Mendukung Mapel & Kokurikuler).
     */
    public function chartData(Request $request)
    {
        $activityType = $request->input('activity_type', 'regular');

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'cocurricular_id' => 'required|exists:cocurriculars,id',
                'period' => 'required|in:weekly,monthly'
            ]);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'period' => 'required|in:weekly,monthly'
            ]);
        }

        $teacher = Auth::user()->teacher;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $schoolClassId = $request->school_class_id;
        $studentId = $request->student_id;
        $periodType = $request->period;

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $cocurricularId = $request->cocurricular_id;
            $schedules = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $cocurricularId)
                ->where('school_class_id', $schoolClassId)
                ->get();

            if ($schedules->isEmpty()) {
                return response()->json(['error' => 'Jadwal kokurikuler tidak ditemukan.'], 404);
            }

            $scheduleIds = $schedules->pluck('id')->toArray();
            $scheduleDays = $schedules->pluck('day_of_week')->unique()->toArray();

            $query = SubjectAttendance::whereIn('schedule_id', $scheduleIds)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        } else {
            $subjectId = $request->subject_id;
            $assignment = TeachingAssignment::where('teacher_id', $teacher->id)
                ->where('school_class_id', $schoolClassId)
                ->where('subject_id', $subjectId)
                ->first();

            if (!$assignment) {
                return response()->json(['error' => 'Jadwal mengajar tidak ditemukan.'], 404);
            }

            $scheduleDays = Schedule::where('teaching_assignment_id', $assignment->id)
                ->pluck('day_of_week')
                ->unique()
                ->toArray();

            $query = SubjectAttendance::where('teacher_id', $teacher->id)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->whereHas('schedule.teachingAssignment', function ($q) use ($schoolClassId, $subjectId) {
                    $q->where('school_class_id', $schoolClassId)->where('subject_id', $subjectId);
                });
        }

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);

        $validDays = collect(CarbonPeriod::create($startDate, $endDate))->filter(function ($date) use ($scheduleDays, $holidays) {
            return in_array($date->dayOfWeekIso, $scheduleDays) && !\App\Models\Calendar::isDateInHolidays($date, $holidays) && $date->startOfDay() <= now()->startOfDay();
        });

        if ($studentId && $studentId !== 'all') {
            $query->where('student_id', $studentId);
            $studentsCount = 1;
        } else {
            $studentsCount = Student::where('school_class_id', $schoolClassId)->count();
        }

        $attendances = $query->get();

        $totalHadir = $attendances->where('status', 'hadir')->count();
        $totalSakit = $attendances->where('status', 'sakit')->count();
        $totalIzin = $attendances->where('status', 'izin')->count();
        $totalAlpaTercatat = $attendances->whereIn('status', ['alpa', 'bolos'])->count();

        $maxPossible = $validDays->count() * $studentsCount;
        
        $p_hadir = 0; $p_sakit = 0; $p_izin = 0; $p_alpa = 0;
        if ($maxPossible > 0) {
            $p_hadir = round(($totalHadir / $maxPossible) * 100, 1);
            $p_sakit = round(($totalSakit / $maxPossible) * 100, 1);
            $p_izin  = round(($totalIzin / $maxPossible) * 100, 1);
            
            $recorded = $totalHadir + $totalSakit + $totalIzin + $totalAlpaTercatat;
            $unrecorded = $maxPossible - $recorded;
            $totalAlpa = $totalAlpaTercatat + ($unrecorded > 0 ? $unrecorded : 0);
            
            $p_alpa  = round(($totalAlpa / $maxPossible) * 100, 1);
        }

        $summaryData = [
            'hadir' => $p_hadir,
            'sakit' => $p_sakit,
            'izin' => $p_izin,
            'alpa' => $p_alpa,
        ];

        $trendLabels = [];
        $trendData = [
            'hadir' => [],
            'sakit' => [],
            'izin' => [],
            'alpa' => []
        ];

        $groupedDays = [];
        foreach ($validDays as $day) {
            if ($periodType === 'weekly') {
                $label = 'Minggu ' . $day->weekOfMonth . ' ' . $day->translatedFormat('F');
            } else {
                $label = $day->translatedFormat('F Y');
            }
            if (!isset($groupedDays[$label])) {
                $groupedDays[$label] = [];
            }
            $groupedDays[$label][] = $day->format('Y-m-d');
        }

        foreach ($groupedDays as $label => $days) {
            $trendLabels[] = $label;
            
            $maxPossGroup = count($days) * $studentsCount;
            if ($maxPossGroup == 0) {
                $trendData['hadir'][] = 0;
                $trendData['sakit'][] = 0;
                $trendData['izin'][] = 0;
                $trendData['alpa'][] = 0;
                continue;
            }

            $groupHadir = 0; $groupSakit = 0; $groupIzin = 0; $groupAlpa = 0;
            foreach ($attendances as $att) {
                if (in_array(Carbon::parse($att->created_at)->format('Y-m-d'), $days)) {
                    if ($att->status == 'hadir') $groupHadir++;
                    elseif ($att->status == 'sakit') $groupSakit++;
                    elseif ($att->status == 'izin') $groupIzin++;
                    elseif (in_array($att->status, ['alpa', 'bolos'])) $groupAlpa++;
                }
            }

            $t_hadir = round(($groupHadir / $maxPossGroup) * 100, 1);
            $t_sakit = round(($groupSakit / $maxPossGroup) * 100, 1);
            $t_izin = round(($groupIzin / $maxPossGroup) * 100, 1);
            
            $g_recorded = $groupHadir + $groupSakit + $groupIzin + $groupAlpa;
            $g_unrecorded = $maxPossGroup - $g_recorded;
            $t_alpa_total = $groupAlpa + ($g_unrecorded > 0 ? $g_unrecorded : 0);
            
            $t_alpa = round(($t_alpa_total / $maxPossGroup) * 100, 1);

            $trendData['hadir'][] = $t_hadir;
            $trendData['sakit'][] = $t_sakit;
            $trendData['izin'][] = $t_izin;
            $trendData['alpa'][] = $t_alpa;
        }

        return response()->json([
            'summary' => $summaryData,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData
        ]);
    }
}
