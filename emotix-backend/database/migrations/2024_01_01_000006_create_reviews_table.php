<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reviews', function (Blueprint $table) {
            // Sesuai protected $primaryKey = 'review_id'
            $table->id('review_id');
            
            // Relasi ke products.product_id
            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
            
            // Relasi ke users.user_id (Buyer)
            $table->foreignId('buyer_id')->constrained('users', 'user_id')->onDelete('cascade');
            
            $table->text('review_text');
            $table->integer('rating')->nullable(); // Set nullable langsung biar aman
            $table->string('sentiment')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reviews');
    }
};