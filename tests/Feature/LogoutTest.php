<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Logout successful
     *
     * @return void
     */
    public function test_logout_was_successful()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.logout'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $response->assertOk();
    }
}
