<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('review_id');        // INT UNSIGNED PK
            $table->unsignedInteger('product_id');  // FK -> products.product_id (INT UNSIGNED)
            $table->unsignedInteger('buyer_id');    // FK -> users.user_id (INT UNSIGNED)
            $table->text('review_text');
            $table->enum('sentiment', ['positive','negative','neutral'])->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->foreign('buyer_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reviews');
    }
};
