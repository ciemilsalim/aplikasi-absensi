<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Menampilkan daftar mata pelajaran.
     */
    public function index(Request $request)
    {
        $sortBy = in_array($request->query('sort_by'), ['name', 'code']) 
            ? $request->query('sort_by') 
            : 'created_at';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'desc';

        $perPage = in_array($request->query('per_page'), [10, 25, 50, 100])
            ? $request->query('per_page')
            : 10;

        $query = Subject::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        $query->orderBy($sortBy, $sortDirection);

        $subjects = $query->paginate($perPage);

        return view('admin.subjects.index', compact('subjects', 'sortBy', 'sortDirection', 'perPage'));
    }

    /**
     * Menampilkan form untuk membuat mata pelajaran baru.
     */
    public function create()
    {
        return view('admin.subjects.create');
    }

    /**
     * Menyimpan mata pelajaran baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:subjects,code'],
            'description' => ['nullable', 'string'],
        ]);

        Subject::create($request->all());

        return redirect()->route('admin.subjects.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit mata pelajaran.
     */
    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    /**
     * Memperbarui data mata pelajaran di database.
     */
    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:subjects,code,' . $subject->id],
            'description' => ['nullable', 'string'],
        ]);

        $subject->update($request->all());

        return redirect()->route('admin.subjects.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus mata pelajaran dari database.
     */
    public function destroy(Subject $subject)
    {
        // Cek apakah mata pelajaran ini masih diajar oleh guru
        if ($subject->teachers()->count() > 0) {
            return redirect()->route('admin.subjects.index')->with('error', 'Mata pelajaran tidak dapat dihapus karena masih ada guru yang mengampu.');
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
