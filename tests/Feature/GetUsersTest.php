<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Role, User};
use Tests\TestCase;

class GetUsersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Users were successfully fetched
     */
    public function test_users_were_successfully_fetched()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.users'));

        $response->assertOk();
    }
}
