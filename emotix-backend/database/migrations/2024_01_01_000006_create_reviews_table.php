<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reviews', function (Blueprint $table) {

            $table->id('review_id');

            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');

            $table->foreignId('buyer_id')->constrained('users', 'user_id')->onDelete('cascade');

            $table->text('review_text')->nullable();
            $table->integer('rating')->nullable(); 
            $table->string('sentiment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reviews');
    }
};
