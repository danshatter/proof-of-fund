<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetPendingApplicationsCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pending applications count fetched successfully
     */
    public function test_pending_applications_count_were_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('pending-applications.count'));

        $response->assertOk();
    }
}
