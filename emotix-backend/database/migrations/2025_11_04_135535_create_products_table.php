<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Sesuai Model Product.php: protected $primaryKey = 'product_id';
            $table->id('product_id');

            // Foreign Keys
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('category_id');

            // Data Produk
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock')->default(0);
            $table->integer('sold')->default(0);
            $table->string('image')->nullable();

            $table->timestamps();

            // Constraints (Relasi)
            // Pastikan tabel users dan categories sudah ada sebelum file ini berjalan
            $table->foreign('seller_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
