<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');

            $table->foreignId('seller_id')->constrained('users', 'user_id')->onDelete('cascade');

            $table->foreignId('category_id')->constrained('categories', 'id')->onDelete('cascade');

            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock');
            $table->string('image')->nullable();
            $table->integer('sold')->default(0); 

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
