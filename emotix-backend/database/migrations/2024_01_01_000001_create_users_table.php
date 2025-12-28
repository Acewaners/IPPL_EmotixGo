<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            // PENTING: Sesuai protected $primaryKey = 'user_id' di User.php
            $table->id('user_id'); 
            
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // PENTING: Sesuai AuthController yang set default 'buyer'
            $table->string('role')->default('buyer'); 
            
            // Sesuai $fillable di User.php
            $table->boolean('is_admin')->default(false); 
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};