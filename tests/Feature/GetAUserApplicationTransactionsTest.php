<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Application, Role, User, Camouflage};
use Tests\TestCase;

class GetAUserApplicationTransactionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Application not found
     */
    public function test_application_is_not_found()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson(route('applications.user-transactions', [
            'applicationId' => 'non-existent-id'
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
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson(route('applications.user-transactions', [
            'applicationId' => $application->id
        ]));

        $response->assertOk();
    }
}
