<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\InvalidPaystackSignatureException;
use App\Models\Transaction;
use App\Traits\ThirdParty\Paystack;

class PaystackWebhookController extends Controller
{
    use Paystack;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if ($request->header('X-Paystack-Signature') !== hash_hmac('sha512', $request->getContent(), config('services.paystack.secret_key'))) {
            throw new InvalidPaystackSignatureException;
        }

        // Decode the JSON data
		$body = $request->json()->all();

        info($body);

        // Write logic here
        http_response_code(200);

        // Check the event just in case we want to add more events
        switch ($body['event']) {
            case 'charge.success':
                // The type of transaction
                $type = $body['data']['metadata']['type'];

                /**
                 * For the different types of charge transactions
                 */
                if ($type === Transaction::ONBOARDING) {
                    // For onboarding payments
                    return $this->onboarding($body['data']);
                } elseif ($type === Transaction::PAYMENT) {
                    // For payments
                    return $this->payments($body['data']);
                }
            break;

            case 'refund.processed':
                return $this->refund($body['data']);
            break;

            default:
                
            break;
        }
    }
}
