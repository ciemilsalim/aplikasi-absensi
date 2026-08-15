<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AboutController extends Controller
{
    /**
     * Menampilkan halaman "Tentang Aplikasi".
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (Auth::check()) {
            return view('about');
        }

        return view('about_public');
    }
}
