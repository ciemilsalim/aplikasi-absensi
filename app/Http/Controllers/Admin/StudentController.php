<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Imports\StudentsImport; // Pastikan kelas ini sudah diimpor
use Maatwebsite\Excel\Facades\Excel; // Pastikan facade Excel sudah diimpor
use App\Models\SchoolClass; // Impor model SchoolClass

class StudentController extends Controller
{
    public function index()
    {
         // Menambahkan relasi 'schoolClass' untuk efisiensi query
        $students = Student::with('schoolClass')->orderBy('name')->paginate(10);
        return view('admin.students.index', compact('students'));
    }

    /**
     * Menampilkan form untuk membuat siswa baru.
     */
    public function create()
    {
        // Ambil semua data kelas untuk ditampilkan di dropdown
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
            'school_class_id' => 'nullable|exists:school_classes,id', // Validasi untuk kelas
        ]);
        // Tambahkan logika ini untuk membuat unique_id secara manual
        $data = $request->all();
        $data['unique_id'] = (string) \Illuminate\Support\Str::uuid();

        Student::create($data); // Gunakan data yang sudah dimodifikasi

        return redirect()->route('admin.students.index')->with('success', 'Siswa berhasil ditambahkan.');
        }

    /**
     * Menampilkan form untuk mengedit data siswa.
     */
    public function edit(Student $student)
    {
        // Ambil semua data kelas untuk ditampilkan di dropdown
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
            'school_class_id' => 'nullable|exists:school_classes,id', // Validasi untuk kelas
        ]);
        $student->update($request->all());
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    public function qr()
    {
        $students = Student::with('schoolClass')->orderBy('name')->get();
        return view('admin.students.qr', compact('students'));
    }

    /**
     * Menampilkan form untuk impor Excel.
     * METODE INI MEMPERBAIKI ERROR ANDA.
     */
    public function showImportForm()
    {
        return view('admin.students.import');
    }

    /**
     * Menangani logika impor dari file Excel.
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
                 $errorMessages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
             }
             return redirect()->back()->with('import_errors', $errorMessages);
        }

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diimpor!');
    }
}
