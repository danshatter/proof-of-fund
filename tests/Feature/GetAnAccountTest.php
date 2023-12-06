<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Account, Role, User};
use Tests\TestCase;

class GetAnAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Account not found
     */
    public function test_account_not_found()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('accounts.user-show', [
            'accountId' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Account fetched successfully
     */
    public function test_account_were_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        $account = Account::factory()
                        ->for($agency)
                        ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('accounts.user-show', [
            'accountId' => $account->id
        ]));

        $response->assertOk();
    }
}
