<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetBalancesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Balance fetched successfully
     */
    public function test_balance_was_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('balances.index'));

        $response->assertOk();
    }
}
