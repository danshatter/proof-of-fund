<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Application, Camouflage, Role, User};
use Tests\TestCase;

class UpdateApplicationStatusTest extends TestCase
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
        $response = $this->putJson(route('admin.applications.status', [
            'application' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_updating_application_status()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
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

        Sanctum::actingAs($admin, ['*']);
        $response = $this->putJson(route('admin.applications.status', [
            'application' => $application
        ]), [
            'status' => null
        ]);

        $response->assertInvalid(['status']);
        $response->assertUnprocessable();
    }

    /**
     * Application status successfully updated
     */
    public function test_application_status_was_successfully_updated()
    {
        $oldStatus = Application::PENDING;
        $newStatus = Application::OPEN;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
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
                                ->create([
                                    'status' => $oldStatus
                                ]);
        

        Sanctum::actingAs($admin, ['*']);
        $response = $this->putJson(route('admin.applications.status', [
            'application' => $application
        ]), [
            'status' => $newStatus
        ]);
        $application->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertSame($newStatus, $application->status);
    }
}
