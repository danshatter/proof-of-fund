<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Option, Role, User};
use Tests\TestCase;

class DeleteOptionTest extends TestCase
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
        $response = $this->deleteJson(route('admin.options.destroy', [
            'option' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Option was successfully deleted
     */
    public function test_option_was_successfully_deleted()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $option = Option::factory()
                        ->create();
        
        Sanctum::actingAs($admin, ['*']);
        $response = $this->deleteJson(route('admin.options.destroy', [
            'option' => $option
        ]));

        $response->assertOk();
        $this->assertModelMissing($option);
    }
}
