<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicPeriodController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::with('semesters')->orderBy('name', 'desc')->get();
        return view('admin.academic_periods.index', compact('academicYears'));
    }

    public function storeYear(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:academic_years,name',
        ]);

        AcademicYear::create([
            'name' => $request->name,
            'is_active' => false,
        ]);

        return back()->with('success', 'Tahun Ajaran berhasil ditambahkan.');
    }

    public function storeSemester(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string',
        ]);

        Semester::create([
            'academic_year_id' => $request->academic_year_id,
            'name' => $request->name,
            'is_active' => false,
        ]);

        return back()->with('success', 'Semester berhasil ditambahkan.');
    }

    public function activateYear($id)
    {
        DB::transaction(function () use ($id) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            AcademicYear::where('id', $id)->update(['is_active' => true]);
        });

        return back()->with('success', 'Tahun Ajaran berhasil diaktifkan.');
    }

    public function activateSemester($id)
    {
        $semester = Semester::findOrFail($id);
        
        DB::transaction(function () use ($semester) {
            Semester::where('academic_year_id', $semester->academic_year_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            $semester->update(['is_active' => true]);
        });

        return back()->with('success', 'Semester berhasil diaktifkan.');
    }

    public function destroyYear($id)
    {
        $year = AcademicYear::findOrFail($id);
        if ($year->is_active) {
            return back()->with('error', 'Tahun Ajaran aktif tidak dapat dihapus.');
        }
        $year->delete();
        return back()->with('success', 'Tahun Ajaran berhasil dihapus.');
    }

    public function destroySemester($id)
    {
        $semester = Semester::findOrFail($id);
        if ($semester->is_active) {
            return back()->with('error', 'Semester aktif tidak dapat dihapus.');
        }
        $semester->delete();
        return back()->with('success', 'Semester berhasil dihapus.');
    }
}
