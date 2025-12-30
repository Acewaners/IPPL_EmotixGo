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
            ['email' => 'dhio@example.com'],
            [
                'name'     => 'dhio',
                'password' => Hash::make('dhio123'), 
                'role'     => 'seller',
            ]
        );
    }
}
