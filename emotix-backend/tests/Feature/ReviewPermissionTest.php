<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_review_if_not_purchased()
    {
        // 1. Setup
        $seller = User::factory()->create();
        $buyer = User::factory()->create(); // Buyer ini belum beli apa-apa
        $category = Category::create(['name' => 'Elektronik']);
        
        $product = Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->id,
            'product_name' => 'Laptop',
            'price' => 5000000,
            'stock' => 10
        ]);

        // 2. Act: Buyer mencoba memberi review tanpa beli
        $response = $this->actingAs($buyer)->postJson('/api/reviews', [
            'product_id' => $product->product_id,
            'review_text' => 'Barang bagus gan!',
            'rating' => 5
        ]);

        // 3. Assert: Harus Ditolak (403 Forbidden)
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Anda harus membeli produk ini dan menyelesaikan pesanan (status Completed) sebelum memberikan ulasan.']);
    }

    public function test_user_can_review_only_after_transaction_completed()
    {
        // 1. Setup: Buyer SUDAH beli, tapi status masih 'processing'
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $category = Category::create(['name' => 'Elektronik']);
        $product = Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->id,
            'product_name' => 'Kamera',
            'price' => 2000000,
            'stock' => 5
        ]);

        $trx = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'total_price' => 2000000,
            'status' => 'processing',
            'transaction_date' => now()
        ]);

        TransactionDetail::create([
            'transaction_id' => $trx->transaction_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'subtotal' => 2000000
        ]);

        // 2. Act: Coba review saat status processing
        $response = $this->actingAs($buyer)->postJson('/api/reviews', [
            'product_id' => $product->product_id,
            'review_text' => 'Barang belum sampai tapi mau review',
            'rating' => 1
        ]);

        $response->assertStatus(403);

        $trx->update(['status' => 'completed']);

        $responseSuccess = $this->actingAs($buyer)->postJson('/api/reviews', [
            'product_id' => $product->product_id,
            'review_text' => 'Barang sudah sampai, mantap!',
            'rating' => 5
        ]);

        $responseSuccess->assertStatus(201);
    }
}