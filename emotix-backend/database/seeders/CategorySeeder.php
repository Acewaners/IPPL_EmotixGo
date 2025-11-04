<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Handphone', 'Laptop', 'Tablet'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
