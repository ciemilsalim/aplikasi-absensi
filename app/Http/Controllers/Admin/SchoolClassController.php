<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::with('homeroomTeacher')->withCount('students')->paginate(10);
        $teachers = Teacher::with('homeroomClass')->orderBy('name')->get();
        return view('admin.classes.index', compact('classes', 'teachers'));
    }

    public function create()
    {
        return redirect()->route('admin.classes.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:school_classes,name',
            'teacher_id' => 'nullable|exists:teachers,id|unique:school_classes,teacher_id',
        ], [
            'teacher_id.unique' => 'Guru ini sudah menjadi wali di kelas lain.'
        ]);
        SchoolClass::create($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil ditambahkan.');
    }
    
    public function edit(SchoolClass $schoolClass)
    {
        $teachers = Teacher::with('homeroomClass')->orderBy('name')->get();
        return view('admin.classes.edit', compact('schoolClass', 'teachers'));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('school_classes')->ignore($schoolClass->id)],
            'teacher_id' => ['nullable', 'exists:teachers,id', Rule::unique('school_classes', 'teacher_id')->ignore($schoolClass->id)],
        ], [
            'teacher_id.unique' => 'Guru ini sudah menjadi wali di kelas lain.'
        ]);
        
        $schoolClass->update($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(SchoolClass $schoolClass)
    {
        $schoolClass->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Menampilkan form untuk penempatan siswa massal.
     * PERBAIKAN: Memastikan query mengambil data yang benar untuk setiap kolom.
     */
    public function showAssignForm(SchoolClass $schoolClass)
    {
        // Mengambil siswa yang ID kelasnya cocok dengan kelas ini
        $studentsInClass = Student::where('school_class_id', $schoolClass->id)->orderBy('name')->get();
        
        // Mengambil siswa yang ID kelasnya masih kosong (null)
        $studentsWithoutClass = Student::whereNull('school_class_id')->orderBy('name')->get();

        return view('admin.classes.assign', compact('schoolClass', 'studentsInClass', 'studentsWithoutClass'));
    }

    /**
     * Memproses penempatan siswa massal.
     */
    public function assignStudents(Request $request)
    {
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'students_to_add' => 'nullable|array',
            'students_to_remove' => 'nullable|array',
        ]);

        if ($request->has('students_to_add')) {
            Student::whereIn('id', $request->students_to_add)->update(['school_class_id' => $request->school_class_id]);
        }

        if ($request->has('students_to_remove')) {
            Student::whereIn('id', $request->students_to_remove)->update(['school_class_id' => null]);
        }

        return redirect()->route('admin.classes.assign', $request->school_class_id)->with('success', 'Data siswa di kelas berhasil diperbarui.');
    }
}
