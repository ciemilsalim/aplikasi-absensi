<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EarlyWarningService
{
    /**
     * Get students requiring attention with risk classification, counts, and action metadata.
     */
    public function getAttentionStudents(array $options = []): Collection
    {
        $days = (int) ($options['days'] ?? 30);
        $sinceDate = Carbon::today()->subDays($days)->startOfDay();
        $classId = $options['class_id'] ?? null;
        $limit = isset($options['limit']) ? (int) $options['limit'] : null;

        $studentsQuery = Student::with(['schoolClass', 'parents'])
            ->where(function ($q) {
                $q->where('status', 'aktif')
                  ->orWhereNull('status')
                  ->orWhere('status', '');
            })
            ->when($classId, fn($q) => $q->where('school_class_id', $classId));

        // Eager count attendances within the window
        $studentsQuery->withCount([
            'attendances as alpha_count' => fn($q) => $q->where('status', 'alpa')->where('attendance_time', '>=', $sinceDate),
            'attendances as late_count' => fn($q) => $q->where('status', 'terlambat')->where('attendance_time', '>=', $sinceDate),
            'attendances as sick_count' => fn($q) => $q->where('status', 'sakit')->where('attendance_time', '>=', $sinceDate),
            'attendances as permit_count' => fn($q) => $q->whereIn('status', ['izin', 'izin_keluar'])->where('attendance_time', '>=', $sinceDate),
        ]);

        $students = $studentsQuery->get();

        $attentionList = $students->filter(function ($student) {
            return $student->alpha_count > 0 || $student->late_count > 0;
        })->map(function ($student) {
            $alpha = (int) $student->alpha_count;
            $late = (int) $student->late_count;

            // Risk Scoring & Classification
            // Alpha >= 3 or Late >= 5 is High Risk
            if ($alpha >= 3 || $late >= 5) {
                $riskLevel = 'high';
                $riskLabel = 'Perhatian Khusus (Kritis)';
                $badgeColor = 'rose';
                $recommendation = 'Panggilan Orang Tua & Koordinasi Guru BK';
            } elseif ($alpha >= 1 || $late >= 3) {
                $riskLevel = 'medium';
                $riskLabel = 'Peringatan Kehadiran (Sedang)';
                $badgeColor = 'amber';
                $recommendation = 'Peringatan Wali Kelas & Pemantauan';
            } else {
                $riskLevel = 'low';
                $riskLabel = 'Pantauan Ringan';
                $badgeColor = 'slate';
                $recommendation = 'Pembinaan Rutin Wali Kelas';
            }

            // Severity Score for intelligent sorting
            $severityScore = ($alpha * 3) + ($late * 1.2);

            // WhatsApp link preparation
            $parent = $student->parents->first();
            $rawPhone = $parent?->phone_number;
            $waLink = null;
            $formattedPhone = null;

            if ($rawPhone) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '62' . substr($cleanPhone, 1);
                } elseif (!str_starts_with($cleanPhone, '62')) {
                    $cleanPhone = '62' . $cleanPhone;
                }

                $formattedPhone = $rawPhone;
                $message = "Yth. Orang Tua / Wali dari {$student->name},\n\nKami dari pihak sekolah ingin mengonfirmasi dan berkoordinasi terkait kehadiran ananda pada 30 hari terakhir tercatat: {$alpha}x Alpa dan {$late}x Terlambat.\n\nMohon bantuannya untuk pembinaan bersama. Terima kasih.";
                $waLink = 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($message);
            }

            $student->risk_level = $riskLevel;
            $student->risk_label = $riskLabel;
            $student->badge_color = $badgeColor;
            $student->recommendation = $recommendation;
            $student->severity_score = $severityScore;
            $student->parent_name = $parent?->name ?? 'Belum Terhubung';
            $student->parent_phone = $formattedPhone;
            $student->wa_link = $waLink;

            return $student;
        })
        ->sortByDesc('severity_score')
        ->values();

        if ($limit && $limit > 0) {
            return $attentionList->take($limit);
        }

        return $attentionList;
    }

    /**
     * Get aggregate early warning statistics for executive dashboard overview.
     */
    public function getEarlyWarningSummary(Collection $attentionStudents): array
    {
        $highRiskCount = $attentionStudents->where('risk_level', 'high')->count();
        $mediumRiskCount = $attentionStudents->where('risk_level', 'medium')->count();
        $lowRiskCount = $attentionStudents->where('risk_level', 'low')->count();
        $totalAlpaAccumulated = $attentionStudents->sum('alpha_count');
        $totalLateAccumulated = $attentionStudents->sum('late_count');

        // Classes breakdown
        $classBreakdown = $attentionStudents->groupBy('schoolClass.name')
            ->map(function ($items, $className) {
                return [
                    'class_name' => $className ?: 'Tanpa Kelas',
                    'count' => $items->count(),
                    'high_count' => $items->where('risk_level', 'high')->count(),
                ];
            })
            ->sortByDesc('high_count')
            ->take(5)
            ->values();

        return compact(
            'highRiskCount',
            'mediumRiskCount',
            'lowRiskCount',
            'totalAlpaAccumulated',
            'totalLateAccumulated',
            'classBreakdown'
        );
    }
}
