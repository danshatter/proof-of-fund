<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetReferralCodeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Referral code fetched successfully
     */
    public function test_referral_code_was_successfully_fetched()
    {
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();
        
        $this->actingAs($individualAgent, 'sanctum');
        $response = $this->getJson(route('referral-code'));

        $response->assertOk();
    }
}
