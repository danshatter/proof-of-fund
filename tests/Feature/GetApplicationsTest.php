<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Role, User};
use Tests\TestCase;

class GetApplicationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Applications were successfully fetched
     */
    public function test_applications_were_successfully_fetched()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.index'));

        $response->assertOk();
    }
}
