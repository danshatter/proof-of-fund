<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\{Tenure, Role, User};
use Tests\TestCase;

class UpdateTenureTest extends TestCase
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
        $response = $this->putJson(route('admin.tenures.update', [
            'tenure' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_updating_tenure()
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
        $response = $this->putJson(route('admin.tenures.update', [
            'tenure' => $tenure
        ]), [
            'months' => null
        ]);

        $response->assertInvalid(['months']);
        $response->assertUnprocessable();
    }

    /**
     * Tenure was updated successfully
     */
    public function test_tenure_was_successfully_updated()
    {
        $oldMonths = 3;
        $newMonths = 5;
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $tenure = Tenure::factory()
                        ->create([
                            'months' => $oldMonths
                        ]);

        Sanctum::actingAs($admin, ['*']);
        $response = $this->putJson(route('admin.tenures.update', [
            'tenure' => $tenure
        ]), [
            'months' => $newMonths
        ]);
        $tenure->refresh();

        $response->assertValid();
        $response->assertOk();
        $this->assertSame($newMonths, $tenure->months);
    }
}
