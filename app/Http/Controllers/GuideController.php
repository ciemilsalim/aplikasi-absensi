<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuideController extends Controller
{
    /**
     * Menampilkan halaman Panduan Pengguna interaktif.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $role = 'all';

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('teacher') || $user->teacher !== null) {
                $role = 'teacher';
            } elseif ($user->hasAnyRole(['admin', 'operator'])) {
                $role = 'admin';
            } elseif ($user->hasRole('parent') || $user->parent !== null) {
                $role = 'parent';
            } elseif ($user->hasRole('satpam')) {
                $role = 'satpam';
            }
        }

        $defaultTab = $request->query('tab', $role);

        if (Auth::check()) {
            return view('guide', compact('defaultTab'));
        }

        return view('guide_public', compact('defaultTab'));
    }
}
