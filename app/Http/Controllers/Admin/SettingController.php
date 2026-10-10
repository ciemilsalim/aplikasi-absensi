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
     * Menampilkan halaman pengaturan WhatsApp Gateway.
     */
    public function whatsapp()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.whatsapp', compact('settings'));
    }

    /**
     * Uji coba koneksi pengiriman pesan WhatsApp Gateway.
     */
    public function testWhatsApp(Request $request, \App\Services\Notification\WhatsAppNotificationService $service)
    {
        $request->validate([
            'test_phone' => 'required|string|min:8|max:25',
        ]);

        $result = $service->testConnection($request->input('test_phone'));

        if (!empty($result['success'])) {
            return back()->with('success', 'Uji coba berhasil! ' . ($result['message'] ?? 'Pesan berhasil dikirim via gateway.'));
        }

        return back()->with('error', 'Gagal mengirim pesan uji coba: ' . ($result['message'] ?? 'Terjadi kesalahan pada gateway.'));
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
                'school_city' => 'nullable|string|max:100',
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

            $settingsToUpdate = array_merge($settingsToUpdate, $request->only($attendanceKeys));
            // PERBAIKAN: Logika untuk menangani checkbox notifikasi alpa
            $settingsToUpdate['send_absent_notification'] = $request->has('send_absent_notification') ? 'on' : 'off';
        }

        // Validasi untuk form WhatsApp Gateway
        if ($request->has('form_type') && $request->form_type === 'whatsapp') {
            $rules = array_merge($rules, [
                'wa_gateway_provider' => 'required|string|in:fonnte,wablas,generic,log',
                'wa_gateway_token' => 'nullable|string|max:500',
                'wa_gateway_url' => 'nullable|url|max:500',
            ]);

            $settingsToUpdate['wa_gateway_provider'] = $request->input('wa_gateway_provider', 'log');
            $settingsToUpdate['wa_gateway_token'] = $request->input('wa_gateway_token', '');
            $settingsToUpdate['wa_gateway_url'] = $request->input('wa_gateway_url', '');
            $settingsToUpdate['wa_notify_clock_in'] = $request->has('wa_notify_clock_in') ? 'on' : 'off';
            $settingsToUpdate['wa_notify_clock_out'] = $request->has('wa_notify_clock_out') ? 'on' : 'off';
            $settingsToUpdate['wa_notify_absent'] = $request->has('wa_notify_absent') ? 'on' : 'off';
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
