<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\Role;
use Tests\TestCase;

class AdminRegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_during_administrator_registration()
    {
        $response = $this->postJson(route('admin.auth.register'), [
            'first_name' => null
        ]);

        $response->assertInvalid(['first_name']);
        $response->assertUnprocessable();
    }

    /**
     * Administrator registration successful
     *
     * @return void
     */
    public function test_administrator_registration_was_successful()
    {
        Notification::fake();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $password = 'password';
        $email = fake()->email();

        $response = $this->postJson(route('admin.auth.register'), [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => $email,
            'phone' => fake()->e164PhoneNumber(),
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'role_id' => $adminRole->id,
            'email' => $email
        ]);
    }
}
