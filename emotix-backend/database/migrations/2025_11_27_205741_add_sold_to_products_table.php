<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CEK DULU: Hanya jalankan jika kolom 'sold' BELUM ada
        if (!Schema::hasColumn('products', 'sold')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('sold')->default(0)->after('stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'sold')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('sold');
            });
        }
    }
};