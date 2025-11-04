<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = [
            [
                'name'  => 'sipa',
                'email' => 'sipa@example.com',
                'password' => 'Password123!',
            ],
            [
                'name'  => 'seller demo',
                'email' => 'seller@example.com',
                'password' => 'Password123!',
            ],
            // tambahkan lagi kalau perlu…
        ];

        foreach ($sellers as $s) {
            User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name'     => $s['name'],
                    'password' => Hash::make($s['password']),
                    'role'     => 'seller',
                ]
            );
        }
    }
}
