<?php
// Jalankan: php artisan make:migration add_gps_settings_to_settings_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan baris data baru untuk pengaturan GPS.
     */
    public function up(): void
    {
        // Menambahkan pengaturan default untuk GPS
        DB::table('settings')->insert([
            ['key' => 'school_latitude', 'value' => '-0.897424'], // Contoh: Palu
            ['key' => 'school_longitude', 'value' => '119.873335'], // Contoh: Palu
            ['key' => 'attendance_radius', 'value' => '100'], // Default 100 meter
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'school_latitude', 'school_longitude', 'attendance_radius'
        ])->delete();
    }
};
