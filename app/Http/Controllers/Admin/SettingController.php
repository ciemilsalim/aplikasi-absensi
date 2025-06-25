<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Menampilkan halaman pengaturan Identitas & Lokasi.
     */
    public function identity()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.identity', compact('settings'));
    }

    /**
     * Menampilkan halaman pengaturan Tampilan & Logo.
     */
    public function appearance()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.appearance', compact('settings'));
    }

    /**
     * Menampilkan halaman pengaturan Waktu Absensi.
     */
    public function attendance()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.attendance', compact('settings'));
    }

    /**
     * Memperbarui pengaturan berdasarkan form yang disubmit.
     */
    public function update(Request $request)
    {
        $rules = [];
        $settingsToUpdate = [];

        // Validasi untuk form Identitas & Lokasi
        if ($request->has('school_latitude')) {
            $rules = [
                'school_name' => 'required|string|max:255',
                'school_address' => 'nullable|string|max:500',
                'school_latitude' => 'required|numeric',
                'school_longitude' => 'required|numeric',
                'attendance_radius' => 'required|integer|min:10',
            ];
            $settingsToUpdate = $request->only(array_keys($rules));
        }
        
        // Validasi untuk form Tampilan & Logo
        if ($request->hasFile('app_logo') || $request->has('dark_mode_checkbox')) {
            $rules['app_logo'] = 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048';
            if ($request->has('dark_mode_checkbox')) {
                $settingsToUpdate['dark_mode'] = $request->has('dark_mode') ? 'on' : 'off';
            }
        }
        
        // Validasi untuk form Waktu Absensi
        if ($request->has('jam_masuk')) {
             $rules = array_merge($rules, [
                'jam_masuk' => 'required|date_format:H:i',
                'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
            ]);
            $settingsToUpdate = array_merge($settingsToUpdate, $request->only(['jam_masuk', 'jam_pulang']));
        }
        
        // Jalankan validasi
        $request->validate($rules);
        
        // Simpan pengaturan ke database
        foreach ($settingsToUpdate as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        // Handle unggah logo jika ada
        if ($request->hasFile('app_logo')) {
            $oldLogoSetting = Setting::where('key', 'app_logo')->first();
            if ($oldLogoSetting && $oldLogoSetting->value) { Storage::disk('public')->delete($oldLogoSetting->value); }
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
