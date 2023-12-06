<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
use Laravel\Sanctum\Sanctum;
use App\Models\{Application, Role, User, Camouflage};
use App\Exceptions\NoPendingApplicationException;
use Tests\TestCase;

class ApplicationOnboardingPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * No pending applications
     */
    public function test_user_has_no_pending_applications()
    {
        $this->withoutExceptionHandling();
        $this->expectException(NoPendingApplicationException::class);
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
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create([
                                    'phone' => $phone
                                ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson(route('applications.onboarding-payment'));
    }

    /**
     * Application onboarding payment was initialized successfully
     *
     * @return void
     */
    public function test_application_onboarding_payment_was_successfully_initialized()
    {
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
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create([
                                    'phone' => $phone
                                ]);
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'status' => Application::PENDING
                                ]);
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/rbozi97dw1gaku5',
                    'access_code' => 'rbozi97dw1gaku5',
                    'reference' => '0uh0zb2dlu'
                ],
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson(route('applications.onboarding-payment'));

        $response->assertOk();
        Http::assertSent(fn(Request $request) => $request->url() === 'https://api.paystack.co/transaction/initialize');
    }
}
