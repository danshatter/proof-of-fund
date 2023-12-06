<?php

namespace App\Services\ThirdParty;

use Illuminate\Support\Facades\Http;
use App\Exceptions\CustomException;

class Termii
{
    /**
     * Send an SMS
     */
    public function sendSms($phone, $message, $throwExceptionOnFailure = false)
    {
        $response = Http::withOptions([
            'connect_timeout' => 20,
            'timeout' => 60
        ])
        ->post('https://api.ng.termii.com/api/sms/send', [
            'api_key' => config('services.termii.api_key'),
            'to' => $phone,
            // 'from' => config('app.name'),
            'from' => 'N-Alert',
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'dnd'
        ]);

        $body = $response->json();

        if ($response->failed()) {
            if ($throwExceptionOnFailure) {
                throw new CustomException('Failed to send SMS: '.($body['message'] ?? 'Unknown error occurred.'), $response->status());
            }
        }

        return $body;
    }
}