<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_details', function (Blueprint $table) {

            $table->id('detail_id');

            $table->foreignId('transaction_id')->constrained('transactions', 'transaction_id')->onDelete('cascade');

            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');

            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void {
        Schema::dropIfExists('transaction_details');
    }
};
