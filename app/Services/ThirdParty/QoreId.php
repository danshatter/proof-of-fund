<?php

namespace App\Services\ThirdParty;

use Illuminate\Support\Facades\{Http, Cache};
use App\Exceptions\CustomException;

class QoreId
{
    /**
     * The access token
     */
    private $accessToken;

    /**
     * Create an instance
     */
    public function __construct()
    {
        // Create the access token
        $this->accessToken = Cache::remember('qore-id-token', 7000, function() {
            $response = Http::withOptions([
                                'connect_timeout' => 20,
                                'timeout' => 60,
                            ])
                            ->acceptJson()
                            ->asJson()
                            ->post('https://api.qoreid.com/token', [
                                'clientId' => config('services.qore_id.client_id'),
                                'secret' => config('services.qore_id.secret_key')
                            ]);

            $body = $response->json();

            if ($response->failed()) {
                throw new CustomException('Failed authenticating to Qore ID: '.$body['message'], 503);
            }

            return $body['accessToken'];
        });
    }

    /**
     * For BVN verification
     */
    public function bvnVerification($bvn, $data)
    {
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->acceptJson()
                        ->asJson()
                        ->withToken($this->accessToken)
                        ->post("https://api.qoreid.com/v1/ng/identities/bvn-basic/{$bvn}", $data);

        $body = $response->json();

        if ($response->failed()) {
            throw new CustomException('BVN confirmation failed: '.$body['message'], 503);
        }

        return $body;
    }

    /**
     * Get the passport details
     */
    public function passportDetails($passportNumber, $data)
    {
        $response = Http::withOptions([
                            'connect_timeout' => 20,
                            'timeout' => 60,
                        ])
                        ->acceptJson()
                        ->asJson()
                        ->withToken($this->accessToken)

                        
                        ->post("https://api.qoreid.com/v1/ng/identities/passport/{$passportNumber}", $data);

        $body = $response->json();

        if ($response->failed()) {
        throw new CustomException('Failed to fetch passport details: '.$body['message'], 503);
        }

        return $body;
    } 
}