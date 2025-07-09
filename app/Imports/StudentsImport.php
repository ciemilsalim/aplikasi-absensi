<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Student([
            'name'      => $row['nama'],
            // PERBAIKAN: Mengubah NIS menjadi string untuk memastikan konsistensi
            'nis'       => (string) $row['nis'],
            'unique_id' => (string) Str::uuid(),
        ]);
    }

    /**
     * Tentukan aturan validasi untuk setiap baris di file Excel.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            // Memastikan validasi juga memeriksa sebagai string
            'nis'  => 'required|unique:students,nis',
        ];
    }
}
