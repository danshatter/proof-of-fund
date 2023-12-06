<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetAccountsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Accounts fetched successfully
     */
    public function test_accounts_were_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('accounts.user-index'));

        $response->assertOk();
    }
}
