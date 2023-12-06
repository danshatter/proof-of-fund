<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\InvalidVerifyMeSignatureException;

class QoreIdWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if ($request->header('X-Verifyme-Signature') !== hash_hmac('sha512', $request->getContent(), config('services.qore_id.webhook_secret'))) {
            throw new InvalidVerifyMeSignatureException;
        }

        // Write logic here
        http_response_code(200);


    }
}
