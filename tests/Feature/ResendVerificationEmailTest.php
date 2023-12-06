<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\{Role, User};
use App\Exceptions\{EmailAlreadyVerifiedException, UserUnregisteredException};
use App\Notifications\AgentVerificationNotification;
use Tests\TestCase;

class ResendVerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_resending_verification_email()
    {
        $response = $this->postJson(route('auth.resend-verification-email'), [
            'email' => null
        ]);

        $response->assertInvalid(['email']);
        $response->assertUnprocessable();
    }

    /**
     * Email does not belong to a registered user
     *
     * @return void
     */
    public function test_email_does_not_belong_to_a_registered_user()
    {
        $this->withoutExceptionHandling();
        $this->expectException(UserUnregisteredException::class);

        $response = $this->postJson(route('auth.resend-verification-email'), [
            'email' => fake()->email(),
        ]);
    }

    /**
     * Email is already verified
     */
    public function test_email_is_already_verified()
    {
        $this->withoutExceptionHandling();
        $this->expectException(EmailAlreadyVerifiedException::class);
        $individualAgentRole = Role::factory()
                            ->individualAgent()
                            ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();

        $response = $this->postJson(route('auth.resend-verification-email'), [
            'email' => $individualAgent->email,
        ]);
    }

    /**
     * Verification email was successfully sent
     */
    public function test_verification_email_was_successfully_sent()
    {
        Notification::fake();
        $agencyRole = Role::factory()
                        ->agency()
                        ->create();
        $agency = User::factory()
                    ->agencies()
                    ->create([
                        'email_verified_at' => null
                    ]);

        $response = $this->postJson(route('auth.resend-verification-email'), [
            'email' => $agency->email,
        ]);

        Notification::assertSentTo($agency, AgentVerificationNotification::class);
    }
}
