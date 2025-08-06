<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\User;
use App\Models\Student;
use App\Imports\ParentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ParentController extends Controller
{
    /**
     * Menampilkan daftar semua orang tua dengan paginasi, sortir, dan filter.
     */
    public function index(Request $request)
    {
        // Validasi parameter untuk pengurutan
        $sortBy = in_array($request->query('sort_by'), ['name', 'email', 'students_count']) 
            ? $request->query('sort_by') 
            : 'created_at';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'desc';

        // Validasi parameter untuk jumlah data per halaman
        $perPage = in_array($request->query('per_page'), [10, 25, 50, 100])
            ? $request->query('per_page')
            : 10;

        $query = ParentModel::query()->with('user')->withCount('students');

        // Terapkan filter pencarian jika ada
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // PERBAIKAN: Menggunakan subquery untuk sortir berdasarkan email
        // Ini memastikan kolom 'students_count' tidak hilang.
        if ($sortBy === 'email') {
            $query->orderBy(
                User::select('email')
                    ->whereColumn('users.id', 'parents.user_id')
                    ->limit(1),
                $sortDirection
            );
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $parents = $query->paginate($perPage);

        return view('admin.parents.index', [
            'parents' => $parents,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'perPage' => $perPage,
        ]);
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
     * Menampilkan form untuk menghubungkan siswa ke akun orang tua.
     */
    public function edit(ParentModel $parent)
    {
        $studentsLinked = $parent->students()->with('schoolClass')->orderBy('name')->get();
        $studentsNotLinked = Student::whereDoesntHave('parents')->with('schoolClass')->orderBy('name')->get();

        return view('admin.parents.edit', compact('parent', 'studentsLinked', 'studentsNotLinked'));
    }

    /**
     * Memperbarui hubungan antara orang tua dan siswa.
     */
    public function update(Request $request, ParentModel $parent)
    {
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

        return redirect()->route('admin.parents.edit', $parent)->with('success', 'Hubungan siswa berhasil diperbarui.');
    }

    /**
     * Menghapus akun orang tua dan user yang terkait.
     */
    public function destroy(ParentModel $parent)
    {
        $parent->user()->delete();
        
        return redirect()->route('admin.parents.index')->with('success', 'Akun orang tua berhasil dihapus.');
    }

    /**
     * Menampilkan form untuk impor data orang tua dari Excel.
     */
    public function showImportForm()
    {
        return view('admin.parents.import');
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
            Excel::import(new ParentsImport, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             foreach ($failures as $failure) {
                 $errorMessages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
             }
             return redirect()->back()->with('import_errors', $errorMessages);
        }

        return redirect()->route('admin.parents.index')->with('success', 'Data orang tua berhasil diimpor!');
    }


    /**
     * Mengambil daftar ID user orang tua yang sedang online.
     */
    public function getOnlineStatus()
    {
        $onlineParentUserIds = User::where('role', 'parent')
                               ->where('last_seen_at', '>', now()->subMinutes(5))
                               ->pluck('id');

        return response()->json($onlineParentUserIds);
    }
}
