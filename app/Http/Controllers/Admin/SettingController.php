<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255', // Validasi untuk nama sekolah
            'school_address' => 'nullable|string|max:500', // Validasi untuk alamat
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
            'app_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Simpan semua pengaturan teks
        $settingsToUpdate = $request->only(['school_name', 'school_address', 'jam_masuk', 'jam_pulang']);
        $settingsToUpdate['dark_mode'] = $request->has('dark_mode') ? 'on' : 'off';
        
        foreach ($settingsToUpdate as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        // Handle unggah logo
        if ($request->hasFile('app_logo')) {
            $oldLogoSetting = Setting::where('key', 'app_logo')->first();
            if ($oldLogoSetting && $oldLogoSetting->value) {
                Storage::disk('public')->delete($oldLogoSetting->value);
            }
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
