<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;

class DashboardController extends Controller
{
    /**
     * Menampilkan data kehadiran di dasbor utama.
     */
    public function index()
    {
        // Ambil data kehadiran, urutkan dari yang terbaru, dan sertakan data siswa
        $attendances = Attendance::with('student')
                                ->latest('attendance_time')
                                ->paginate(15);

        return view('admin.dashboard', compact('attendances'));
    }
}
