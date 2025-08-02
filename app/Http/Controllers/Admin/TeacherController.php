<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Imports\TeachersImport; // Impor kelas baru
use Maatwebsite\Excel\Facades\Excel; // Impor facade Excel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        // Validasi parameter untuk pengurutan
        $sortBy = in_array($request->query('sort_by'), ['name', 'nip', 'email']) 
            ? $request->query('sort_by') 
            : 'created_at';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'desc';

        // Validasi parameter untuk jumlah data per halaman
        $perPage = in_array($request->query('per_page'), [10, 25, 50, 100])
            ? $request->query('per_page')
            : 10;

        $query = Teacher::query()->with('user');

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

        // Terapkan pengurutan
        if ($sortBy === 'email') {
            $query->join('users', 'teachers.user_id', '=', 'users.id')
                  ->orderBy('users.email', $sortDirection)
                  ->select('teachers.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $teachers = $query->paginate($perPage);

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'nip' => ['nullable', 'string', 'max:255', 'unique:teachers'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
        ]);

        $user->teacher()->create($request->only('name', 'nip', 'phone_number'));

        return redirect()->route('admin.teachers.index')->with('success', 'Akun guru berhasil dibuat.');
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$teacher->user_id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'nip' => ['nullable', 'string', 'max:255', 'unique:teachers,nip,'.$teacher->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $teacher->user;
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $teacher->update($request->only('name', 'nip', 'phone_number'));

        return redirect()->route('admin.teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

     /**
     * Menampilkan form untuk impor data guru dari Excel.
     */
    public function showImportForm()
    {
        return view('admin.teachers.import');
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
            Excel::import(new TeachersImport, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             foreach ($failures as $failure) {
                 $errorMessages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
             }
             return redirect()->back()->with('import_errors', $errorMessages);
        }

        return redirect()->route('admin.teachers.index')->with('success', 'Data guru berhasil diimpor!');
    }

    public function getOnlineStatus()
    {
        // Guru dianggap online jika aktivitas terakhirnya dalam 5 menit terakhir
        $onlineTeacherUserIds = User::where('role', 'teacher')
                               ->where('last_seen_at', '>', now()->subMinutes(5))
                               ->pluck('id');

        return response()->json($onlineTeacherUserIds);
    }
    
    /**
     * Menghapus data guru dari database.
     */
    public function destroy(Teacher $teacher)
    {
        // Karena relasi di database diatur dengan onDelete('cascade'),
        // menghapus data user akan otomatis menghapus data guru yang terhubung.
        $teacher->user()->delete();
        
        return redirect()->route('admin.teachers.index')->with('success', 'Akun guru berhasil dihapus.');
    }
}
