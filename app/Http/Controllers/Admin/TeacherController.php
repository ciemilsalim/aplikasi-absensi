<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Subject; // <-- TAMBAHKAN INI
use App\Imports\TeachersImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = in_array($request->query('sort_by'), ['name', 'nip', 'email']) 
            ? $request->query('sort_by') 
            : 'created_at';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'desc';

        $perPage = in_array($request->query('per_page'), [10, 25, 50, 100])
            ? $request->query('per_page')
            : 10;
        
        // Eager load relasi 'user' dan 'subjects' untuk efisiensi query
        $query = Teacher::query()->with(['user', 'subjects']); // <-- UBAH INI

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%");
                  });
            });
        }

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
        // Ambil semua data mata pelajaran untuk ditampilkan di form
        $subjects = Subject::orderBy('name')->get(); // <-- TAMBAHKAN INI
        return view('admin.teachers.create', compact('subjects')); // <-- UBAH INI
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'nip' => ['nullable', 'string', 'max:255', 'unique:teachers'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'subjects' => ['nullable', 'array'], // <-- TAMBAHKAN VALIDASI
            'subjects.*' => ['exists:subjects,id'], // <-- TAMBAHKAN VALIDASI
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
        ]);

        $teacher = $user->teacher()->create($request->only('name', 'nip', 'phone_number'));

        // Simpan relasi mata pelajaran yang dipilih
        $teacher->subjects()->sync($request->subjects); // <-- TAMBAHKAN INI

        return redirect()->route('admin.teachers.index')->with('success', 'Akun guru berhasil dibuat.');
    }

    public function edit(Teacher $teacher)
    {
        // Ambil semua data mata pelajaran
        $subjects = Subject::orderBy('name')->get(); // <-- TAMBAHKAN INI
        return view('admin.teachers.edit', compact('teacher', 'subjects')); // <-- UBAH INI
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$teacher->user_id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'nip' => ['nullable', 'string', 'max:255', 'unique:teachers,nip,'.$teacher->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'subjects' => ['nullable', 'array'], // <-- TAMBAHKAN VALIDASI
            'subjects.*' => ['exists:subjects,id'], // <-- TAMBAHKAN VALIDASI
        ]);

        $user = $teacher->user;
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $teacher->update($request->only('name', 'nip', 'phone_number'));

        // Perbarui relasi mata pelajaran dengan data yang baru
        $teacher->subjects()->sync($request->subjects); // <-- TAMBAHKAN INI

        return redirect()->route('admin.teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }
    
    public function showImportForm()
    {
        return view('admin.teachers.import');
    }

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
        $onlineTeacherUserIds = User::where('role', 'teacher')
                               ->where('last_seen_at', '>', now()->subMinutes(5))
                               ->pluck('id');

        return response()->json($onlineTeacherUserIds);
    }
    
    public function destroy(Teacher $teacher)
    {
        $teacher->user()->delete();
        
        return redirect()->route('admin.teachers.index')->with('success', 'Akun guru berhasil dihapus.');
    }
}
