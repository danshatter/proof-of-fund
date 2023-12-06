<?php

namespace App\Services\ThirdParty;

use Illuminate\Support\Facades\{Http, Cache};
use App\Exceptions\CustomException;

class Paystack
{
    /**
     * Get the list of banks
     */
    public function banks()
    {
        return Cache::remember('banks', 3000, function() {
            // Make the request to fetch the banks
            $response = Http::withOptions([
                                'connect_timeout' => 20,
                                'timeout' => 60,
                            ])
                            ->withToken(config('services.paystack.secret_key'))
                            ->get('https://api.paystack.co/bank');

            $data = $response->json();

            // Check if there was an error
            if ($response->failed() ||
                $this->failedBasedOnResponseBody($data)
            ) {
                // Delete the items from the cache. This is not needed but is here just in case
                Cache::delete('banks');

                throw new CustomException('Error fetching banks: '.$this->failureResponse($data), 503);
            }

            // The banks and the data we need
            return collect($data['data'])->unique('name')->map(fn($bank) => [
                'name' => data_get($bank, 'name'),
                'code' => data_get($bank, 'code')
            ]);
        });
    }

    /**
     * Get a bank
     */
    public function bank($code)
    {
        $banks = $this->banks();

        return $banks->firstWhere('code', $code);
    }

    /**
     * Verify a NUBAN
     */
    public function nubanVerify($accountNumber, $bankCode)
    {
        // Make the request to verify an account number
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->withToken(config('services.paystack.secret_key'))
                        ->get('https://api.paystack.co/bank/resolve', [
                            'account_number' => $accountNumber,
                            'bank_code' => $bankCode
                        ]);
        
        $data = $response->json();

        // Check if there was an error
        if ($response->failed() ||
            $this->failedBasedOnResponseBody($data)) {
            throw new CustomException('Error verifying account number: '.$this->failureResponse($data), 503);
        }

        return $data;
    }

    /**
     * Refund a customer
     */
    public function refund($reference, $amount)
    {
        // Make the request to refund a customer
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/refund', [
                            'transaction' => $reference,
                            'amount' => $amount
                        ]);
        
        $data = $response->json();

        info('Paystack refund');
        info('Paystack refund status: '.$response->status());
        info($data);

        // Check if there was an error
        if ($response->failed() ||
            $this->failedBasedOnResponseBody($data)
        ) {
            throw new CustomException('Error refunding user: '.$this->failureResponse($data), 503);
        }

        return $data;
    }

    /**
     * Initiate debit
     */
    public function debit($requestBody, $skip = false)
    {
        // Make the request to charge a card
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/transaction/charge_authorization', $requestBody);
        
        $data = $response->json();

        info('Paystack debit');
        info('Paystack debit status: '.$response->status());
        info($data);

        // Check if there was an error
        if ($response->failed() ||
            $this->failedBasedOnResponseBody($data)
        ) {
            if ($skip) {
                return $data;
            }

            throw new CustomException('Error initiating debit: '.$this->failureResponse($data), 503);
        }

        return $data;
    }

    /**
     * Initiate partial debit
     */
    public function partialDebit($requestBody, $skip = false)
    {
        // Make the request to charge a card
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/transaction/partial_debit', $requestBody);
        
        $data = $response->json();

        info('Paystack partial debit');
        info('Paystack partial debit status: '.$response->status());
        info($data);

        // Check if there was an error
        if ($response->failed() ||
            $this->failedBasedOnResponseBody($data)
        ) {
            if ($skip) {
                return $data;
            }

            throw new CustomException('Error initiating partial debit: '.$this->failureResponse($data), 503);
        }

        return $data;
    }

    /**
     * Initialize a transaction
     */
    public function initializeTransaction($requestBody)
    {
        // Make the request to create a virtual account
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/transaction/initialize', $requestBody);
        
        $data = $response->json();

        // Check if there was an error
        if ($response->failed() ||
            $this->failedBasedOnResponseBody($data)
        ) {
            throw new CustomException('Error initializing transaction: '.$this->failureResponse($data), 503);
        }

        return $data;
    }

    /**
     * Initiate a split payment transaction
     */
    public function split($requestBody)
    {
        // Make the request to create a virtual account
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/split', $requestBody);
        
        $data = $response->json();

        info('Paystack split');
        info('Paystack split status: '.$response->status());
        info($data);

        // Check if there was an error
        if ($response->failed() ||
            $this->failedBasedOnResponseBody($data)
        ) {
            throw new CustomException('Error initiating split payment: '.$this->failureResponse($data), 503);
        }

        return $data;
    }

    /**
     * Check if the request was successful based on the response body
     */
    private function failedBasedOnResponseBody($body)
    {
        return (isset($body['status']) && $body['status'] === false) ||
            (isset($body['data']['status']) && strtolower($body['data']['status'] === 'failed'));
    }

    /**
     * Get the failure response message based on the request body
     */
    private function failureResponse($body)
    {
        return $body['data']['gateway_response'] ?? $body['message'] ?? 'Unknown error occurred';
    }
}