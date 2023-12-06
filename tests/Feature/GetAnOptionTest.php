<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Option;
use Tests\TestCase;

class GetAnOptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Option not found
     */
    public function test_option_is_not_found()
    {
        $response = $this->getJson(route('options.show', [
            'option' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Option was successfully fetched
     */
    public function test_option_was_successfully_fetched()
    {
        $option = Option::factory()
                        ->create();

        $response = $this->getJson(route('options.show', [
            'option' => $option
        ]));

        $response->assertOk();
    }
}
