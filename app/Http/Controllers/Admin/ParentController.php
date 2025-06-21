<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ParentController extends Controller
{
    /**
     * Menampilkan daftar semua orang tua.
     */
    public function index()
    {
        $parents = ParentModel::with('user')->withCount('students')->latest()->paginate(10);
        return view('admin.parents.index', compact('parents'));
    }

    /**
     * Menampilkan form untuk membuat akun orang tua baru.
     */
    public function create()
    {
        return view('admin.parents.create');
    }

    /**
     * Menyimpan akun orang tua baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255', 'unique:parents,phone_number'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'parent',
        ]);

        $user->parent()->create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->route('admin.parents.index')->with('success', 'Akun orang tua berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk menghubungkan siswa ke orang tua.
     * PERBAIKAN: Menggunakan ID untuk mencari model secara manual.
     */
    public function edit($id)
    {
        $parent = ParentModel::findOrFail($id);

        // Ambil siswa yang sudah terhubung dengan orang tua ini
        $studentsLinked = $parent->students()->orderBy('name')->get();
        $linkedStudentIds = $studentsLinked->pluck('id');

        // Ambil siswa yang belum punya wali sama sekali
        $studentsNotLinked = Student::whereDoesntHave('parents')->orderBy('name')->get();

        return view('admin.parents.edit', compact('parent', 'studentsLinked', 'studentsNotLinked'));
    }

    /**
     * Memperbarui hubungan antara orang tua dan siswa.
     * PERBAIKAN: Menggunakan ID untuk mencari model secara manual.
     */
    public function update(Request $request, $id)
    {
        $parent = ParentModel::findOrFail($id);
        
        $request->validate([
            'students_to_add' => ['nullable', 'array'],
            'students_to_remove' => ['nullable', 'array'],
        ]);

        if ($request->has('students_to_add')) {
            $parent->students()->attach($request->students_to_add);
        }

        if ($request->has('students_to_remove')) {
            $parent->students()->detach($request->students_to_remove);
        }

        return redirect()->route('admin.parents.edit', $parent->id)->with('success', 'Hubungan siswa berhasil diperbarui.');
    }

    /**
     * Menghapus akun orang tua dan user yang terkait.
     * PERBAIKAN: Menggunakan ID untuk mencari model secara manual.
     */
    public function destroy($id)
    {
        $parent = ParentModel::findOrFail($id);
        $parent->user()->delete();
        
        return redirect()->route('admin.parents.index')->with('success', 'Akun orang tua berhasil dihapus.');
    }
}
