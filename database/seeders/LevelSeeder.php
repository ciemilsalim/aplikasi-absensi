<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Level; // <-- Jangan lupa import model Level

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama untuk menghindari duplikasi saat seeding ulang
        Level::truncate();

        // Data tingkat kelas yang akan dimasukkan
        $levels = [
            ['name' => 'Kelas 7'],
            ['name' => 'Kelas 8'],
            ['name' => 'Kelas 9'],
            ['name' => 'Kelas 10'],
            ['name' => 'Kelas 11'],
            ['name' => 'Kelas 12'],
        ];

        // Masukkan data ke dalam tabel menggunakan model Level
        foreach ($levels as $level) {
            Level::create($level);
        }
    }
}