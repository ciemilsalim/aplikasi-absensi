<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolLocationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lokasi contoh (SMKN 1 Cibinong atau generik)
        $settings = [
            ['key' => 'school_latitude', 'value' => '-6.471947'],
            ['key' => 'school_longitude', 'value' => '106.840428'],
            ['key' => 'school_radius', 'value' => '100'], // Dalam meter
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(
            ['key' => $setting['key']],
            ['value' => $setting['value']]
            );
        }
    }
}
