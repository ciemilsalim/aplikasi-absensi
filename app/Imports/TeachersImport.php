<?php

namespace App\Imports;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rules;

class TeachersImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // 1. Buat User baru dengan peran 'teacher'
        $user = User::create([
            'name'     => $row['nama'],
            'email'    => $row['email'],
            'password' => Hash::make($row['password']),
            'role'     => 'teacher',
        ]);

        // 2. Buat data Teacher yang terhubung dengan user baru
        return new Teacher([
            'user_id'       => $user->id,
            'name'          => $row['nama'],
            'nip'           => $row['nip'] ?? null,
            'phone_number'  => $row['nomor_hp'] ?? null,
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
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            
            // PERBAIKAN: Menghapus validasi 'string' agar lebih fleksibel
            // terhadap format angka dari Excel.
            'nip' => ['nullable', 'unique:teachers,nip'],
            'nomor_hp' => ['nullable'],
        ];
    }
}
