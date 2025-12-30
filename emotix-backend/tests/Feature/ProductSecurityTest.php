<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_cannot_update_other_sellers_product()
    {
        // 1. Setup
        $sellerA = User::factory()->create(); // Pemilik Asli
        $sellerB = User::factory()->create(); // Orang Iseng
        $category = Category::create(['name' => 'Elektronik']);

        $product = Product::create([
            'seller_id' => $sellerA->user_id, // Punya Si A
            'category_id' => $category->id,
            'product_name' => 'Laptop Mahal',
            'price' => 10000000,
            'stock' => 10
        ]);

        // 2. Act: Si B mencoba mengganti harga produk Si A jadi murah
        $response = $this->actingAs($sellerB)->putJson("/api/products/{$product->product_id}", [
            'product_name' => 'Laptop Mahal',
            'price' => 500, // Diubah jadi 500 perak
            'stock' => 10,
            'category_id' => $category->id
        ]);

        // 3. Assert:
        // EKSPEKTASI KITA: Sistem menolak (403 Forbidden)
        // REALITA (Mungkin): Sistem mengizinkan (200 OK) -> BUG!
        $response->assertStatus(403);
    }
    
    public function test_seller_cannot_delete_other_sellers_product()
    {
        // 1. Setup
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $category = Category::create(['name' => 'Elektronik']);

        $product = Product::create([
            'seller_id' => $sellerA->user_id,
            'category_id' => $category->id,
            'product_name' => 'Laptop Penting',
            'price' => 10000000,
            'stock' => 1
        ]);

        // 2. Act: Si B mencoba menghapus produk Si A
        $response = $this->actingAs($sellerB)->deleteJson("/api/products/{$product->product_id}");

        // 3. Assert: Harus Ditolak (403)
        $response->assertStatus(403);
        
        // Barang harusnya MASIH ADA di database
        $this->assertDatabaseHas('products', ['product_id' => $product->product_id]);
    }
}