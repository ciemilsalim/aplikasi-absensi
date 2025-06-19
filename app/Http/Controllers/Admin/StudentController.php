<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::orderBy('name')->paginate(10);
        return view('admin.students.index', compact('students'));
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'nis' => 'required|string|max:50|unique:students,nis']);
        Student::create($request->all());
        return redirect()->route('admin.students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function edit(Student $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate(['name' => 'required|string|max:255', 'nis' => 'required|string|max:50|unique:students,nis,' . $student->id]);
        $student->update($request->all());
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Menampilkan halaman untuk mencetak QR Code semua siswa.
     */
    public function qr()
    {
        $students = Student::orderBy('name')->get(); // Ambil semua siswa, tidak perlu paginasi
        return view('admin.students.qr', compact('students'));
    }
}
