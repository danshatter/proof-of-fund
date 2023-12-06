<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Role, User};
use App\Exceptions\{InvalidOtpException, OtpExpiredException, PasswordResetLockedByFailedOtpException};
use App\Services\Auth\Otp as OtpService;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_resetting_password()
    {
        $response = $this->putJson(route('auth.reset-password'), [
            'phone' => null
        ]);

        $response->assertInvalid(['phone']);
        $response->assertUnprocessable();
    }

    /**
     * Password reset locked by failed OTP
     */
    public function test_password_reset_locked_due_to_failed_otp()
    {
        $this->withoutExceptionHandling();
        $this->expectException(PasswordResetLockedByFailedOtpException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $generatedOtp = '111111';
        $password = 'password';
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone,
                        'locked_due_to_failed_verification_at' => now()
                    ]);

        $response = $this->putJson(route('auth.reset-password'), [
            'phone' => $user->phone,
            'password' => $password,
            'password_confirmation' => $password,
            'otp' => $generatedOtp
        ]);
    }

    /**
     * OTP has expired
     */
    public function test_otp_has_expired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(OtpExpiredException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $password = 'password';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => '+2348123456789',
                        'password' => $password,
                        'verification' => $otp,
                        'verification_expires_at' => now()->subHour()
                    ]);

        $response = $this->putJson(route('auth.reset-password'), [
            'phone' => $user->phone,
            'password' => $password,
            'password_confirmation' => $password,
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
        $password = 'password';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => '+2348123456789',
                        'password' => $password,
                        'verification' => $generatedOtp
                    ]);

        $response = $this->putJson(route('auth.reset-password'), [
            'phone' => $user->phone,
            'password' => $password,
            'password_confirmation' => $password,
            'otp' => $otp
        ]);
    }

    /**
     * Password reset successfully
     */
    public function test_password_is_reset_successfully()
    {
        $generatedOtp = app()->make(OtpService::class)->generate();
        $password = 'password';
        $newPassword = 'newpassword';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => '+2348123456789',
                        'password' => $password,
                        'verification' => $generatedOtp
                    ]);
        $oldHashedPassword = $user->password;

        $response = $this->putJson(route('auth.reset-password'), [
            'phone' => $user->phone,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'otp' => $generatedOtp
        ]);
        $user->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNotSame($user->password, $oldHashedPassword);
    }
}
