<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Application, Role, User};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetApplicationTransactionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Application not found
     */
    public function test_application_is_not_found()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.transactions', [
            'application' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Application transactions was successfully fetched
     */
    public function test_application_transactions_was_successfully_fetched()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create();
    
        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.transactions', [
            'application' => $application
        ]));

        $response->assertOk();
    }
}
