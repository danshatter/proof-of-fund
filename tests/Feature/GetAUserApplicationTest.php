<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Application, Role, User, Camouflage};
use Tests\TestCase;

class GetAUserApplicationTest extends TestCase
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

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson(route('applications.user-show', [
            'applicationId' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Application was successfully fetched
     */
    public function test_application_was_successfully_fetched()
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

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson(route('applications.user-show', [
            'applicationId' => $application->id
        ]));

        $response->assertOk();
    }
}
