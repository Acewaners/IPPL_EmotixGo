<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthNegativeTest extends TestCase
{
    use RefreshDatabase;

    // 1. Tes Register: Email Kembar
    public function test_user_cannot_register_with_existing_email()
    {
        // Setup: Buat user A
        User::factory()->create([
            'email' => 'kembar@example.com'
        ]);

        // Act: User B coba daftar pakai email yang sama
        $response = $this->postJson('/api/register', [
            'name' => 'User B',
            'email' => 'kembar@example.com', // <--- Email Duplikat
            'password' => 'password123'
        ]);

        // Assert: Harus gagal (422 Unprocessable Entity)
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // 2. Tes Login: Password Salah
    public function test_user_cannot_login_with_wrong_password()
    {
        // Setup
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password_benar')
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password_salah' // <--- Salah
        ]);

        // Assert: Harus gagal (401 Unauthorized)
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized access']);
    }

    // 3. Tes Update Profil: Ganti email ke email orang lain
    public function test_user_cannot_update_email_to_already_taken_email()
    {
        // Setup: User A (Korban) dan User B (Pelaku)
        User::factory()->create(['email' => 'korban@example.com']);
        $pelaku = User::factory()->create(['email' => 'pelaku@example.com']);

        // Act: Pelaku mencoba ganti emailnya jadi email si Korban
        $response = $this->actingAs($pelaku)->putJson('/api/user/update-profile', [
            'name' => 'Pelaku Edit',
            'email' => 'korban@example.com' 
        ]);

        // Assert: Harus gagal (422) karena validasi 'unique'
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // 4. Tes Ganti Password: Password Lama Salah
    public function test_user_cannot_change_password_if_current_password_wrong()
    {
        // Setup
        $user = User::factory()->create([
            'password' => Hash::make('rahasia123')
        ]);

        // Act
        $response = $this->actingAs($user)->putJson('/api/user/update-password', [
            'current_password' => 'ngawur',
            'new_password' => 'baru12345',
            'new_password_confirmation' => 'baru12345'
        ]);

        // Assert: Harus gagal (422)
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'The password is incorrect.']);
    }
}