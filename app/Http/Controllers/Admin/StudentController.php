<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * Menampilkan daftar siswa dengan fitur pencarian, filter, sortir, dan paginasi dinamis.
     */
    public function index(Request $request)
    {
        $classes = SchoolClass::orderBy('name')->get();
        
        $sortBy = in_array($request->query('sort_by'), ['name', 'nis', 'class_name']) 
            ? $request->query('sort_by') 
            : 'name';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'asc';

        $perPage = in_array($request->query('per_page'), [10, 25, 50, 100])
            ? $request->query('per_page')
            : 10;

        $query = Student::with('schoolClass');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        if ($sortBy === 'class_name') {
            $query->orderBy(
                SchoolClass::select('name')
                    ->whereColumn('id', 'students.school_class_id'),
                $sortDirection
            );
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $students = $query->paginate($perPage);

        return view('admin.students.index', [
            'students' => $students,
            'classes' => $classes,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Menampilkan form untuk membuat siswa baru.
     */
    public function create()
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.students.create', compact('classes'));
    }

    /**
     * Menyimpan data siswa baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:students,nis',
            'school_class_id' => 'nullable|exists:school_classes,id',
        ]);

        $data = $request->all();
        $data['unique_id'] = (string) Str::uuid();

        Student::create($data);

        return redirect()->route('admin.students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit data siswa.
     */
    public function edit(Student $student)
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.students.edit', compact('student', 'classes'));
    }

    /**
     * Memperbarui data siswa di database.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:students,nis,' . $student->id,
            'school_class_id' => 'nullable|exists:school_classes,id',
        ]);

        $student->update($request->all());
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data siswa.
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * [METODE BARU] Menghapus beberapa siswa sekaligus (hapus massal).
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        Student::whereIn('id', $request->student_ids)->delete();

        return redirect()->route('admin.students.index')->with('success', 'Siswa yang dipilih berhasil dihapus.');
    }

    /**
     * Menampilkan form untuk impor data siswa dari Excel.
     */
    public function showImportForm()
    {
        return view('admin.students.import');
    }

    /**
     * Menangani proses impor dari file Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             foreach ($failures as $failure) {
                 $errorMessages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors()) . ' (Nilai: ' . $failure->values()[$failure->attribute()] . ')';
             }
             return redirect()->back()->with('import_errors', $errorMessages);
        }

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diimpor!');
    }
    
    /**
     * Menampilkan halaman pratinjau cetak kartu QR.
     */
    public function qr(Request $request)
    {
        $query = Student::with('schoolClass');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        $students = $query->orderBy('name')->get();
        
        return view('admin.students.qr', compact('students'));
    }
}
