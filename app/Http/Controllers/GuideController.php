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
        $userRoleLabel = 'Pengunjung / Publik';

        if (Auth::check()) {
            $user = Auth::user();

            if ($user->hasAnyRole(['wakasek_kurikulum', 'wakasek kurikulum', 'waka_kurikulum', 'waka kurikulum', 'kepala_sekolah', 'kepala sekolah', 'headmaster'])) {
                $role = 'supervisor';
                $userRoleLabel = 'Pimpinan / Wakasek Kurikulum / Kepala Sekolah';
            } elseif ($user->hasAnyRole(['admin', 'operator'])) {
                $role = 'admin';
                $userRoleLabel = 'Administrator & Operator';
            } elseif ($user->hasRole('teacher') || $user->teacher !== null) {
                $role = 'teacher';
                $userRoleLabel = 'Guru Mata Pelajaran & Wali Kelas';
            } elseif ($user->hasRole('parent') || $user->parent !== null) {
                $role = 'parent';
                $userRoleLabel = 'Orang Tua / Wali Murid';
            } elseif ($user->hasRole('satpam') || $user->hasRole('guru_piket')) {
                $role = 'satpam';
                $userRoleLabel = 'Petugas Piket & Satpam';
            } else {
                $role = 'all';
                $userRoleLabel = 'Pengguna';
            }
        }

        $defaultTab = $request->query('tab', $role);

        if (Auth::check()) {
            return view('guide', compact('defaultTab', 'userRoleLabel'));
        }

        return view('guide_public', compact('defaultTab', 'userRoleLabel'));
    }
}
