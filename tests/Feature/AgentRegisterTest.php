<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\Role;
use App\Notifications\AgentVerificationNotification;
use Tests\TestCase;

class AgentRegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_during_user_registration()
    {
        $response = $this->postJson(route('auth.register-agent'), [
            'email' => null
        ]);

        $response->assertInvalid(['email']);
        $response->assertUnprocessable();
    }

    /**
     * Individual agent registration successful
     *
     * @return void
     */
    public function test_individual_agent_registration_was_successful()
    {
        Notification::fake();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $individualAgencyRole = Role::factory()
                                    ->individualAgent()
                                    ->create();
        $password = 'password';
        $email = fake()->email();

        $response = $this->postJson(route('auth.register-agent'), [
            'register_as' => 'INDIVIDUAL',
            'email' => $email,
            'phone' => fake()->e164PhoneNumber(),
            'address' => fake()->address(),
            'request_message' => fake()->paragraph(),
            'password' => $password,
            'password_confirmation' => $password,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date(),
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'role_id' => $individualAgencyRole->id,
            'email' => $email
        ]);
        $this->assertDatabaseCount('balances', 1);
        Notification::assertSentTimes(AgentVerificationNotification::class, 1);
    }

    /**
     * Agency agent registration successful
     *
     * @return void
     */
    public function test_agency_agent_registration_was_successful()
    {
        Notification::fake();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $password = 'password';
        $email = fake()->email();

        $response = $this->postJson(route('auth.register-agent'), [
            'register_as' => 'AGENCY',
            'email' => $email,
            'phone' => fake()->e164PhoneNumber(),
            'address' => fake()->address(),
            'request_message' => fake()->paragraph(),
            'password' => $password,
            'password_confirmation' => $password,
            'business_name' => fake()->company(),
            'business_website' => fake()->url(),
            'business_state' => fake()->city(),
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'role_id' => $agencyRole->id,
            'email' => $email
        ]);
        $this->assertDatabaseCount('balances', 1);
        Notification::assertSentTimes(AgentVerificationNotification::class, 1);
    }
}
