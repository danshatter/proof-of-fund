<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Camouflage, Role, User};
use App\Services\Auth\Otp as OtpService;
use App\Exceptions\{InvalidOtpException, BvnAlreadyVerifiedException, BvnVerificationLockedByFailedOtpException, OtpExpiredException, BvnLinkedToExistingAccountException};
use Tests\TestCase;

class VerifyBvnTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_verifying_bvn()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([            
                        'phone' => $phone
                    ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
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
    public function test_bvn_is_already_verified()
    {
        $this->withoutExceptionHandling();
        $this->expectException(BvnAlreadyVerifiedException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create([
                                    'phone' => $phone
                                ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
            'phone' => $camouflage->phone,
            'otp' => $otp
        ]);
    }

    /**
     * BVN linked to existing account
     */
    public function test_bvn_is_linked_to_an_existing_account()
    {
        $this->withoutExceptionHandling();
        $this->expectException(BvnLinkedToExistingAccountException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $phone1 = '+2348123456789';
        $phone2 = '+2348123456700';
        $user1 = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone1
                    ]);
        $user2 = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone2
                    ]);
        $bvn = '222222222222';
        $camouflage1 = Camouflage::factory()
                                ->for($user1)
                                ->create([
                                    'phone' => $phone2,
                                    'confidential' => $bvn,
                                    'confidential_hash' => hash_hmac('sha256', $bvn, config('japa.bvn_hash_secret'))
                                ]);
        $camouflage2 = Camouflage::factory()
                                ->for($user2)
                                ->verified()
                                ->create([
                                    'phone' => $phone2,
                                    'confidential' => $bvn,
                                    'confidential_hash' => hash_hmac('sha256', $bvn, config('japa.bvn_hash_secret'))
                                ]);
        
        $this->actingAs($user1, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
            'phone' => $phone2,
            'otp' => $otp
        ]);
    }

    /**
     * OTP has expired
     */
    public function test_bvn_verification_locked_due_to_failed_otp()
    {
        $this->withoutExceptionHandling();
        $this->expectException(BvnVerificationLockedByFailedOtpException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone,
                    ]);
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone,
                                    'locked_due_to_failed_verification_at' => now()
                                ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
            'phone' => $camouflage->phone,
            'otp' => $otp
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
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone,
                    ]);
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone,
                                    'verification' => $otp,
                                    'verification_expires_at' => now()->subHour()
                                ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
            'phone' => $camouflage->phone,
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
                    ->verified()
                    ->create([
                        'phone' => $phone,
                    ]);
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone,
                                    'verification' => $generatedOtp
                                ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
            'phone' => $camouflage->phone,
            'otp' => $otp
        ]);
    }

    /**
     * BVN verification successful
     */
    public function test_bvn_verification_was_successful()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $otp = app()->make(OtpService::class)->generate();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone,
                    ]);
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone,
                                    'verification' => $otp,
                                    'verification_expires_at' => now()->addHour()
                                ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->putJson(route('auth.verify-bvn'), [
            'phone' => $camouflage->phone,
            'otp' => $otp
        ]);
        $camouflage->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNotNull($camouflage->verified_at);
    }
}
