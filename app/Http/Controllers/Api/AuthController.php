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

        // Cek apakah role diizinkan (teacher atau parent)
        if (!in_array($user->role, ['teacher', 'parent'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $responseData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        // Tambahan info jika Guru
        if ($user->role === 'teacher') {
            $teacherInfo = $user->teacher;
            $responseData['is_homeroom'] = $teacherInfo ? $teacherInfo->homeroomClass()->exists() : false;
            $responseData['teacher_info'] = $teacherInfo;
        }

        // Tambahan info jika Ortu
        if ($user->role === 'parent') {
            $parentInfo = $user->parent;
            $responseData['parent_info'] = $parentInfo;
            // Sertakan daftar anak untuk inisialisasi cepat di mobile
            $responseData['students'] = $parentInfo ? $parentInfo->students()->with('schoolClass')->get() : [];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $responseData
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
