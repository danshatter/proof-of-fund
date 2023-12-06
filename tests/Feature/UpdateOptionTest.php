<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Option, Role, User};
use Tests\TestCase;

class UpdateOptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Option not found
     */
    public function test_option_is_not_found()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        
        Sanctum::actingAs($admin, ['*']);
        $response = $this->putJson(route('admin.options.update', [
            'option' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_updating_option()
    {
        $name = 'auditor';
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $option = Option::factory()
                        ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->putJson(route('admin.options.update', [
            'option' => $option
        ]), [
            'type' => null
        ]);

        $response->assertInvalid(['type']);
        $response->assertUnprocessable();
    }

    /**
     * Option was updated successfully
     */
    public function test_option_was_successfully_updated()
    {
        $oldType = 'Letter of investment';
        $oldInterest = 3.4;
        $newType = 'Letter of sponsorship';
        $newInterest = 4.0;
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $option = Option::factory()
                        ->create([
                            'type' => $oldType,
                            'interest' => $oldInterest
                        ]);

        Sanctum::actingAs($admin, ['*']);
        $response = $this->putJson(route('admin.options.update', [
            'option' => $option
        ]), [
            'type' => $newType,
            'interest' => $newInterest
        ]);
        $option->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertSame($newType, $option->type);
        $this->assertSame($newInterest, $option->interest);
    }
}
