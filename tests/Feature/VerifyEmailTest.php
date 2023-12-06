<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\{Role, User};
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * No token in URL
     */
    public function test_no_token_was_present_in_url()
    {
        $response = $this->get(route('auth.verify-email'));

        $response->assertSee(__('app.email_verification_failed'));
    }

    /**
     * No registered with verification
     */
    public function test_no_registered_user_has_supplied_verification_token()
    {
        $response = $this->get(route('auth.verify-email', [
            'token' => Str::random(20)
        ]));

        $response->assertSee(__('app.email_verification_failed'));
    }

    /**
     * Email has already been verified
     */
    public function test_registered_user_has_already_been_verified()
    {
        $role = Role::factory()
                    ->individualAgent()
                    ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->create([
                                'email_verified_at' => now(),
                                'email_verification' => Str::random(20)
                            ]);

        $response = $this->get(route('auth.verify-email', [
            'token' => $individualAgent->email_verification
        ]));

        $response->assertSee(__('app.email_already_verified'));
    }

    /**
     * Email verification successful
     */
    public function test_email_was_successfully_verified()
    {
        $role = Role::factory()
                    ->agency()
                    ->create();
        $agency = User::factory()
                    ->agencies()
                    ->create([
                        'email_verified_at' => null,
                        'email_verification' => Str::random(20)
                    ]);

        $response = $this->get(route('auth.verify-email', [
            'token' => $agency->email_verification
        ]));
        $agency->refresh();

        $this->assertNotNull($agency->email_verified_at);
    }
}
