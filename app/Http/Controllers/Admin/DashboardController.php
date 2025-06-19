<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan data kehadiran di dasbor utama, dengan filter tanggal dan pencarian nama.
     */
    public function index(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
            'search' => 'nullable|string|max:255',
        ]);

        // 2. Tentukan tanggal yang akan difilter
        $selectedDate = $request->filled('tanggal')
                        ? Carbon::createFromFormat('Y-m-d', $request->tanggal)
                        : Carbon::today();

        // 3. Buat query dasar untuk data kehadiran
        $attendancesQuery = Attendance::with('student')
                                      ->whereDate('attendance_time', $selectedDate);

        // 4. Tambahkan filter pencarian nama jika ada
        if ($request->filled('search')) {
            $search = $request->search;
            $attendancesQuery->whereHas('student', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        // 5. Ambil data yang sudah difilter dengan paginasi
        $attendances = $attendancesQuery->latest('attendance_time')->paginate(15);

        // 6. Kirim data ke view
        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
        ]);
    }
}
