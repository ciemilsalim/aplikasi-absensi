<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // Bisa email atau NIP
            'password' => 'required',
        ]);

        $user = User::where('email', $request->login)->first();

        // Jika bukan email, coba cari guru berdasarkan NIP
        if (!$user) {
            $teacher = Teacher::where('nip', $request->login)->first();
            if ($teacher) {
                $user = User::find($teacher->user_id);
            }
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        if ($user->role !== 'teacher') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Anda bukan Wali Kelas.',
            ], 403);
        }

        // Dapatkan data guru
        $teacherInfo = $user->teacher;
        $isHomeroom = $teacherInfo ? $teacherInfo->homeroomClass()->exists() : false;

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_homeroom' => $isHomeroom,
                'teacher_info' => $teacherInfo,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }
}
