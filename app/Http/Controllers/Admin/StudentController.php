<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Imports\StudentsImport; // Impor kelas baru
use Maatwebsite\Excel\Facades\Excel; // Impor facade Excel

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::orderBy('name')->paginate(10);
        return view('admin.students.index', compact('students'));
    }

    public function qr()
    {
        $students = Student::orderBy('name')->get();
        return view('admin.students.qr', compact('students'));
    }

    public function create() { /* ... kode tetap sama ... */ }
    public function store(Request $request) { /* ... kode tetap sama ... */ }
    public function edit(Student $student) { /* ... kode tetap sama ... */ }
    public function update(Request $request, Student $student) { /* ... kode tetap sama ... */ }
    public function destroy(Student $student) { /* ... kode tetap sama ... */ }

    /**
     * Menampilkan form untuk impor Excel.
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
