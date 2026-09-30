<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScanLog;
use App\Models\User;
use Carbon\Carbon;

class ScanLogController extends Controller
{
    /**
     * Tampilkan halaman monitoring log scan absensi siswa.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
            'user_id'    => 'nullable|integer',
            'scan_type'  => 'nullable|in:masuk,pulang,gagal',
            'search'     => 'nullable|string|max:255',
            'per_page'   => 'nullable|integer|in:15,25,50,100',
        ]);

        $perPage = $request->get('per_page', 25);
        $today   = Carbon::today();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : $today->copy()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : $today->copy()->endOfDay();

        $logsQuery = ScanLog::with(['user', 'student.schoolClass'])
            ->whereBetween('scanned_at', [$dateFrom, $dateTo])
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('scan_type'), fn($q) => $q->where('scan_type', $request->scan_type))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('user_name', 'like', $search)
                        ->orWhere('student_name', 'like', $search)
                        ->orWhere('student_nis', 'like', $search);
                });
            })
            ->latest('scanned_at');

        // Statistik harian (untuk tampilan hari ini)
        $todayStats = ScanLog::whereBetween('scanned_at', [
            $today->copy()->startOfDay(),
            $today->copy()->endOfDay(),
        ])->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN scan_type = 'masuk'  THEN 1 ELSE 0 END) as total_masuk,
            SUM(CASE WHEN scan_type = 'pulang' THEN 1 ELSE 0 END) as total_pulang,
            SUM(CASE WHEN scan_type = 'gagal'  THEN 1 ELSE 0 END) as total_gagal
        ")->first();

        // Daftar user scanner (untuk filter dropdown)
        $scannerUsers = ScanLog::select('user_id', 'user_name', 'user_role')
            ->distinct()
            ->whereNotNull('user_id')
            ->orderBy('user_name')
            ->get();

        // Top scanner (siapa yang paling banyak scan hari ini)
        $topScanners = ScanLog::whereBetween('scanned_at', [
            $today->copy()->startOfDay(),
            $today->copy()->endOfDay(),
        ])
            ->selectRaw('user_id, user_name, user_role, COUNT(*) as total_scan')
            ->groupBy('user_id', 'user_name', 'user_role')
            ->orderByDesc('total_scan')
            ->limit(5)
            ->get();

        $logs = $logsQuery->paginate($perPage)->withQueryString();

        return view('admin.scan-logs.index', compact(
            'logs',
            'dateFrom',
            'dateTo',
            'todayStats',
            'scannerUsers',
            'topScanners',
            'perPage',
        ));
    }
}
