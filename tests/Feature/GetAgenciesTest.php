<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Role, User};
use Tests\TestCase;

class GetAgenciesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Agencies were successfully fetched
     */
    public function test_agencies_were_successfully_fetched()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.individual-agents'));

        $response->assertOk();
    }
}
