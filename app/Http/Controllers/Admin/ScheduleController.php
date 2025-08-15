<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Menampilkan halaman pemilihan kelas untuk melihat jadwal.
     */
    public function index()
    {
        // UBAH BARIS INI: Tambahkan with('level') untuk eager loading
        $classes = SchoolClass::with('level')->orderBy('name')->get();
        
        return view('admin.schedules.index', compact('classes'));
    }

    /**
     * Menampilkan tabel jadwal untuk kelas yang dipilih.
     */
    public function show(SchoolClass $schoolClass)
    {
        // Ambil semua penugasan (guru-mapel) untuk kelas ini
        $assignments = TeachingAssignment::with(['subject', 'teacher'])
            ->where('school_class_id', $schoolClass->id)
            ->get()
            ->sortBy('subject.name');

        // Ambil jadwal yang sudah ada dan kelompokkan berdasarkan hari dan jam
        $schedules = Schedule::whereIn('teaching_assignment_id', $assignments->pluck('id'))
            ->with('teachingAssignment.subject', 'teachingAssignment.teacher')
            ->get()
            ->groupBy(['day_of_week', 'start_time']);

        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return view('admin.schedules.show', compact('schoolClass', 'assignments', 'schedules', 'days'));
    }

    /**
     * Menyimpan entri jadwal baru.
     */
    public function store(Request $request, SchoolClass $schoolClass)
    {
        $request->validate([
            'teaching_assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        // Validasi agar tidak ada jadwal yang tumpang tindih untuk kelas yang sama
        $isOverlapping = Schedule::whereHas('teachingAssignment', function ($query) use ($schoolClass) {
            $query->where('school_class_id', $schoolClass->id);
        })
        ->where('day_of_week', $request->day_of_week)
        ->where(function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where('start_time', '<', $request->end_time)
                  ->where('end_time', '>', $request->start_time);
            });
        })->exists();

        if ($isOverlapping) {
            return back()->with('error', 'Jadwal tumpang tindih dengan jadwal yang sudah ada di kelas ini.');
        }

        Schedule::create($request->all());

        return redirect()->route('admin.schedules.show', $schoolClass)->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Menghapus entri jadwal.
     */
    public function destroy(Schedule $schedule)
    {
        $schoolClassId = $schedule->teachingAssignment->school_class_id;
        $schedule->delete();

        return redirect()->route('admin.schedules.show', $schoolClassId)->with('success', 'Jadwal berhasil dihapus.');
    }
}
