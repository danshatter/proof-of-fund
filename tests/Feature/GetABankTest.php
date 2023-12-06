<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use App\Exceptions\CustomException;
use Tests\TestCase;

class GetABankTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bank not found
     */
    public function test_bank_is_not_found()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CustomException::class);
        $this->expectExceptionMessage('Bank not found');
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

        $response = $this->getJson(route('banks.show', [
            'bankCode' => 'unknown-bank-code'
        ]));
    }

    /**
     * Bank was successfully fetched
     */
    public function test_bank_is_successfully_fetched()
    {
        $bankCode = '120001';
        Http::fake([
            'https://api.paystack.co/bank' => Http::response([
                'status' => true,
                'message' => 'Banks retrieved',
                'data' => [
                    [
                        'id' => 302,
                        'name' => '9mobile 9Payment Service Bank',
                        'code' => $bankCode
                    ]
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);

        $response = $this->getJson(route('banks.show', [
            'bankCode' => $bankCode
        ]));

        $response->assertOk();
    }
}
