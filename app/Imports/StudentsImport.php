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
            'nis'       => $row['nis'],
            'unique_id' => (string) Str::uuid(), // Otomatis buat ID unik
        ]);
    }

    /**
     * Tentukan aturan validasi untuk setiap baris.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'nis' => 'required|string|unique:students,nis',
        ];
    }

    /**
     * Pesan kustom untuk validasi.
     *
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nama.required' => 'Kolom nama tidak boleh kosong.',
            'nis.required' => 'Kolom NIS tidak boleh kosong.',
            'nis.unique' => 'NIS :input sudah terdaftar.',
        ];
    }
}
