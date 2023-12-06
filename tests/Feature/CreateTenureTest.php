<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Role, User};
use Tests\TestCase;

class CreateTenureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_creating_tenure()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->postJson(route('admin.tenures.store'), [
            'months' => null
        ]);

        $response->assertInvalid(['months']);
        $response->assertUnprocessable();
    }

    /**
     * Tenure was created successfully
     */
    public function test_tenure_was_successfully_created()
    {
        $months =  1;
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->postJson(route('admin.tenures.store'), [
            'months' => $months,
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('tenures', [
            'months' => $months,
        ]);
    }
}
