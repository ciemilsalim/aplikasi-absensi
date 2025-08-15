<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder lain yang mungkin sudah ada...

        // Panggil LevelSeeder yang baru dibuat
        $this->call([
            LevelSeeder::class,
            // Anda bisa menambahkan seeder lain di sini
        ]);
    }
}