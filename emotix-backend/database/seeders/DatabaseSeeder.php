<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,   // Membuat Super Admin
            CategorySeeder::class,    // Membuat Kategori Produk
            SellerSipaSeeder::class,  // Akun Seller Spesifik
            SellerSeeder::class,      // Akun-akun Seller lainnya
            BuyerDemoSeeder::class,   // Akun-akun Buyer untuk demo
        ]);
    }
}