<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_failed_if_stock_is_not_enough()
    {
        // 1. Setup:
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $category = Category::create(['name' => 'Gadget']);
        
        $product = Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->id,
            'product_name' => 'HP Murah',
            'price' => 1000000,
            'stock' => 2,
            'description' => 'Tes stok'
        ]);

        // 2. Act:
        $response = $this->actingAs($buyer)->postJson('/api/transactions', [
            'seller_id' => $seller->user_id,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'quantity' => 5 
                ]
            ]
        ]);

        // 3. Assert:
        $response->assertStatus(422);
        
        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'stock' => 2
        ]);
    }
    public function test_stock_is_rolled_back_if_one_item_fails_in_bulk_order()
    {
        // 1. Setup
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $category = Category::create(['name' => 'Campur']);

        // Produk A: Stok Banyak (Aman)
        $productA = Product::create([
            'seller_id' => $seller->user_id, 'category_id' => $category->id,
            'product_name' => 'Barang Aman', 'price' => 50000, 'stock' => 100 
        ]);

        // Produk B: Stok Sedikit (Akan Gagal)
        $productB = Product::create([
            'seller_id' => $seller->user_id, 'category_id' => $category->id,
            'product_name' => 'Barang Langka', 'price' => 50000, 'stock' => 2
        ]);

        // 2. Act: Beli keduanya sekaligus
        $response = $this->actingAs($buyer)->postJson('/api/transactions', [
            'seller_id' => $seller->user_id,
            'items' => [
                ['product_id' => $productA->product_id, 'quantity' => 5],
                ['product_id' => $productB->product_id, 'quantity' => 10] // <--- Penyebab Gagal
            ]
        ]);

        // 3. Assert
        $response->assertStatus(422);

        $this->assertDatabaseHas('products', [
            'product_id' => $productA->product_id,
            'stock' => 100 
        ]);
    }
}