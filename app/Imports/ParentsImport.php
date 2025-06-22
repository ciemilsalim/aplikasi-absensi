<?php

namespace App\Imports;

use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rules;

class ParentsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // 1. Buat User baru dengan peran 'parent'
        $user = User::create([
            'name'     => $row['nama'],
            'email'    => $row['email'],
            'password' => Hash::make($row['password']),
            'role'     => 'parent',
        ]);

        // 2. Buat data ParentModel yang terhubung dengan user baru
        return new ParentModel([
            'user_id'       => $user->id,
            'name'          => $row['nama'],
            'phone_number'  => $row['nomor_hp'],
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
            'nomor_hp' => ['required', 'string', 'unique:parents,phone_number'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
