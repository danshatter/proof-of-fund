<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Application, Role, User, Transaction};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetApplicationTransactionTest extends TestCase
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
        $response = $this->getJson(route('admin.applications.transaction', [
            'application' => 'non-existent-id',
            'transaction' => 'non-existent-id'
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
        $response = $this->getJson(route('admin.applications.transaction', [
            'application' => $application,
            'transaction' => 'non-existent-id'
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
        $transaction = Transaction::factory()
                                ->for($application)
                                ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.transaction', [
            'application' => $application,
            'transaction' => $transaction
        ]));

        $response->assertOk();
    }
}
