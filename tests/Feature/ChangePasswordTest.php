<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use App\Models\{Role, User};
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_changing_password()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create();

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson(route('auth.change-password'), [
            'current_password' => null
        ]);

        $response->assertInvalid(['current_password']);
        $response->assertUnprocessable();
    }

    /**
     * Password changed successfully
     */
    public function test_password_is_changed_successfully()
    {
        $currentPassword = 'password';
        $newPassword = 'newpassword';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create([
                        'password' => Hash::make($currentPassword)
                    ]);
        $oldHashedPassword = $user->password;

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson(route('auth.change-password'), [
            'current_password' => $currentPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);
        $user->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertNotSame($user->password, $oldHashedPassword);
    }
}
