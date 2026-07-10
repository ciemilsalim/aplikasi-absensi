<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Semester;

class AcademicPeriodController extends Controller
{
    public function setPeriod(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $semester = Semester::findOrFail($request->semester_id);

        session(['active_semester_id' => $semester->id]);
        session(['active_academic_year_id' => $semester->academic_year_id]);

        return redirect()->back()->with('success', 'Tahun Ajaran dan Semester berhasil diubah.');
    }
}
