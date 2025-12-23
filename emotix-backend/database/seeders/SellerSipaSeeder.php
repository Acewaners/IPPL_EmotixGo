<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SellerSipaSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ujang@example.com'],
            [
                'name'     => 'ujang',
                'password' => Hash::make('ujang123'), // ganti sesuai mau
                'role'     => 'seller',
            ]
        );
    }
}
