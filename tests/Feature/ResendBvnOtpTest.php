<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use App\Models\{Camouflage, Role, User};
use App\Exceptions\{BvnAlreadyVerifiedException, BvnLinkedToExistingAccountException, BvnNotTiedToAccountException};
use App\Notifications\BvnVerificationNotification;
use Tests\TestCase;

class ResendBvnOtpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_resending_bvn_otp()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
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
        $response = $this->postJson(route('auth.resend-bvn-otp'), [
            'bvn' => null
        ]);

        $response->assertInvalid(['bvn']);
        $response->assertUnprocessable();
    }

    /**
     * BVN not tied to account
     *
     * @return void
     */
    public function test_bvn_is_not_tied_to_account()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $this->withoutExceptionHandling();
        $this->expectException(BvnNotTiedToAccountException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $bvn = '11111111111';
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([            
                        'phone' => $phone
                    ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.resend-bvn-otp'), [
            'bvn' => $bvn
        ]);
    }

    /**
     * BVN already verified
     */
    public function test_bvn_has_already_been_verified()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $this->withoutExceptionHandling();
        $this->expectException(BvnAlreadyVerifiedException::class);
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
        $bvn = '11111111111';
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create([
                                    'confidential' => $bvn,
                                    'phone' => $phone
                                ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.resend-bvn-otp'), [
            'bvn' => $bvn
        ]);
    }

    /**
     * BVN linked to existing account
     */
    public function test_bvn_is_linked_to_an_existing_account()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        $this->withoutExceptionHandling();
        $this->expectException(BvnLinkedToExistingAccountException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
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
                                    'phone' => $phone1,
                                    'confidential' => $bvn,
                                ]);
        $camouflage2 = Camouflage::factory()
                                ->for($user2)
                                ->verified()
                                ->create([
                                    'phone' => $phone1,
                                    'confidential' => $bvn,
                                ]);
        
        $this->actingAs($user1, 'sanctum');
        $response = $this->postJson(route('auth.resend-bvn-otp'), [
            'bvn' => $bvn
        ]);
    }

    /**
     * BVN OTP resent successfully
     */
    public function test_bvn_otp_is_resent_successfully()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
        Notification::fake();
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
        $bvn = '11111111111';
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'confidential' => $bvn,
                                    'phone' => $phone
                                ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.resend-bvn-otp'), [
            'bvn' => $bvn
        ]);
        $camouflage->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNotNull($camouflage->verification);
        Notification::assertSentTo($user, BvnVerificationNotification::class);
    }
}
