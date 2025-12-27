<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Kita ubah kolom rating agar boleh kosong (null)
            $table->integer('rating')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Kembalikan ke wajib isi jika rollback
            $table->integer('rating')->nullable(false)->change();
        });
    }
};
