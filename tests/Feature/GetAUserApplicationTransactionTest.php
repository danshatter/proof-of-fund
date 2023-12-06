<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Application, Role, User, Camouflage, Transaction};
use Tests\TestCase;

class GetAUserApplicationTransactionTest extends TestCase
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
        $response = $this->getJson(route('applications.user-transaction', [
            'applicationId' => 'non-existent-id',
            'transactionId' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Transaction not found
     */
    public function test_transaction_is_not_found()
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
        $response = $this->getJson(route('applications.user-transaction', [
            'applicationId' => $application->id,
            'transactionId' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Application transaction was successfully fetched
     */
    public function test_application_transaction_was_successfully_fetched()
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
        $transaction = Transaction::factory()
                                ->for($application)
                                ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson(route('applications.user-transaction', [
            'applicationId' => $application->id,
            'transactionId' => $transaction->id
        ]));

        $response->assertOk();
    }
}
