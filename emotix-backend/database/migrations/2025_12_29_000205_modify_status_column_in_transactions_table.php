<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Kita ubah ke string biasa agar fleksibel menerima status apapun 
            // yang sudah kita definisikan di Controller & Frontend
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Jika ingin kembali ke batasan lama (opsional)
            // $table->enum('status', ['pending_payment', 'completed', 'failed'])->change();
        });
    }
};