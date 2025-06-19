<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Menampilkan halaman pengaturan.
     */
    public function index()
    {
        // Ambil semua pengaturan dari database, ubah menjadi format yang mudah diakses di view
        $settings = Setting::pluck('value', 'key');
        
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Menyimpan atau memperbarui pengaturan.
     */
    public function update(Request $request)
    {
        $request->validate([
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
        ]);

        // Loop melalui data yang divalidasi dan simpan ke database
        foreach ($request->only(['jam_masuk', 'jam_pulang']) as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
