<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Level;
use Illuminate\Support\Facades\Schema; // <-- TAMBAHKAN INI

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nonaktifkan pengecekan foreign key sementara
        Schema::disableForeignKeyConstraints();

        // Hapus data lama dengan aman
        Level::truncate();

        // Aktifkan kembali pengecekan foreign key
        Schema::enableForeignKeyConstraints();

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
