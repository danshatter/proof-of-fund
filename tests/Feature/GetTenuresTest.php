<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTenuresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tenures were successfully fetched
     */
    public function test_tenures_were_successfully_fetched()
    {
        $response = $this->getJson(route('tenures.index'));

        $response->assertOk();
    }
}
