<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetBanksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Banks were successfully fetched
     */
    public function test_banks_were_successfully_fetched()
    {
        Http::fake([
            'https://api.paystack.co/bank' => Http::response([
                'status' => true,
                'message' => 'Banks retrieved',
                'data' => [
                    [
                        'id' => 302,
                        'name' => '9mobile 9Payment Service Bank',
                        'code' => '120001'
                    ]
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);

        $response = $this->getJson(route('banks.index'));

        $response->assertOk();
    }
}
