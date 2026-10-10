<?php

namespace App\Services\Admin;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Calendar;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\SubjectAttendance;
use Carbon\CarbonPeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AdminLeaveRequestService
{
    /**
     * Get index dataset for leave requests management.
     */
    public function getIndexData(array $filters = []): array
    {
        // 1. Pending requests submitted by parents
        $pendingRequests = LeaveRequest::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('created_by')
                  ->where(function ($sq) {
                      $sq->whereNull('submission_source')
                        ->orWhere('submission_source', 'aplikasi_ortu');
                  });
            })
            ->with(['student.schoolClass', 'parent'])
            ->oldest()
            ->get();

        // 2. Manual interventions created by Admin/TU
        $manualInterventionsQuery = LeaveRequest::where(function ($q) {
                $q->whereNotNull('created_by')
                  ->orWhere(function ($sq) {
                      $sq->whereNotNull('submission_source')
                        ->where('submission_source', '!=', 'aplikasi_ortu');
                  });
            })
            ->with(['student.schoolClass', 'creator', 'approver'])
            ->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $manualInterventionsQuery->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['class_id'])) {
            $classId = $filters['class_id'];
            $manualInterventionsQuery->whereHas('student', function ($q) use ($classId) {
                $q->where('school_class_id', $classId);
            });
        }

        if (!empty($filters['type'])) {
            $manualInterventionsQuery->where('type', $filters['type']);
        }

        $manualInterventions = $manualInterventionsQuery->paginate(15, ['*'], 'manual_page')->withQueryString();

        // 3. Processed parent requests (approved or rejected)
        $processedRequests = LeaveRequest::whereIn('status', ['approved', 'rejected'])
            ->where(function ($q) {
                $q->whereNull('created_by')
                  ->where(function ($sq) {
                      $sq->whereNull('submission_source')
                        ->orWhere('submission_source', 'aplikasi_ortu');
                  });
            })
            ->with(['student.schoolClass', 'parent', 'approver'])
            ->latest('updated_at')
            ->paginate(10, ['*'], 'processed_page')->withQueryString();

        // 4. Classes for quick input
        $classes = SchoolClass::with(['students' => function ($q) {
            $q->orderBy('name', 'asc');
        }])->orderBy('name', 'asc')->get();

        return compact(
            'pendingRequests',
            'manualInterventions',
            'processedRequests',
            'classes'
        );
    }

    /**
     * Get students list by school class.
     */
    public function getStudentsByClass(?int $classId): Collection
    {
        if (!$classId) {
            return collect();
        }

        $schoolClass = SchoolClass::find($classId);
        $students = collect();
        if ($schoolClass) {
            $students = $schoolClass->students()->orderBy('name', 'asc')->get();
        }

        if ($students->isEmpty()) {
            $q = Student::where('school_class_id', $classId);
            $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool) Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            $students = $q->orderBy('name', 'asc')->get();
        }

        return $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'photo_url' => $student->photo_url,
            ];
        });
    }

    /**
     * Store manual intervention leave requests for one or multiple students.
     */
    public function storeManualIntervention(array $validated, ?UploadedFile $attachment, int $creatorId): int
    {
        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('leave_attachments', 'public');
        }

        $createdCount = 0;
        foreach ($validated['student_ids'] as $studentId) {
            $student = Student::with('parents')->find($studentId);
            if (!$student) {
                continue;
            }

            $parentId = $student->parents->first()?->id ?? null;

            $leaveRequest = LeaveRequest::create([
                'student_id' => $student->id,
                'parent_id' => $parentId,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'type' => $validated['type'],
                'submission_source' => $validated['submission_source'],
                'reason' => $validated['reason'],
                'attachment' => $attachmentPath,
                'status' => 'approved',
                'approved_by' => $creatorId,
                'created_by' => $creatorId,
            ]);

            $this->syncAttendancesForLeave($leaveRequest);
            $createdCount++;
        }

        return $createdCount;
    }

    /**
     * Format a leave request for edit modal response.
     */
    public function formatForEdit(LeaveRequest $leaveRequest): array
    {
        $leaveRequest->load(['student.schoolClass']);

        return [
            'id' => $leaveRequest->id,
            'student_id' => $leaveRequest->student_id,
            'student_name' => $leaveRequest->student?->name ?? 'Siswa',
            'class_name' => $leaveRequest->student?->schoolClass?->name ?? '-',
            'start_date' => $leaveRequest->start_date ? $leaveRequest->start_date->format('Y-m-d') : '',
            'end_date' => $leaveRequest->end_date ? $leaveRequest->end_date->format('Y-m-d') : '',
            'type' => $leaveRequest->type,
            'submission_source' => $leaveRequest->submission_source ?? 'whatsapp',
            'reason' => $leaveRequest->reason,
            'attachment_url' => $leaveRequest->attachment ? asset('storage/' . $leaveRequest->attachment) : null,
        ];
    }

    /**
     * Update a manual intervention leave request and resynchronize attendance.
     */
    public function updateManualIntervention(LeaveRequest $leaveRequest, array $validated, ?UploadedFile $attachment, int $updaterId): LeaveRequest
    {
        // Revert previous attendance records
        $this->revertAttendancesForLeave($leaveRequest);

        if ($attachment) {
            if ($leaveRequest->attachment && Storage::disk('public')->exists($leaveRequest->attachment)) {
                Storage::disk('public')->delete($leaveRequest->attachment);
            }
            $leaveRequest->attachment = $attachment->store('leave_attachments', 'public');
        }

        $leaveRequest->start_date = $validated['start_date'];
        $leaveRequest->end_date = $validated['end_date'];
        $leaveRequest->type = $validated['type'];
        $leaveRequest->submission_source = $validated['submission_source'];
        $leaveRequest->reason = $validated['reason'];
        $leaveRequest->status = 'approved';
        $leaveRequest->approved_by = $updaterId;
        $leaveRequest->save();

        // Resync new attendance records
        $this->syncAttendancesForLeave($leaveRequest);

        return $leaveRequest;
    }

    /**
     * Delete an intervention and revert attendance records.
     */
    public function deleteIntervention(LeaveRequest $leaveRequest): string
    {
        $this->revertAttendancesForLeave($leaveRequest);

        if ($leaveRequest->attachment && Storage::disk('public')->exists($leaveRequest->attachment)) {
            Storage::disk('public')->delete($leaveRequest->attachment);
        }

        $studentName = $leaveRequest->student?->name ?? 'Siswa';
        $leaveRequest->delete();

        return $studentName;
    }

    /**
     * Approve parent leave request and sync attendance.
     */
    public function approveParentRequest(LeaveRequest $leaveRequest, int $approverId): bool
    {
        if ($leaveRequest->status !== 'pending') {
            return false;
        }

        $leaveRequest->status = 'approved';
        $leaveRequest->approved_by = $approverId;
        $leaveRequest->save();

        $this->syncAttendancesForLeave($leaveRequest);

        return true;
    }

    /**
     * Reject parent leave request.
     */
    public function rejectParentRequest(LeaveRequest $leaveRequest, string $rejectionReason, int $approverId): bool
    {
        if ($leaveRequest->status !== 'pending') {
            return false;
        }

        $leaveRequest->status = 'rejected';
        $leaveRequest->approved_by = $approverId;
        $leaveRequest->rejection_reason = $rejectionReason;
        $leaveRequest->save();

        return true;
    }

    /**
     * Synchronize daily attendance and subject attendance for approved leave request.
     */
    public function syncAttendancesForLeave(LeaveRequest $leaveRequest): void
    {
        $student = $leaveRequest->student;
        if (!$student) {
            return;
        }

        $classId = $student->school_class_id;

        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeSemester = Semester::where('is_active', true)->first();
        $yearId = $activeYear?->id;
        $semesterId = $activeSemester?->id;

        $holidays = Calendar::getHolidaysInRange($leaveRequest->start_date, $leaveRequest->end_date);
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

        $sourceText = match ($leaveRequest->submission_source) {
            'whatsapp' => 'WA Ortu',
            'telepon' => 'Telepon Ortu',
            'surat' => 'Surat',
            'lisan' => 'Lisan/Langsung',
            default => 'Izin Resmi'
        };

        foreach ($period as $date) {
            if ($date->isWeekend() || Calendar::isDateInHolidays($date, $holidays)) {
                continue;
            }

            $currentDate = $date->copy();

            // 1. Daily attendance (Homeroom & General School Attendance)
            $existingDaily = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $currentDate)
                ->first();
            $oldDailyStatus = $existingDaily ? $existingDaily->status : 'belum_absen';

            $dailyAttendance = Attendance::updateOrCreate(
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

            // Audit log tracking for BOS/Inspektorat compliance
            $approver = $leaveRequest->approver ?? auth()->user();
            app(\App\Services\Audit\AttendanceAuditService::class)->recordLog(
                student: $student,
                attendance: $dailyAttendance,
                oldStatus: $oldDailyStatus,
                newStatus: $leaveRequest->type,
                reason: "[Dispensasi {$sourceText}] " . $leaveRequest->reason,
                actor: $approver,
                actionType: 'leave_sync',
                attendanceDate: $currentDate
            );

            // 2. Subject attendance (Subject Teachers)
            if ($classId) {
                $dayOfWeek = $currentDate->isoWeekday();

                $schedules = Schedule::where('school_class_id', $classId)
                    ->where('day_of_week', $dayOfWeek)
                    ->get();

                $assignmentSchedules = Schedule::whereHas('teachingAssignment', function ($q) use ($classId) {
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
     * Revert/neutralize attendance records created for a leave request.
     */
    public function revertAttendancesForLeave(LeaveRequest $leaveRequest): void
    {
        $studentId = $leaveRequest->student_id;
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

        foreach ($period as $date) {
            Attendance::where('student_id', $studentId)
                ->whereDate('attendance_time', $date)
                ->where('status', $leaveRequest->type)
                ->delete();

            SubjectAttendance::where('student_id', $studentId)
                ->whereDate('created_at', $date)
                ->where('status', $leaveRequest->type)
                ->delete();
        }
    }
}
