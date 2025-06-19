<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon; // Pastikan Carbon diimpor

class DashboardController extends Controller
{
    /**
     * Menampilkan data kehadiran di dasbor utama, dengan filter tanggal.
     */
    public function index(Request $request)
    {
        // 1. Validasi input tanggal untuk memastikan formatnya benar
        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
        ]);

        // 2. Tentukan tanggal yang akan difilter.
        //    Jika ada tanggal di request, gunakan itu. Jika tidak, gunakan tanggal hari ini.
        $selectedDate = $request->filled('tanggal')
                        ? Carbon::createFromFormat('Y-m-d', $request->tanggal)
                        : Carbon::today();

        // 3. Ambil data kehadiran berdasarkan tanggal yang dipilih
        $attendances = Attendance::with('student')
                                ->whereDate('attendance_time', $selectedDate)
                                ->latest('attendance_time')
                                ->paginate(15);

        // 4. Kirim data yang sudah difilter dan tanggal yang dipilih ke view
        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
        ]);
    }
}
