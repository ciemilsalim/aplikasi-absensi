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
    /**
     * Menampilkan halaman utama manajemen kelas.
     */
    public function index()
    {
        $classes = SchoolClass::with('homeroomTeacher')->withCount('students')->paginate(10);
        $teachers = Teacher::with('homeroomClass')->orderBy('name')->get();
        return view('admin.classes.index', compact('classes', 'teachers'));
    }
    
    /**
     * Menampilkan form untuk membuat kelas baru.
     */
    public function create()
    {
        // Fungsi ini bisa dibiarkan kosong jika form tambah ada di halaman index,
        // atau dibuat untuk halaman tambah terpisah di masa depan.
        return redirect()->route('admin.classes.index');
    }


    /**
     * Menyimpan data kelas baru.
     */
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

     public function edit(SchoolClass $class)
    {
        $teachers = Teacher::with('homeroomClass')->orderBy('name')->get();
        return view('admin.classes.edit', [
            'schoolClass' => $class, // Kirim ke view dengan nama yang konsisten
            'teachers' => $teachers,
        ]);
    }

    /**
     * Memperbarui data kelas yang sudah ada.
     */
    public function update(Request $request, SchoolClass $class)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('school_classes')->ignore($class->id)],
            'teacher_id' => ['nullable', 'exists:teachers,id', Rule::unique('school_classes', 'teacher_id')->ignore($class->id)],
        ], [
            'teacher_id.unique' => 'Guru ini sudah menjadi wali di kelas lain.'
        ]);
        
        $class->update($request->all());
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    /**
     * Menghapus data kelas.
     */
    public function destroy(SchoolClass $class)
    {
        $class->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Menampilkan form untuk penempatan siswa massal.
     */
    public function showAssignForm(SchoolClass $class)
    {
        $studentsInClass = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentsWithoutClass = Student::whereNull('school_class_id')->orderBy('name')->get();

        return view('admin.classes.assign', [
            'schoolClass' => $class,
            'studentsInClass' => $studentsInClass,
            'studentsWithoutClass' => $studentsWithoutClass,
        ]);
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
