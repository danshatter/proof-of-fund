<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetIncomingPaymentsCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Incoming payments count fetched successfully
     */
    public function test_incoming_payments_were_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('incoming-payments.count'));

        $response->assertOk();
    }
}
