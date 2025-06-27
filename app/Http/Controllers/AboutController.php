<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Menampilkan halaman "Tentang Aplikasi".
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Anda bisa menambahkan logika lain di sini jika diperlukan
        return view('about');
    }
}
