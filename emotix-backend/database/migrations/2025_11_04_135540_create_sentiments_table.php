<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sentiments', function (Blueprint $table) {
            $table->increments('sentiment_id');     // INT UNSIGNED PK
            $table->unsignedInteger('review_id');   // FK -> reviews.review_id
            $table->enum('category', ['positive','neutral','negative']);
            $table->string('model_version', 50)->nullable();
            $table->dateTime('analyzed_at')->useCurrent();

            $table->foreign('review_id')->references('review_id')->on('reviews')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sentiments');
    }
};
