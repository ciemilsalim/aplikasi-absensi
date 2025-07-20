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
     * Menampilkan daftar siswa dengan fitur pencarian dan filter kelas.
     */
    public function index(Request $request)
    {
        // Ambil semua kelas untuk ditampilkan di dropdown filter
        $classes = SchoolClass::orderBy('name')->get();

        $query = Student::with('schoolClass');

        // Terapkan filter pencarian jika ada
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        // PERBAIKAN: Terapkan filter berdasarkan kelas yang dipilih
        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        $students = $query->orderBy('name')->paginate(10);

        // Kirim data kelas ke view
        return view('admin.students.index', compact('students', 'classes'));
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
     * Menampilkan halaman pratinjau cetak kartu QR berdasarkan filter.
     */
    public function qr(Request $request)
    {
        $query = Student::with('schoolClass');

        // PERBAIKAN: Menerapkan filter yang sama dari halaman index
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

        // Mengambil semua siswa yang cocok (tanpa paginasi) untuk dicetak
        $students = $query->orderBy('name')->get();
        
        return view('admin.students.qr', compact('students'));
    }
}
