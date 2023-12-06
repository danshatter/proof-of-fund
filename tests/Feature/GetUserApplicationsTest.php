<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Camouflage, Role, User};
use Tests\TestCase;

class GetUserApplicationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Applications were successfully fetched
     */
    public function test_applications_were_successfully_fetched()
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
        $response = $this->getJson(route('applications.user-index'));

        $response->assertOk();
    }
}
