<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Menampilkan dasbor untuk guru yang sedang login.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Ambil data user yang sedang login
        $user = Auth::user();

        // 2. Ambil data guru yang terhubung dengan user tersebut.
        //    'teacher' adalah nama metode relasi yang ada di model User.
        $teacher = $user->teacher;

        // 3. Kirim data guru ke tampilan dasbor guru.
        //    Data user (seperti email) dapat diakses melalui relasi $teacher->user
        return view('teacher.dashboard', compact('teacher'));
    }
}
