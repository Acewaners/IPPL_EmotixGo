<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    // Skenario 1: Tamu (Guest) mencoba akses halaman akun
    // Tujuan: Memastikan Middleware 'auth:sanctum' melindungi route
    public function test_guest_cannot_access_protected_account_page()
    {
        // Act: Coba akses endpoint profil (/api/me) tanpa token
        $response = $this->getJson('/api/me');

        // Assert: Harus ditolak keras (401 Unauthorized)
        // Jika return 200 (walau data kosong) atau 500, berarti Middleware GAGAL.
        $response->assertStatus(401);
    }

    // Skenario 2: Akses halaman setelah Logout (Isu yang ditemukan Blackbox)
    // Tujuan: Memastikan Token benar-benar dihancurkan di database saat logout
    public function test_token_is_invalidated_immediately_after_logout()
    {
        // 1. Setup: User Login & dapat Token
        $user = User::factory()->create();
        
        // Kita simulasikan login manual untuk dapat token string-nya
        $token = $user->createToken('test-token')->plainTextToken;

        // Cek dulu: Token ini valid
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
             ->getJson('/api/me')
             ->assertStatus(200);

        // 2. Act: User melakukan LOGOUT
        // Endpoint ini memanggil $req->user()->currentAccessToken()->delete();
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
             ->postJson('/api/logout')
             ->assertStatus(200);

        // 3. Assert Critical:
        // Coba akses lagi pakai token yang sama.
        // EKSPEKTASI: Backend harus menolak (401).
        // JIKA FAIL (200): Berarti token tidak terhapus, ini celah keamanan besar!
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
             ->getJson('/api/me');

        $response->assertStatus(401);
    }
}