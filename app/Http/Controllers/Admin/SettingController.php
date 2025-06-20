<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage; // Impor facade Storage

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
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
            'whacenter_device_id' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048', // Validasi untuk logo
        ]);

        // Simpan pengaturan teks dan toggle
        $settingsToUpdate = $request->only(['jam_masuk', 'jam_pulang', 'whacenter_device_id']);
        $settingsToUpdate['dark_mode'] = $request->has('dark_mode') ? 'on' : 'off';
        
        foreach ($settingsToUpdate as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Handle unggah logo
        if ($request->hasFile('app_logo')) {
            // Hapus logo lama jika ada
            $oldLogoSetting = Setting::where('key', 'app_logo')->first();
            if ($oldLogoSetting && $oldLogoSetting->value) {
                Storage::disk('public')->delete($oldLogoSetting->value);
            }

            // Simpan logo baru dan dapatkan path-nya
            $path = $request->file('app_logo')->store('logos', 'public');

            // Simpan path logo baru ke database
            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $path]
            );
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
