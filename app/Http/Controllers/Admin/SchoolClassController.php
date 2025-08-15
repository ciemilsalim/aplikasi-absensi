<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Level; // <-- TAMBAHKAN INI
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = in_array($request->query('sort_by'), ['name', 'teacher_name', 'students_count']) 
            ? $request->query('sort_by') 
            : 'name';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'asc';

        $perPage = in_array($request->query('per_page'), [10, 25, 50, 100])
            ? $request->query('per_page')
            : 10;

        $query = SchoolClass::query()->with('homeroomTeacher')->withCount('students');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('homeroomTeacher', function($teacherQuery) use ($search) {
                      $teacherQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($sortBy === 'teacher_name') {
            $query->orderBy(
                Teacher::select('name')
                    ->whereColumn('teachers.id', 'school_classes.teacher_id')
                    ->limit(1),
                $sortDirection
            );
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $classes = $query->paginate($perPage);
        $teachers = Teacher::with('homeroomClass')->orderBy('name')->get();

        return view('admin.classes.index', [
            'classes' => $classes,
            'teachers' => $teachers,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'perPage' => $perPage,
        ]);
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
            'level_id' => 'required|exists:levels,id', // <-- TAMBAHKAN VALIDASI
        ], [
            'teacher_id.unique' => 'Guru ini sudah menjadi wali di kelas lain.'
        ]);
        SchoolClass::create($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil ditambahkan.');
    }
    
    public function edit(SchoolClass $class)
    {
        $teachers = Teacher::with('homeroomClass')->orderBy('name')->get();
        $levels = Level::orderBy('name')->get(); // <-- TAMBAHKAN INI
        return view('admin.classes.edit', compact('class', 'teachers', 'levels')); // <-- UBAH INI
    }

    public function update(Request $request, SchoolClass $class)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('school_classes')->ignore($class->id)],
            'teacher_id' => ['nullable', 'exists:teachers,id', Rule::unique('school_classes', 'teacher_id')->ignore($class->id)],
            'level_id' => ['required', 'exists:levels,id'], // <-- TAMBAHKAN VALIDASI
        ], [
            'teacher_id.unique' => 'Guru ini sudah menjadi wali di kelas lain.'
        ]);
        
        $class->update($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(SchoolClass $class)
    {
        $class->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function showAssignForm(SchoolClass $school_class)
    {
        $studentsInClass = $school_class->students()->orderBy('name')->get();
        $studentsWithoutClass = Student::whereNull('school_class_id')->orderBy('name')->get();

        return view('admin.classes.assign', [
            'class' => $school_class,
            'studentsInClass' => $studentsInClass,
            'studentsWithoutClass' => $studentsWithoutClass
        ]);
    }

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
