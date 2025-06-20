<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::withCount('students')->paginate(10);
        return view('admin.classes.index', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:school_classes,name']);
        SchoolClass::create($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $request->validate(['name' => 'required|string|max:255|unique:school_classes,name,' . $schoolClass->id]);
        $schoolClass->update($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(SchoolClass $schoolClass)
    {
        $schoolClass->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function showAssignForm(SchoolClass $schoolClass)
    {
        $studentsInClass = Student::where('school_class_id', $schoolClass->id)->orderBy('name')->get();
        $studentsWithoutClass = Student::whereNull('school_class_id')->orderBy('name')->get();

        return view('admin.classes.assign', compact('schoolClass', 'studentsInClass', 'studentsWithoutClass'));
    }

    public function assignStudents(Request $request)
    {
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'students_to_add' => 'nullable|array',
            'students_to_remove' => 'nullable|array',
        ]);

        // Tambahkan siswa ke kelas
        if ($request->has('students_to_add')) {
            Student::whereIn('id', $request->students_to_add)->update(['school_class_id' => $request->school_class_id]);
        }

        // Hapus siswa dari kelas
        if ($request->has('students_to_remove')) {
            Student::whereIn('id', $request->students_to_remove)->update(['school_class_id' => null]);
        }

        return redirect()->route('admin.classes.assign', $request->school_class_id)->with('success', 'Data siswa di kelas berhasil diperbarui.');
    }
}
