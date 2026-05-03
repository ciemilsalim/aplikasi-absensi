<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExtracurricularController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan guru'], 403);
        }

        $extracurriculars = $teacher->coachingExtracurriculars()->get();

        return response()->json([
            'status' => 'success',
            'data' => $extracurriculars
        ]);
    }

    public function getStudents($id)
    {
        $teacher = Auth::user()->teacher;
        $extracurricular = Extracurricular::findOrFail($id);

        // Otorisasi: Pastikan guru ini adalah pembinanya
        if ($extracurricular->teacher_id !== $teacher->id) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan pembina ekskul ini'], 403);
        }

        $date = request('date', Carbon::today()->toDateString());
        $students = $extracurricular->students()->with(['attendances' => function($q) use ($date, $id) {
            $q->where('date', $date)->where('extracurricular_id', $id);
        }])->get()->map(function($student) {
            $attendance = $student->attendances->first();
            return [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
                'status' => $attendance ? $attendance->status : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'extracurricular' => $extracurricular,
            'students' => $students
        ]);
    }

    public function storeAttendance(Request $request)
    {
        $request->validate([
            'extracurricular_id' => 'required|exists:extracurriculars,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa',
            'date' => 'nullable|date',
        ]);

        $date = $request->date ?? Carbon::today()->toDateString();
        $extracurricularId = $request->extracurricular_id;

        foreach ($request->attendances as $att) {
            ExtracurricularAttendance::updateOrCreate(
                [
                    'extracurricular_id' => $extracurricularId,
                    'student_id' => $att['student_id'],
                    'date' => $date,
                ],
                [
                    'status' => $att['status'],
                    'teacher_id' => Auth::user()->teacher->id,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi ekskul berhasil disimpan untuk tanggal ' . $date
        ]);
    }
}
