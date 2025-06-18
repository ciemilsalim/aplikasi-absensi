<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Str; // 1. Tambahkan baris ini untuk menggunakan class Str

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2. Modifikasi: Buat unique_id secara eksplisit saat membuat data siswa
        Student::create([
            'name' => 'Budi Santoso', 
            'nis' => '1001',
            'unique_id' => (string) Str::uuid()
        ]);
        Student::create([
            'name' => 'Citra Lestari', 
            'nis' => '1002',
            'unique_id' => (string) Str::uuid()
        ]);
        Student::create([
            'name' => 'Dewi Anggraini', 
            'nis' => '1003',
            'unique_id' => (string) Str::uuid()
        ]);
        Student::create([
            'name' => 'Eko Prasetyo', 
            'nis' => '1004',
            'unique_id' => (string) Str::uuid()
        ]);
        Student::create([
            'name' => 'Fitriani', 
            'nis' => '1005',
            'unique_id' => (string) Str::uuid()
        ]);
    }
}
// Jangan lupa panggil seeder ini di DatabaseSeeder.php
// $this->call(StudentSeeder::class);
