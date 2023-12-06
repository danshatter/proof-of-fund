<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\{Activity, Role, User};
use App\Notifications\UserVerificationNotification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_during_user_registration()
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => null
        ]);

        $response->assertInvalid(['first_name']);
        $response->assertUnprocessable();
    }

    /**
     * User registration successful
     *
     * @return void
     */
    public function test_user_registration_was_successful()
    {
        Notification::fake();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $agent = User::factory()
                    ->individualAgents()
                    ->create();
        $referralCode = $agent->generateReferralCode();
        $password = 'password';
        $email = fake()->email();

        $response = $this->postJson(route('auth.register'), [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => $email,
            'phone' => fake()->e164PhoneNumber(),
            'date_of_birth' => fake()->date(),
            'password' => $password,
            'password_confirmation' => $password,
            'referral_code' => $referralCode
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'role_id' => $userRole->id,
            'email' => $email,
            'referred_by' => $agent->id
        ]);
        $this->assertDatabaseHas('activities', [
            'user_id' => $agent->id,
            'type' => Activity::SIGN_UP
        ]);
        Notification::assertSentTimes(UserVerificationNotification::class, 1);
    }
}
