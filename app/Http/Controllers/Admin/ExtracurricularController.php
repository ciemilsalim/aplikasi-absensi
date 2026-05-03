<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ExtracurricularController extends Controller
{
    public function index()
    {
        $extracurriculars = Extracurricular::with('coach', 'students')->get();
        return view('admin.extracurriculars.index', compact('extracurriculars'));
    }

    public function create()
    {
        $teachers = Teacher::orderBy('name')->get();
        return view('admin.extracurriculars.create', compact('teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        Extracurricular::create($request->all());

        return redirect()->route('admin.extracurriculars.index')->with('success', 'Ekstrakurikuler berhasil ditambahkan.');
    }

    public function edit(Extracurricular $extracurricular)
    {
        $teachers = Teacher::orderBy('name')->get();
        return view('admin.extracurriculars.edit', compact('extracurricular', 'teachers'));
    }

    public function update(Request $request, Extracurricular $extracurricular)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $extracurricular->update($request->all());

        return redirect()->route('admin.extracurriculars.index')->with('success', 'Ekstrakurikuler berhasil diperbarui.');
    }

    public function destroy(Extracurricular $extracurricular)
    {
        $extracurricular->delete();
        return redirect()->route('admin.extracurriculars.index')->with('success', 'Ekstrakurikuler berhasil dihapus.');
    }

    public function students(Extracurricular $extracurricular)
    {
        $extracurricular->load('students.schoolClass');
        $classes = SchoolClass::with('students')->orderBy('name')->get();
        return view('admin.extracurriculars.students', compact('extracurricular', 'classes'));
    }

    public function assignStudents(Request $request, Extracurricular $extracurricular)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $extracurricular->students()->syncWithoutDetaching($request->student_ids);

        return back()->with('success', 'Siswa berhasil ditambahkan ke ekstrakurikuler.');
    }

    public function removeStudent(Extracurricular $extracurricular, Student $student)
    {
        $extracurricular->students()->detach($student->id);
        return back()->with('success', 'Siswa berhasil dihapus dari ekstrakurikuler.');
    }
}
