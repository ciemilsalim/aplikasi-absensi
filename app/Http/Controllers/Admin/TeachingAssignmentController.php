<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    /**
     * Menampilkan halaman untuk mengatur guru mata pelajaran untuk kelas tertentu.
     */
    public function index(SchoolClass $schoolClass)
    {
        // Ambil semua mata pelajaran, diurutkan berdasarkan nama
        $subjects = Subject::orderBy('name')->get();

        // Ambil semua guru, diurutkan berdasarkan nama
        $teachers = Teacher::orderBy('name')->get();

        // Ambil data penugasan yang sudah ada untuk kelas ini untuk ditampilkan di form
        $assignments = TeachingAssignment::where('school_class_id', $schoolClass->id)
            ->pluck('teacher_id', 'subject_id')
            ->all();

        return view('admin.classes.assign-teacher', compact('schoolClass', 'subjects', 'teachers', 'assignments'));
    }

    /**
     * Menyimpan atau memperbarui data penugasan guru mata pelajaran.
     */
    public function store(Request $request, SchoolClass $schoolClass)
    {
        $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        foreach ($request->assignments as $subjectId => $data) {
            // Jika guru dipilih (tidak kosong)
            if (!empty($data['teacher_id'])) {
                // Gunakan updateOrCreate untuk membuat data baru atau memperbarui yang sudah ada
                TeachingAssignment::updateOrCreate(
                    [
                        'school_class_id' => $schoolClass->id,
                        'subject_id' => $subjectId,
                    ],
                    [
                        'teacher_id' => $data['teacher_id'],
                    ]
                );
            } else {
                // Jika guru tidak dipilih (dikosongkan), hapus data penugasan jika ada
                TeachingAssignment::where('school_class_id', $schoolClass->id)
                    ->where('subject_id', $subjectId)
                    ->delete();
            }
        }

        return redirect()->route('admin.classes.index')->with('success', 'Pengaturan guru mata pelajaran untuk kelas ' . $schoolClass->name . ' berhasil diperbarui.');
    }
}
