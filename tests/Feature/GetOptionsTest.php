<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetOptionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Options were successfully fetched
     */
    public function test_options_were_successfully_fetched()
    {
        $response = $this->getJson(route('options.index'));

        $response->assertOk();
    }
}
