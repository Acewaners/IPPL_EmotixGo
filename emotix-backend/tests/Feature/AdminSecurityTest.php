<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\AdminMiddleware;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware([AdminMiddleware::class])->get('/_test/admin', function () {
            return 'OK';
        });
    }

    public function test_non_admin_cannot_access_admin_route()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/_test/admin');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_route()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/_test/admin');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }
}
