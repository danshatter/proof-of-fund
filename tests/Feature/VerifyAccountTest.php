<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_verifying_account()
    {
        $response = $this->getJson(route('accounts.verify'));

        $response->assertInvalid(['account_number']);
        $response->assertUnprocessable();
    }

    /**
     * Account verification successful
     */
    public function test_account_was_successfully_verified()
    {
        $bankCode = '058';
        $accountNumber = '1100000000';
        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => 'Renners Investment',
                    'bank_id' => 9
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);
        $response = $this->getJson(route('accounts.verify', [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber
        ]));

        Http::assertSent(fn(Request $request) => str_starts_with($request->url(), 'https://api.paystack.co/bank/resolve'));
        $response->assertOk();
    }
}
