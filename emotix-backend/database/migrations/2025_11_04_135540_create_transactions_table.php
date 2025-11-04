<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('transaction_id');     // INT UNSIGNED PK
            $table->unsignedInteger('buyer_id');      // FK -> users.user_id
            $table->unsignedInteger('seller_id');     // FK -> users.user_id
            $table->dateTime('transaction_date')->useCurrent();
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending_payment','processing','shipped','completed','failed'])
                  ->default('pending_payment');
            $table->string('tracking_number', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('buyer_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('seller_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};
