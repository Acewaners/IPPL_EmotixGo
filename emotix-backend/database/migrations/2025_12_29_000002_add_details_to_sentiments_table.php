<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sentiments', function (Blueprint $table) {
            // Tambah kolom model_version jika belum ada
            if (!Schema::hasColumn('sentiments', 'model_version')) {
                $table->string('model_version')->nullable()->after('category');
            }
            
            // Tambah kolom analyzed_at jika belum ada
            if (!Schema::hasColumn('sentiments', 'analyzed_at')) {
                $table->timestamp('analyzed_at')->nullable()->after('model_version');
            }

            // Opsional: Rename id jadi sentiment_id agar cocok dengan Model (jika masih id)
            // (Hanya jika DB support rename column tanpa error dependency)
            if (Schema::hasColumn('sentiments', 'id') && !Schema::hasColumn('sentiments', 'sentiment_id')) {
                $table->renameColumn('id', 'sentiment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sentiments', function (Blueprint $table) {
            if (Schema::hasColumn('sentiments', 'model_version')) {
                $table->dropColumn(['model_version', 'analyzed_at']);
            }
            if (Schema::hasColumn('sentiments', 'sentiment_id')) {
                $table->renameColumn('sentiment_id', 'id');
            }
        });
    }
};