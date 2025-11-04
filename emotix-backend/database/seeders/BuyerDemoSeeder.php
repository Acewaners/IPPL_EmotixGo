<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BuyerDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name'     => 'buyer demo',
                'password' => Hash::make('buyer123'),
                'role'     => 'buyer',
            ]
        );
    }
}
