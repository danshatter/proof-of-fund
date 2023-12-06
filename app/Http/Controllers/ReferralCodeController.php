<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReferralCodeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $referralCode = $request->user()->generateReferralCode();

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'code' => $referralCode
        ]);
    }
}
