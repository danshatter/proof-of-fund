<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Notification;
use App\Models\{Role, User};
use App\Exceptions\{UserUnregisteredException, UserAlreadyVerifiedException};
use App\Notifications\UserVerificationNotification;
use Tests\TestCase;

class ResendUserOtpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_resending_otp()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $response = $this->postJson(route('auth.resend-user-otp'), [
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
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $this->withoutExceptionHandling();
        $this->expectException(UserUnregisteredException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();

        $response = $this->postJson(route('auth.resend-user-otp'), [
            'phone' => fake()->e164PhoneNumber()
        ]);
    }

    /**
     * User already verified
     */
    public function test_user_already_verified()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $this->withoutExceptionHandling();
        $this->expectException(UserAlreadyVerifiedException::class);
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);

        $response = $this->postJson(route('auth.resend-user-otp'), [
            'phone' => $user->phone
        ]);
    }

    /**
     * OTP resent successfully
     */
    public function test_otp_is_resent_successfully()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        Notification::fake();
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone
                    ]);

        $response = $this->postJson(route('auth.resend-user-otp'), [
            'phone' => $user->phone
        ]);
        $user->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNotNull($user->verification);
        Notification::assertSentTimes(UserVerificationNotification::class, 1);
    }
}
