<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use App\Models\{Application, Role, User, Camouflage};
use App\Exceptions\CompletedApplicationException;
use Tests\TestCase;

class ApplicationPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_initiating_application_payment()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson(route('applications.payment', [
            'applicationId' => 'non-existent-id'
        ]), [
            'amount' => null
        ]);

        $response->assertInvalid(['amount']);
        $response->assertUnprocessable();
    }

    /**
     * Application not found
     */
    public function test_application_is_not_found()
    {
        $amount = 100000;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson(route('applications.payment', [
            'applicationId' => 'non-existent-id'
        ]), [
            'amount' => $amount
        ]);

        $response->assertNotFound();
    }

    /**
     * Application is already completed
     */
    public function test_application_is_already_completed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CompletedApplicationException::class);
        $amount = 1000000;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'status' => Application::COMPLETED
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
        $response = $this->postJson(route('applications.payment', [
            'applicationId' => $application->id
        ]), [
            'amount' => $amount
        ]);
    }

    /**
     * Application payment was initialized successfully
     */
    public function test_application_payment_was_successfully_initialized()
    {
        $amount = 1000000;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'status' => Application::OPEN
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
        $response = $this->postJson(route('applications.payment', [
            'applicationId' => $application->id
        ]), [
            'amount' => $amount
        ]);

        $response->assertOk();
        Http::assertSent(fn(Request $request) => $request->url() === 'https://api.paystack.co/transaction/initialize');
    }
}
