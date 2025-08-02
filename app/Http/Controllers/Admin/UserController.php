<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua pengguna dengan fitur pencarian, filter, dan sortir.
     */
    public function index(Request $request)
    {
        // Validasi parameter untuk pengurutan
        $sortBy = in_array($request->query('sort_by'), ['name', 'email', 'role', 'last_seen_at']) 
            ? $request->query('sort_by') 
            : 'created_at';

        $sortDirection = in_array($request->query('sort_direction'), ['asc', 'desc']) 
            ? $request->query('sort_direction') 
            : 'desc';

        $query = User::query();

        // Terapkan filter pencarian jika ada
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Terapkan filter berdasarkan peran jika ada
        if ($request->filled('role')) {
            $query->where('role', 'request->role');
        }

        // Terapkan pengurutan
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate(10);

        // Mengirim data ke view, termasuk parameter sortir untuk membuat link
        return view('admin.users.index', [
            'users' => $users,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    /**
     * Menampilkan form untuk membuat pengguna baru.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Menyimpan pengguna baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'operator', 'teacher', 'parent'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Jika peran adalah guru atau orang tua, buat juga entri di tabel yang sesuai
        if ($request->role === 'teacher') {
            Teacher::create(['user_id' => $user->id, 'name' => $user->name]);
        } elseif ($request->role === 'parent') {
            ParentModel::create(['user_id' => $user->id, 'name' => $user->name]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk mengedit pengguna.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Memperbarui data pengguna di database.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'operator', 'teacher', 'parent'])],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Sinkronisasi data di tabel teacher/parent jika peran diubah
        if ($user->wasChanged('role')) {
            if ($user->role === 'teacher' && !$user->teacher) {
                Teacher::create(['user_id' => $user->id, 'name' => $user->name]);
                $user->parent?->delete();
            } elseif ($user->role === 'parent' && !$user->parent) {
                ParentModel::create(['user_id' => $user->id, 'name' => $user->name]);
                $user->teacher?->delete();
            } else {
                // Jika peran diubah menjadi admin atau operator, hapus data guru/ortu jika ada
                $user->teacher?->delete();
                $user->parent?->delete();
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna dari database.
     */
    public function destroy(User $user)
    {
        // Mencegah admin menghapus akunnya sendiri
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }
        
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Menghapus beberapa pengguna sekaligus (hapus massal).
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->input('user_ids');

        // Filter untuk memastikan admin tidak bisa menghapus dirinya sendiri
        $filteredUserIds = array_filter($userIds, function($id) {
            return $id != Auth::id();
        });

        if (count($filteredUserIds) < count($userIds)) {
            User::whereIn('id', $filteredUserIds)->delete();
            return redirect()->route('admin.users.index')->with('warning', 'Beberapa pengguna berhasil dihapus, tetapi Anda tidak dapat menghapus akun Anda sendiri.');
        }

        User::whereIn('id', $filteredUserIds)->delete();
        return redirect()->route('admin.users.index')->with('success', 'Pengguna yang dipilih berhasil dihapus.');
    }
}
