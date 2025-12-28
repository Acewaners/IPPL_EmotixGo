<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sentiments', function (Blueprint $table) {
            // Ubah nama kolom 'label' jadi 'category'
            // Pastikan install: composer require doctrine/dbal (jika error)
            // ATAU cara manual aman: Drop & Add

            if (Schema::hasColumn('sentiments', 'label')) {
                $table->renameColumn('label', 'category');
            } 
            // Jaga-jaga kalau kolom label belum ada tapi category juga belum ada
            elseif (!Schema::hasColumn('sentiments', 'category')) {
                $table->string('category')->nullable()->after('review_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sentiments', function (Blueprint $table) {
            if (Schema::hasColumn('sentiments', 'category')) {
                $table->renameColumn('category', 'label');
            }
        });
    }
};