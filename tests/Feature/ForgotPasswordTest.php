<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use App\Models\{Role, User};
use App\Exceptions\UserUnregisteredException;
use App\Notifications\ResetPasswordNotification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_initiating_forgot_otp()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $response = $this->postJson(route('auth.forgot-password'), [
            'phone' => null
        ]);

        $response->assertInvalid(['phone']);
        $response->assertUnprocessable();
    }

    /**
     * Non-existent user
     *
     * @return void
     */
    public function test_user_does_not_exist()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $this->expectException(UserUnregisteredException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();

        $response = $this->postJson(route('auth.forgot-password'), [
            'phone' => fake()->e164PhoneNumber()
        ]);
    }

    /**
     * Forgot password initiated successfully
     */
    public function test_forgot_password_is_initiated_successfully()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        Notification::fake();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone
                    ]);

        $response = $this->postJson(route('auth.forgot-password'), [
            'phone' => $user->phone
        ]);
        $user->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNotNull($user->verification);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
}
