<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan disini tertulis 'transactions', BUKAN 'products'
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id'); // Primary Key
            
            // Relasi ke users (buyer & seller)
            $table->foreignId('buyer_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users', 'user_id')->onDelete('cascade');
            
            $table->date('transaction_date')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->string('status')->default('pending');
            $table->string('tracking_number')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};