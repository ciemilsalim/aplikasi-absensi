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
    public function attendance(Request $request)
    {
        $settings = Setting::pluck('value', 'key');
        $selectedYear = $request->input('year', date('Y'));
        return view('admin.settings.attendance', compact('settings', 'selectedYear'));
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
                // Added validation for Headmaster
                'school_headmaster_name' => 'nullable|string|max:255',
                'school_headmaster_nip' => 'nullable|string|max:50',
            ];
            // Included new fields in the update list
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
        if ($request->has('form_type') && $request->form_type === 'attendance') {
            $rules = array_merge($rules, [
                'jam_masuk' => 'required|date_format:H:i',
                'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
                // Validation for Teacher Attendance Times
                'jam_masuk_guru' => 'required|date_format:H:i',
                'jam_pulang_guru' => 'required|date_format:H:i|after:jam_masuk_guru',
            ]);
            
            $attendanceKeys = ['jam_masuk', 'jam_pulang', 'jam_masuk_guru', 'jam_pulang_guru'];
            $selectedYear = $request->input('effective_year', date('Y'));
            for ($i = 1; $i <= 12; $i++) {
                $monthKey = 'effective_days_' . $selectedYear . '_' . $i;
                $rules[$monthKey] = 'nullable|integer|min:0|max:31';
                $attendanceKeys[] = $monthKey;
            }

            $settingsToUpdate = array_merge($settingsToUpdate, $request->only($attendanceKeys));
            // PERBAIKAN: Logika untuk menangani checkbox notifikasi alpa
            $settingsToUpdate['send_absent_notification'] = $request->has('send_absent_notification') ? 'on' : 'off';
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
            if ($oldLogoSetting && $oldLogoSetting->value) {
                Storage::disk('public')->delete($oldLogoSetting->value);
            }
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
