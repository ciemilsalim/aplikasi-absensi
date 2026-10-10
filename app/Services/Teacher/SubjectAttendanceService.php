<?php

namespace App\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Calendar;
use App\Models\Cocurricular;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentAnecdote;
use App\Models\Subject;
use App\Models\SubjectAttendance;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubjectAttendanceService
{
    /**
     * Memeriksa otorisasi guru terhadap jadwal (baik reguler maupun kokurikuler).
     */
    public function isTeacherAuthorized(Schedule $schedule, Teacher $teacher): bool
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
    public function getScheduleClassId(Schedule $schedule): ?int
    {
        if ($schedule->isCocurricular()) {
            return $schedule->school_class_id;
        }
        return $schedule->teachingAssignment?->school_class_id;
    }

    /**
     * Mengambil seluruh data yang dibutuhkan untuk halaman pemindai QR / Wajah.
     */
    public function getScannerData(Schedule $schedule, ?string $dateStr = null): array
    {
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();

        $subjectAttendancesToday = SubjectAttendance::where('schedule_id', $schedule->id)
            ->whereDate('created_at', $selectedDate)
            ->with('student')
            ->get();

        $attendedStudents = $subjectAttendancesToday->where('status', 'hadir');
        $studentsOnLeave = $subjectAttendancesToday->whereIn('status', ['sakit', 'izin']);
        $studentsSakit = $subjectAttendancesToday->where('status', 'sakit');
        $studentsIzin = $subjectAttendancesToday->where('status', 'izin');
        $studentsAlpa = $subjectAttendancesToday->where('status', 'alpa');
        $studentsBolos = $subjectAttendancesToday->where('status', 'bolos');
        $studentIdsWithRecord = $subjectAttendancesToday->pluck('student_id')->all();

        $allClassStudents = $schedule->getEnrolledStudents();
        $totalClassStudents = $allClassStudents->count();
        $attendancePercentage = $totalClassStudents > 0 ? round(($attendedStudents->count() / $totalClassStudents) * 100) : 0;

        $studentsWithoutNotice = $allClassStudents->whereNotIn('id', $studentIdsWithRecord)->values();

        $studentsForFaceRecognition = $allClassStudents
            ->whereNotNull('photo')
            ->map(function ($student) {
                return [
                    'unique_id' => $student->unique_id,
                    'name' => $student->name,
                    'photo_url' => $student->photo_url,
                    'face_descriptor' => $student->face_descriptor ?? null,
                ];
            })
            ->values();

        // Catatan Anekdot siswa pada jadwal dan tanggal ini
        $anecdotesToday = collect();
        try {
            if (Schema::hasTable('student_anecdotes')) {
                $anecdotesToday = StudentAnecdote::where('schedule_id', $schedule->id)
                    ->whereDate('date', $selectedDate)
                    ->get()
                    ->keyBy('student_id');
            }
        } catch (\Throwable $e) {
            $anecdotesToday = collect();
        }

        return [
            'schedule' => $schedule,
            'attendedStudents' => $attendedStudents,
            'studentsOnLeave' => $studentsOnLeave,
            'studentsSakit' => $studentsSakit,
            'studentsIzin' => $studentsIzin,
            'studentsAlpa' => $studentsAlpa,
            'studentsBolos' => $studentsBolos,
            'studentsWithoutNotice' => $studentsWithoutNotice,
            'totalClassStudents' => $totalClassStudents,
            'attendancePercentage' => $attendancePercentage,
            'studentsForFaceRecognition' => $studentsForFaceRecognition,
            'selectedDate' => $selectedDate,
            'anecdotesToday' => $anecdotesToday,
        ];
    }

    /**
     * Memvalidasi jam kerja dan hari libur untuk absensi real-time.
     */
    public function validateAttendanceTimeWindow(Carbon $selectedDate): ?string
    {
        if ($selectedDate->isWeekend()) {
            return 'Absensi tidak dapat dilakukan pada akhir pekan.';
        }

        $holiday = Calendar::where('is_holiday', true)
            ->whereDate('start_date', '<=', $selectedDate)
            ->where(function ($query) use ($selectedDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $selectedDate);
            })->first();

        if ($holiday) {
            return 'Hari ini libur: ' . $holiday->title;
        }

        $settings = Setting::pluck('value', 'key');
        $jamMasuk = $settings->get('jam_masuk', '07:30');
        $jamPulang = $settings->get('jam_pulang_guru') ?: $settings->get('jam_pulang', '16:00');

        $now = now();
        $startTime = Carbon::createFromTimeString($jamMasuk)->subMinutes(30);
        $endTime = Carbon::createFromTimeString($jamPulang)->addHours(1);

        if (!$now->between($startTime, $endTime)) {
            return 'Absensi hanya dapat dilakukan pada jam sekolah/kerja (' . $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ').';
        }

        return null;
    }

    /**
     * Menyimpan data presensi melalui scan QR / Wajah.
     */
    public function recordScanAttendance(Schedule $schedule, Teacher $teacher, string $qrData, ?string $dateStr = null): array
    {
        $classId = $this->getScheduleClassId($schedule);

        $parts = explode('-', $qrData, 2);
        if (count($parts) === 2) {
            $student = Student::where('nis', $parts[0])->where('unique_id', $parts[1])->first();
        } else {
            $student = Student::where('unique_id', $qrData)->orWhere('nis', $qrData)->first();
        }

        if (!$student) {
            return [
                'status' => 404,
                'payload' => ['success' => false, 'message' => 'Siswa tidak ditemukan atau QR Code tidak valid.']
            ];
        }

        if (in_array(strtolower(trim((string)($student->status ?? ''))), Student::$inactiveStatuses)) {
            return [
                'status' => 422,
                'payload' => [
                    'success' => false,
                    'message' => 'Presensi ditolak. Siswa ' . $student->name . ' berstatus tidak aktif (' . ($student->status ?? 'nonaktif') . ').'
                ]
            ];
        }

        if ($student->school_class_id !== $classId) {
            return [
                'status' => 422,
                'payload' => ['success' => false, 'message' => 'Siswa tidak terdaftar di kelas ini.']
            ];
        }

        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $timeWindowError = $this->validateAttendanceTimeWindow($selectedDate);
        if ($timeWindowError) {
            return [
                'status' => 422,
                'payload' => ['success' => false, 'message' => $timeWindowError]
            ];
        }

        $existingAttendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $selectedDate)
            ->first();

        if ($existingAttendance) {
            return [
                'status' => 409,
                'payload' => ['success' => false, 'message' => 'Siswa sudah diabsen sebelumnya.']
            ];
        }

        $attendanceDateTime = $selectedDate->copy()->setTimeFrom(now());

        $attendance = new SubjectAttendance([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'hadir',
        ]);
        $attendance->created_at = $attendanceDateTime;
        $attendance->updated_at = $attendanceDateTime;
        $attendance->save();

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'message' => 'Kehadiran ' . $student->name . ' berhasil dicatat.',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'status' => 'hadir',
                    'time' => $attendance->created_at->format('H:i'),
                    'photo_url' => $student->photo_url,
                ]
            ]
        ];
    }

    /**
     * Mengambil riwayat absensi mata pelajaran guru untuk tanggal tertentu.
     */
    public function getHistoryData(Teacher $teacher, ?string $dateStr = null): array
    {
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
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

        return [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
        ];
    }

    /**
     * Menandai status kehadiran siswa secara manual oleh guru.
     */
    public function recordManualAttendance(Schedule $schedule, Teacher $teacher, int $studentId, string $status, ?string $dateStr = null): array
    {
        $student = Student::find($studentId);
        if (!$student) {
            return [
                'status' => 404,
                'payload' => ['success' => false, 'message' => 'Siswa tidak ditemukan.']
            ];
        }

        if (in_array(strtolower(trim((string)($student->status ?? ''))), Student::$inactiveStatuses)) {
            return [
                'status' => 422,
                'payload' => [
                    'success' => false,
                    'message' => 'Presensi ditolak. Siswa ' . $student->name . ' berstatus tidak aktif (' . ($student->status ?? 'nonaktif') . ').'
                ]
            ];
        }

        $classId = $this->getScheduleClassId($schedule);
        if ($student->school_class_id !== $classId) {
            return [
                'status' => 422,
                'payload' => ['success' => false, 'message' => 'Siswa tidak terdaftar di kelas ini.']
            ];
        }

        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $timeWindowError = $this->validateAttendanceTimeWindow($selectedDate);
        if ($timeWindowError) {
            return [
                'status' => 422,
                'payload' => ['success' => false, 'message' => $timeWindowError]
            ];
        }

        $attendanceDateTime = $selectedDate->copy()->setTimeFrom(now());

        $attendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $selectedDate)
            ->first();

        if ($attendance) {
            $attendance->update([
                'status' => $status,
                'teacher_id' => $teacher->id,
            ]);
        } else {
            $attendance = new SubjectAttendance([
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => $status,
            ]);
            $attendance->created_at = $attendanceDateTime;
            $attendance->updated_at = $attendanceDateTime;
            $attendance->save();
        }

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'message' => 'Status ' . $student->name . ' berhasil diubah menjadi ' . ucfirst($status),
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'status' => $status,
                    'time' => $attendanceDateTime->format('H:i'),
                    'photo_url' => $student->photo_url,
                ]
            ]
        ];
    }

    /**
     * Mengambil opsi data filter untuk form rekap absensi.
     */
    public function getReportFormData(User $user, ?Teacher $teacher): array
    {
        $isExecutive = $user->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster']);

        if ($isExecutive) {
            $classes = SchoolClass::orderBy('name', 'asc')->pluck('name', 'id');
            $subjects = Subject::orderBy('name', 'asc')->pluck('name', 'id');
            $cocurricularProjects = DB::table('cocurriculars')->pluck('title', 'id');
            $cocurricularClasses = SchoolClass::orderBy('name', 'asc')->pluck('name', 'id');
        } else {
            $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->get();

            $classes = $assignments->pluck('schoolClass.name', 'schoolClass.id')->filter()->unique();
            $subjects = $assignments->pluck('subject.name', 'subject.id')->filter()->unique();

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

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'cocurricularProjects' => $cocurricularProjects,
            'cocurricularClasses' => $cocurricularClasses,
        ];
    }

    /**
     * Mengambil dan mengagregasi data rekap absensi (digunakan bersama untuk Preview & Print).
     */
    public function getAggregatedReportData(array $filters, User $user, ?Teacher $teacher, bool $isForPrint = false): array
    {
        $activityType = $filters['activity_type'] ?? 'regular';
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $schoolClassId = $filters['school_class_id'];
        $isExecutive = $user->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster']);

        $classInfo = SchoolClass::withoutGlobalScopes()->find($schoolClassId);
        $students = $classInfo ? $classInfo->students()->orderBy('name')->get() : collect();
        if ($students->isEmpty()) {
            $q = Student::where('school_class_id', $schoolClassId);
            $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool)Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            $students = $q->orderBy('name')->get();
        }

        if ($activityType === 'cocurricular' || !empty($filters['cocurricular_id'])) {
            $cocurricularId = $filters['cocurricular_id'];
            $cocurricularInfo = Cocurricular::find($cocurricularId);
            $subjectInfo = null;

            $schedules = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $cocurricularId)
                ->where('school_class_id', $schoolClassId)
                ->get();

            if ($schedules->isEmpty()) {
                return ['error' => 'Jadwal kokurikuler tidak ditemukan untuk kombinasi kelas dan proyek ini.'];
            }

            $scheduleIds = $schedules->pluck('id')->toArray();
            $scheduleDays = $schedules->pluck('day_of_week')->unique()->toArray();

            $attendances = SubjectAttendance::with('student')
                ->whereIn('schedule_id', $scheduleIds)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->get();
        } else {
            $subjectId = $filters['subject_id'];
            $subjectInfo = Subject::find($subjectId);
            $cocurricularInfo = null;

            $assignmentQuery = TeachingAssignment::where('school_class_id', $schoolClassId)
                ->where('subject_id', $subjectId);

            if (!$isExecutive && $teacher) {
                $assignmentQuery->where('teacher_id', $teacher->id);
            }

            $assignment = $assignmentQuery->first();

            if (!$assignment) {
                return ['error' => 'Jadwal mengajar tidak ditemukan untuk kombinasi ini.'];
            }

            $students = $assignment->getEnrolledStudents();

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

        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);

        $period = collect(CarbonPeriod::create($startDate, $endDate)->filter(function ($date) use ($scheduleDays, $holidays) {
            return in_array($date->dayOfWeekIso, $scheduleDays) && !Calendar::isDateInHolidays($date, $holidays);
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

        $result = [
            'students' => $students,
            'period' => $period,
            'attendanceData' => $attendanceData,
            'attendanceSummary' => $attendanceSummary,
            'totalEffDays' => $totalEffDays,
            'totalHadir' => $totalHadir,
            'totalSakit' => $totalSakit,
            'totalIzin' => $totalIzin,
            'totalAlpa' => $totalAlpa,
            'totalBolos' => $totalBolos,
            'classAvgPercent' => $classAvgPercent,
            'classInfo' => $classInfo,
            'subjectInfo' => $subjectInfo,
            'cocurricularInfo' => $cocurricularInfo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'activityType' => $activityType,
        ];

        if ($isForPrint) {
            $settings = Setting::whereIn('key', [
                'app_logo', 'school_name', 'school_address', 'school_phone',
                'school_email', 'school_headmaster_name', 'school_headmaster_nip'
            ])->get();

            $result['schoolIdentity'] = [
                'logo' => $settings->firstWhere('key', 'app_logo')->value ?? null,
                'name' => $settings->firstWhere('key', 'school_name')->value ?? null,
                'address' => $settings->firstWhere('key', 'school_address')->value ?? null,
                'phone' => $settings->firstWhere('key', 'school_phone')->value ?? null,
                'email' => $settings->firstWhere('key', 'school_email')->value ?? null,
                'headmaster_name' => $settings->firstWhere('key', 'school_headmaster_name')->value ?? null,
                'headmaster_nip' => $settings->firstWhere('key', 'school_headmaster_nip')->value ?? null,
            ];
        }

        return $result;
    }

    /**
     * Memperbarui atau menghapus status presensi dari tabel preview rekap.
     */
    public function updateReportAttendance(array $data, User $user, ?Teacher $teacher): array
    {
        $isExecutive = $user->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster']);
        $date = Carbon::parse($data['date']);
        $dayOfWeek = $date->dayOfWeekIso;

        $assignment = null;
        if (!empty($data['cocurricular_id'])) {
            $schedule = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $data['cocurricular_id'])
                ->where('school_class_id', $data['school_class_id'])
                ->where('day_of_week', $dayOfWeek)
                ->first();
        } else {
            $assignmentQuery = TeachingAssignment::where('school_class_id', $data['school_class_id'])
                ->where('subject_id', $data['subject_id']);

            if (!$isExecutive && $teacher) {
                $assignmentQuery->where('teacher_id', $teacher->id);
            }

            $assignment = $assignmentQuery->first();

            if (!$assignment) {
                return ['type' => 'error', 'message' => 'Jadwal mengajar tidak ditemukan.'];
            }

            $schedule = Schedule::where('teaching_assignment_id', $assignment->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();
        }

        if (!$schedule) {
            return ['type' => 'error', 'message' => 'Tidak ada jadwal untuk hari yang dipilih. Data tidak dapat dibuat.'];
        }

        $recordedByTeacherId = $teacher?->id ?: ($schedule->teacher_id ?? $assignment?->teacher_id);

        $startTimeStr = $schedule->start_time ?: '07:30:00';
        try {
            $attendanceTime = Carbon::parse($startTimeStr);
            $attendanceDateTime = $date->copy()->setTime($attendanceTime->hour, $attendanceTime->minute, $attendanceTime->second);
        } catch (\Throwable $e) {
            $attendanceDateTime = $date->copy()->setTime(7, 30, 0);
        }

        $attendance = SubjectAttendance::where('student_id', $data['student_id'])
            ->where('schedule_id', $schedule->id)
            ->whereDate('created_at', $date)
            ->first();

        if ($data['status'] === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return ['type' => 'success', 'message' => 'Data kehadiran berhasil dihapus.'];
            }
            return ['type' => 'info', 'message' => 'Data kehadiran tidak ditemukan untuk dihapus.'];
        }

        if ($attendance) {
            $attendance->status = $data['status'];
            if ($recordedByTeacherId) {
                $attendance->teacher_id = $recordedByTeacherId;
            }
            $attendance->save();
        } else {
            $activeYear = AcademicYear::getActive();
            $activeSemester = Semester::getActive();

            $newAttendance = new SubjectAttendance();
            $newAttendance->student_id = $data['student_id'];
            $newAttendance->schedule_id = $schedule->id;
            $newAttendance->status = $data['status'];
            $newAttendance->teacher_id = $recordedByTeacherId;
            $newAttendance->academic_year_id = session('active_academic_year_id') ?: $activeYear?->id;
            $newAttendance->semester_id = session('active_semester_id') ?: $activeSemester?->id;
            $newAttendance->created_at = $attendanceDateTime;
            $newAttendance->updated_at = $attendanceDateTime;
            $newAttendance->save();
        }

        return ['type' => 'success', 'message' => 'Data kehadiran berhasil diperbarui.'];
    }

    /**
     * Mengambil opsi data filter untuk halaman chart analitik.
     */
    public function getChartFormData(Teacher $teacher): array
    {
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

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'cocurricularProjects' => $cocurricularProjects,
            'cocurricularClasses' => $cocurricularClasses,
            'studentsMap' => $studentsMap,
        ];
    }

    /**
     * Mengolah data analitik grafik untuk dikembalikan dalam format Chart.js JSON.
     */
    public function getChartAnalyticsData(array $filters, Teacher $teacher): array
    {
        $activityType = $filters['activity_type'] ?? 'regular';
        $targetPercentage = (float)($filters['target_percentage'] ?? 80);
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $schoolClassId = $filters['school_class_id'];
        $studentId = $filters['student_id'] ?? null;
        $periodType = $filters['period'] ?? 'weekly';

        if ($activityType === 'cocurricular' || !empty($filters['cocurricular_id'])) {
            $cocurricularId = $filters['cocurricular_id'];
            $schedules = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $cocurricularId)
                ->where('school_class_id', $schoolClassId)
                ->get();

            if ($schedules->isEmpty()) {
                return [
                    'success' => false,
                    'emptyState' => true,
                    'message' => 'Belum ada jadwal kokurikuler untuk kombinasi kelas dan proyek ini.'
                ];
            }

            $scheduleIds = $schedules->pluck('id')->toArray();
            $scheduleDays = $schedules->pluck('day_of_week')->unique()->toArray();

            $query = SubjectAttendance::whereIn('schedule_id', $scheduleIds)
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        } else {
            $subjectId = $filters['subject_id'];
            $assignment = TeachingAssignment::where('teacher_id', $teacher->id)
                ->where('school_class_id', $schoolClassId)
                ->where('subject_id', $subjectId)
                ->first();

            if (!$assignment) {
                return [
                    'success' => false,
                    'emptyState' => true,
                    'message' => 'Jadwal mengajar mata pelajaran tidak ditemukan untuk kombinasi ini.'
                ];
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

        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);

        $validDays = collect(CarbonPeriod::create($startDate, $endDate))->filter(function ($date) use ($scheduleDays, $holidays) {
            return in_array($date->dayOfWeekIso, $scheduleDays) && !Calendar::isDateInHolidays($date, $holidays) && $date->startOfDay() <= now()->startOfDay();
        });

        $classInfo = SchoolClass::withoutGlobalScopes()->find($schoolClassId);
        $allStudents = $classInfo ? $classInfo->students()->orderBy('name')->get() : collect();
        if ($allStudents->isEmpty()) {
            $q = Student::where('school_class_id', $schoolClassId);
            $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool)Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            $allStudents = $q->orderBy('name')->get();
        }

        if ($studentId && $studentId !== 'all') {
            $query->where('student_id', $studentId);
            $studentsCount = 1;
        } else {
            $studentsCount = $allStudents->count();
        }

        if ($validDays->isEmpty() || $studentsCount === 0) {
            return [
                'success' => true,
                'hasData' => false,
                'message' => 'Belum ada sesi pertemuan efektif pada rentang tanggal ini.'
            ];
        }

        $attendances = $query->get();

        $totalHadir = $attendances->where('status', 'hadir')->count();
        $totalSakit = $attendances->where('status', 'sakit')->count();
        $totalIzin = $attendances->where('status', 'izin')->count();
        $totalAlpaTercatat = $attendances->whereIn('status', ['alpa', 'bolos'])->count();

        $maxPossible = $validDays->count() * $studentsCount;

        $pHadir = 0; $pSakit = 0; $pIzin = 0; $pAlpa = 0;
        if ($maxPossible > 0) {
            $pHadir = round(($totalHadir / $maxPossible) * 100, 1);
            $pSakit = round(($totalSakit / $maxPossible) * 100, 1);
            $pIzin  = round(($totalIzin / $maxPossible) * 100, 1);

            $recorded = $totalHadir + $totalSakit + $totalIzin + $totalAlpaTercatat;
            $unrecorded = $maxPossible - $recorded;
            $totalAlpa = $totalAlpaTercatat + ($unrecorded > 0 ? $unrecorded : 0);

            $pAlpa  = round(($totalAlpa / $maxPossible) * 100, 1);
        } else {
            $totalAlpa = 0;
        }

        $summaryData = [
            'hadir' => $pHadir,
            'sakit' => $pSakit,
            'izin' => $pIzin,
            'alpa' => $pAlpa,
        ];

        // Hitung siswa di bawah target (Need Attention)
        $studentsBelowTarget = [];
        if (!$studentId || $studentId === 'all') {
            $totalSessions = $validDays->count();
            if ($totalSessions > 0) {
                foreach ($allStudents as $st) {
                    $stHadir = $attendances->where('student_id', $st->id)->where('status', 'hadir')->count();
                    $stPercent = round(($stHadir / $totalSessions) * 100, 1);
                    if ($stPercent < $targetPercentage) {
                        $stMissed = $totalSessions - $stHadir;
                        $studentsBelowTarget[] = [
                            'id' => $st->id,
                            'name' => $st->name,
                            'nis' => $st->nis ?? '-',
                            'percent' => $stPercent,
                            'hadir' => $stHadir,
                            'missed' => $stMissed
                        ];
                    }
                }
            }
        }

        usort($studentsBelowTarget, fn($a, $b) => $a['percent'] <=> $b['percent']);

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

            $tHadir = round(($groupHadir / $maxPossGroup) * 100, 1);
            $tSakit = round(($groupSakit / $maxPossGroup) * 100, 1);
            $tIzin = round(($groupIzin / $maxPossGroup) * 100, 1);

            $gRecorded = $groupHadir + $groupSakit + $groupIzin + $groupAlpa;
            $gUnrecorded = $maxPossGroup - $gRecorded;
            $tAlpaTotal = $groupAlpa + ($gUnrecorded > 0 ? $gUnrecorded : 0);

            $tAlpa = round(($tAlpaTotal / $maxPossGroup) * 100, 1);

            $trendData['hadir'][] = $tHadir;
            $trendData['sakit'][] = $tSakit;
            $trendData['izin'][] = $tIzin;
            $trendData['alpa'][] = $tAlpa;
        }

        return [
            'success' => true,
            'hasData' => true,
            'summary' => $summaryData,
            'metrics' => [
                'total_hadir' => $totalHadir,
                'total_sakit' => $totalSakit,
                'total_izin' => $totalIzin,
                'total_alpa' => $totalAlpa,
                'total_students' => $studentsCount,
                'total_sessions' => $validDays->count(),
                'max_possible' => $maxPossible,
                'class_avg_percent' => $pHadir,
                'target_percent' => $targetPercentage,
            ],
            'studentsBelowTarget' => $studentsBelowTarget,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData
        ];
    }
}
