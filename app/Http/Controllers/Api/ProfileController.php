<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the teacher profile information.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data guru tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'name'          => $teacher->name,
                'nip'           => $teacher->nip,
                'email'         => $user->email,
                'phone_number'  => $teacher->phone_number,
                'photo_url'     => $teacher->photo ? asset('storage/' . $teacher->photo) : null,
                'homeroom_class' => $teacher->homeroomClass ? $teacher->homeroomClass->name : 'Bukan Wali Kelas',
                'joined_at'     => $teacher->created_at->format('d M Y'),
            ]
        ]);
    }

    /**
     * Update teacher profile (phone_number & optional password change).
     */
    public function update(Request $request)
    {
        $user    = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data guru tidak ditemukan.'
            ], 404);
        }

        $request->validate([
            'phone_number'        => 'nullable|string|max:20',
            'current_password'    => 'required_with:new_password|string',
            'new_password'        => ['nullable', 'string', 'confirmed', Password::min(6)],
        ]);

        // Update nomor telepon guru
        if ($request->filled('phone_number')) {
            $teacher->phone_number = $request->phone_number;
            $teacher->save();
        }

        // Update password jika diisi
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Password saat ini tidak sesuai.',
                ], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }
}

