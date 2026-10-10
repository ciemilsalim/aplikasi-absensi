<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\SchoolClass;
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
        // Cari kelas berdasarkan ID atau nama kelas (jika diisi)
        $schoolClassId = null;
        if (!empty($row['kelas'])) {
            $class = SchoolClass::where('name', trim($row['kelas']))
                ->orWhere('id', trim($row['kelas']))
                ->first();
            if ($class) {
                $schoolClassId = $class->id;
            }
        } elseif (!empty($row['school_class_id'])) {
            $schoolClassId = $row['school_class_id'];
        }

        return new Student([
            'name'            => trim($row['nama']),
            'nis'             => (string) trim($row['nis']),
            'school_class_id' => $schoolClassId,
            'unique_id'       => (string) Str::uuid(),
        ]);
    }

    /**
     * Aturan validasi untuk setiap baris file Excel.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nis'  => ['required', 'unique:students,nis'],
        ];
    }
}
