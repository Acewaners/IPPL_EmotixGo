<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sentiments', function (Blueprint $table) {
            if (Schema::hasColumn('sentiments', 'label')) {
                $table->renameColumn('label', 'category');
            }
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
