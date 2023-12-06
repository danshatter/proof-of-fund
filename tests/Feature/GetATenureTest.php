<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Tenure;
use Tests\TestCase;

class GetATenureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tenure not found
     */
    public function test_tenure_is_not_found()
    {
        $response = $this->getJson(route('tenures.show', [
            'tenure' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Tenure was successfully fetched
     */
    public function test_tenure_was_successfully_fetched()
    {
        $tenure = Tenure::factory()
                        ->create();

        $response = $this->getJson(route('tenures.show', [
            'tenure' => $tenure
        ]));

        $response->assertOk();
    }
}
