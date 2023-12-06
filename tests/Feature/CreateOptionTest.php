<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Role, User};
use Tests\TestCase;

class CreateOptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_creating_option()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->postJson(route('admin.options.store'), [
            'type' => null
        ]);

        $response->assertInvalid(['type']);
        $response->assertUnprocessable();
    }

    /**
     * Option was created successfully
     */
    public function test_option_was_successfully_created()
    {
        $type = fake()->sentence();
        $interest = 2.6;
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->postJson(route('admin.options.store'), [
            'type' => $type,
            'interest' => $interest
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('options', [
            'type' => $type,
            'interest' => $interest
        ]);
    }
}
