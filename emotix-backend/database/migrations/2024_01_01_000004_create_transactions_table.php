<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            // Sesuai protected $primaryKey = 'product_id'
            $table->id('product_id');
            
            // Relasi ke users.user_id (Seller)
            $table->foreignId('seller_id')->constrained('users', 'user_id')->onDelete('cascade');
            
            // Relasi ke categories.id
            $table->foreignId('category_id')->constrained('categories', 'id')->onDelete('cascade');
            
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock');
            $table->string('image')->nullable();
            $table->integer('sold')->default(0); // Kolom ini sering Anda update via migrasi lain, lebih baik ada dari awal.
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};