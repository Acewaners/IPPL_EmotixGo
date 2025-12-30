<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_cannot_mark_transaction_as_shipped()
    {
        // 1. Setup
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        
        $trx = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'total_price' => 100000,
            'status' => 'processing',
            'transaction_date' => now()
        ]);

        // 2. Act: GANTI putJson MENJADI postJson
        $response = $this->actingAs($buyer)->postJson("/api/transactions/{$trx->transaction_id}/status", [
            'status' => 'shipped', 
            'tracking_number' => 'FAKE123'
        ]);

        // 3. Assert
        $response->assertStatus(403);
    }

    public function test_seller_can_mark_transaction_as_shipped()
    {
        // 1. Setup
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        
        $trx = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'total_price' => 100000,
            'status' => 'processing',
            'transaction_date' => now()
        ]);

        // 2. Act: GANTI putJson MENJADI postJson
        $response = $this->actingAs($seller)->postJson("/api/transactions/{$trx->transaction_id}/status", [
            'status' => 'shipped',
            'tracking_number' => 'JNE123456'
        ]);

        // 3. Assert
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => $trx->transaction_id,
            'status' => 'shipped',
            'tracking_number' => 'JNE123456'
        ]);
    }

    public function test_buyer_cannot_complete_cancelled_order()
    {
        // 1. Setup: Transaksi yang sudah DIBATALKAN
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        
        $trx = Transaction::create([
            'buyer_id' => $buyer->user_id, 'seller_id' => $seller->user_id,
            'total_price' => 50000, 
            'status' => 'cancelled', // <--- Sudah Batal
            'transaction_date' => now()
        ]);

        // 2. Act: Buyer mencoba "Menyelesaikan" pesanan batal tersebut
        $response = $this->actingAs($buyer)->postJson("/api/transactions/{$trx->transaction_id}/status", [
            'status' => 'completed'
        ]);

        // 3. Assert
        $response->assertStatus(403); 
    }
}