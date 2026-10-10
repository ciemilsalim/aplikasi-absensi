<?php

namespace App\Services\Admin;

use App\Models\ScanLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminScanLogService
{
    /**
     * Get paginated logs and dashboard monitoring metrics.
     */
    public function getScanLogsData(array $filters = []): array
    {
        $perPage = isset($filters['per_page']) && in_array((int) $filters['per_page'], [15, 25, 50, 100])
            ? (int) $filters['per_page']
            : 25;

        $today = Carbon::today();

        $dateFrom = !empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : $today->copy()->startOfDay();

        $dateTo = !empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : $today->copy()->endOfDay();

        $logsQuery = ScanLog::with(['user', 'student.schoolClass'])
            ->whereBetween('scanned_at', [$dateFrom, $dateTo])
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['scan_type']), fn($q) => $q->where('scan_type', $filters['scan_type']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('user_name', 'like', $search)
                        ->orWhere('student_name', 'like', $search)
                        ->orWhere('student_nis', 'like', $search);
                });
            })
            ->latest('scanned_at');

        $logs = $logsQuery->paginate($perPage)->withQueryString();

        $todayStats = $this->getTodayStats($today);
        $scannerUsers = $this->getScannerUsers();
        $topScanners = $this->getTopScanners($today, 5);

        return compact(
            'logs',
            'dateFrom',
            'dateTo',
            'todayStats',
            'scannerUsers',
            'topScanners',
            'perPage'
        );
    }

    /**
     * Get aggregate scan statistics for a target date (defaults to today).
     */
    public function getTodayStats(?Carbon $targetDate = null): object
    {
        $date = $targetDate ?: Carbon::today();

        $stats = ScanLog::whereBetween('scanned_at', [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
        ])->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN scan_type = 'masuk'  THEN 1 ELSE 0 END) as total_masuk,
            SUM(CASE WHEN scan_type = 'pulang' THEN 1 ELSE 0 END) as total_pulang,
            SUM(CASE WHEN scan_type = 'gagal'  THEN 1 ELSE 0 END) as total_gagal
        ")->first();

        return $stats ?: (object) [
            'total' => 0,
            'total_masuk' => 0,
            'total_pulang' => 0,
            'total_gagal' => 0,
        ];
    }

    /**
     * Get distinct scanner users list for filtering.
     */
    public function getScannerUsers()
    {
        return ScanLog::select('user_id', 'user_name', 'user_role')
            ->distinct()
            ->whereNotNull('user_id')
            ->orderBy('user_name')
            ->get();
    }

    /**
     * Get top scanners who performed the most scans on the target date.
     */
    public function getTopScanners(?Carbon $targetDate = null, int $limit = 5)
    {
        $date = $targetDate ?: Carbon::today();

        return ScanLog::whereBetween('scanned_at', [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
        ])
            ->selectRaw('user_id, user_name, user_role, COUNT(*) as total_scan')
            ->groupBy('user_id', 'user_name', 'user_role')
            ->orderByDesc('total_scan')
            ->limit($limit)
            ->get();
    }
}
