<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\{Role, User};
use App\Exceptions\{InvalidCredentialsException, AccountLockedException};
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_authenticating_user()
    {
        $response = $this->postJson(route('auth.login'), [
            'username' => null
        ]);

        $response->assertInvalid(['username']);
        $response->assertUnprocessable();
    }

    /**
     * Invalid credentials
     *
     * @return void
     */
    public function test_user_provided_invalid_credentials()
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvalidCredentialsException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $password = 'password';
        $wrongPassword = 'wrongpassword';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone,
                        'password' => Hash::make($password)
                    ]);

        $response = $this->postJson(route('auth.login'), [
            'username' => $user->phone,
            'password' => $wrongPassword
        ]);
    }

    /**
     * User account is locked
     *
     * @return void
     */
    public function test_user_account_is_locked()
    {
        $this->withoutExceptionHandling();
        $this->expectException(AccountLockedException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $password = 'password';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone,
                        'locked_due_to_failed_login_attempts_at' => now()
                    ]);

        $response = $this->postJson(route('auth.login'), [
            'username' => $user->phone,
            'password' => $password,
        ]);
    }

    /**
     * User login successful
     *
     * @return void
     */
    public function test_user_login_was_successful()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $password = 'password';
        $user = User::factory()
                    ->users()
                    ->create([
                        'phone' => $phone
                    ]);

        $response = $this->postJson(route('auth.login'), [
            'username' => $user->phone,
            'password' => $password,
        ]);

        $response->assertValid();
        $response->assertOk();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id
        ]);
    }
}
