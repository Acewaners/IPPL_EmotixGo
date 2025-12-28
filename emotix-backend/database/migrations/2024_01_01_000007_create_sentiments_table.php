<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sentiments', function (Blueprint $table) {
            $table->id();
            // Relasi ke reviews.review_id (One to One)
            $table->foreignId('review_id')->constrained('reviews', 'review_id')->onDelete('cascade');
            
            $table->string('label')->nullable(); // Positif/Negatif
            $table->float('score')->nullable(); // Confidence score
            $table->json('details')->nullable(); // Raw response dari AI
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sentiments');
    }
};