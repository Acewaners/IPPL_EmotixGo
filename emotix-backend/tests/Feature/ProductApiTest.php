<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_products_json_structure()
    {
        // 1. Setup: Buat Data Pendukung
        $user = User::factory()->create();
        
        $category = Category::create(['name' => 'Elektronik']); 

        Product::create([
            'seller_id'    => $user->user_id,
            'category_id'  => $category->id,
            'product_name' => 'Laptop Gaming',
            'price'        => 15000000,
            'stock'        => 5,
            'description'  => 'Laptop kencang',
        ]);

        // 2. Act: Panggil API
        $response = $this->getJson('/api/products');

        // 3. Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'product_id',
                    'product_name',
                    'price',
                    'rating',
                    'rating_count'
                ]
            ]
        ]);
    }

    public function test_cannot_create_product_with_negative_price()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Elektronik']);

        $response = $this->actingAs($user)->postJson('/api/products', [
            'product_name' => 'Laptop Rusak',
            'price'        => -5000, //
            'stock'        => 10,
            'category_id'  => $category->id,
            'description'  => 'Tes harga minus'
        ]);

        $response->assertStatus(422);
        
        $response->assertJsonValidationErrors(['price']);
    }

}