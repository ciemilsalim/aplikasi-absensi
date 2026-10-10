<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminScanLogService;
use Illuminate\Http\Request;

class ScanLogController extends Controller
{
    public function __construct(
        protected AdminScanLogService $scanLogService
    ) {}

    /**
     * Tampilkan halaman monitoring log scan absensi siswa.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
            'user_id'    => 'nullable|integer',
            'scan_type'  => 'nullable|in:masuk,pulang,gagal',
            'search'     => 'nullable|string|max:255',
            'per_page'   => 'nullable|integer|in:15,25,50,100',
        ]);

        $data = $this->scanLogService->getScanLogsData($validated);

        return view('admin.scan-logs.index', $data);
    }
}
