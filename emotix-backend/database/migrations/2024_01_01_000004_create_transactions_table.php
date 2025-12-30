<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');

            $table->foreignId('buyer_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users', 'user_id')->onDelete('cascade');

            $table->dateTime('transaction_date')->nullable();
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
