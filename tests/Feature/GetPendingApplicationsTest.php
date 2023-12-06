<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetPendingApplicationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pending applications fetched successfully
     */
    public function test_pending_applications_were_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('pending-applications.index'));

        $response->assertOk();
    }
}
