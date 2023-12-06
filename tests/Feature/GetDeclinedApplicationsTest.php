<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use Tests\TestCase;

class GetDeclinedApplicationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Declined applications fetched successfully
     */
    public function test_declined_applications_were_successfully_fetched()
    {
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->emailVerified()
                    ->create();
        
        $this->actingAs($agency, 'sanctum');
        $response = $this->getJson(route('declined-applications.index'));

        $response->assertOk();
    }
}
