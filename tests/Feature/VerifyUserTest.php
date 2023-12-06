<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use App\Services\Auth\Otp as OtpService;
use App\Exceptions\{InvalidOtpException, OtpExpiredException, UserAlreadyVerifiedException, UserVerificationLockedByFailedOtpException};
use Tests\TestCase;

class VerifyUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_verifying_otp()
    {
        $response = $this->putJson(route('auth.verify-user'), [
            'phone' => null
        ]);

        $response->assertInvalid(['phone']);
        $response->assertUnprocessable();
    }

    /**
     * User already verified
     *
     * @return void
     */
    public function test_user_is_already_verified()
    {
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

        $response = $this->putJson(route('auth.verify-user'), [
            'phone' => $user->phone,
            'otp' => app()->make(OtpService::class)->generate()
        ]);
    }

    /**
     * User verification locked by failed OTP
     */
    public function test_user_verification_locked_due_to_failed_otp()
    {
        $this->withoutExceptionHandling();
        $this->expectException(UserVerificationLockedByFailedOtpException::class);
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $generatedOtp = '111111';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone,
                        'locked_due_to_failed_verification_at' => now()
                    ]);

        $response = $this->putJson(route('auth.verify-user'), [
            'phone' => $user->phone,
            'otp' => $generatedOtp
        ]);
    }

    /**
     * OTP has expired
     *
     * @return void
     */
    public function test_user_verification_otp_has_expired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(OtpExpiredException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $user = User::factory()
                    ->users()
                    ->create([
                        'verification' => $otp,
                        'verification_expires_at' => now()->subHour(),
                        'phone' => '+2348123456789'
                    ]);

        $response = $this->putJson(route('auth.verify-user'), [
            'phone' => $user->phone,
            'otp' => $otp
        ]);
    }

    /**
     * Invalid OTP
     */
    public function test_there_was_an_invalid_otp()
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvalidOtpException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $generatedOtp = '111111';
        $otp = '222222';
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone,
                        'verification' => $generatedOtp
                    ]);

        $response = $this->putJson(route('auth.verify-user'), [
            'phone' => $user->phone,
            'otp' => $otp
        ]);
    }

    /**
     * User verification successful
     */
    public function test_user_verification_was_successful()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => '+2348123456789',
                        'verification' => $otp,
                        'verification_expires_at' => now()->addHour()
                    ]);

        $response = $this->putJson(route('auth.verify-user'), [
            'phone' => $user->phone,
            'otp' => $otp
        ]);
        $user->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNull($user->verification);
        $this->assertNotNull($user->verified_at);
    }
}
