<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetReferralListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Referral list fetched successfully
     */
    public function test_referral_list_was_successfully_fetched()
    {
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();
        
        $this->actingAs($individualAgent, 'sanctum');
        $response = $this->getJson(route('referral-list'));

        $response->assertOk();
    }
}
