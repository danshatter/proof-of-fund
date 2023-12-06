<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Tenure, Role, User};
use Tests\TestCase;

class DeleteTenureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tenure not found
     */
    public function test_tenure_is_not_found()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        
        Sanctum::actingAs($admin, ['*']);
        $response = $this->deleteJson(route('admin.tenures.destroy', [
            'tenure' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Tenure was successfully deleted
     */
    public function test_tenure_was_successfully_deleted()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $tenure = Tenure::factory()
                        ->create();
        
        Sanctum::actingAs($admin, ['*']);
        $response = $this->deleteJson(route('admin.tenures.destroy', [
            'tenure' => $tenure
        ]));

        $response->assertOk();
        $this->assertModelMissing($tenure);
    }
}
