<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\SchoolClass; // Impor model SchoolClass
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
            'school_class_id' => 'nullable|integer|exists:school_classes,id', // Validasi untuk filter kelas
        ]);

        // 2. Tentukan tanggal yang akan difilter
        $selectedDate = $request->filled('tanggal')
                        ? Carbon::createFromFormat('Y-m-d', $request->tanggal)
                        : Carbon::today();

        // Buat query dasar, dan muat relasi siswa beserta kelasnya
        $attendancesQuery = Attendance::with(['student.schoolClass']) // Diperbarui untuk memuat data kelas
                                      ->whereDate('attendance_time', $selectedDate);

        // 4. Tambahkan filter pencarian nama jika ada
        if ($request->filled('search')) {
            $search = $request->search;
            $attendancesQuery->whereHas('student', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

                // 5. Terapkan filter kelas jika ada
        if ($request->filled('school_class_id')) {
            $attendancesQuery->whereHas('student', function ($query) use ($request) {
                $query->where('school_class_id', $request->school_class_id);
            });
        }

        // 5. Ambil data yang sudah difilter dengan paginasi
        $attendances = $attendancesQuery->latest('attendance_time')->paginate(15);

        // Ambil daftar semua kelas untuk filter di view
        $classes = SchoolClass::all();

        // 6. Kirim data ke view
        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'classes' => $classes, // Kirim daftar kelas ke view
        ]);
    }
}
