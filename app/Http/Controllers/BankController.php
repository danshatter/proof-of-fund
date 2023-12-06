<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\CustomException;
use App\Services\ThirdParty\Paystack as PaystackService;

class BankController extends Controller
{
    /**
     * Get the list of banks
     */
    public function index(Request $request)
    {
        $banks = app()->make(PaystackService::class)->banks();

        return $this->sendSuccess(__('app.request_successful'), 200, $banks);
    }

    /**
     * Get a bank
     */
    public function show(Request $request, $bankCode)
    {
        $bank = app()->make(PaystackService::class)->bank($bankCode);

        if (!isset($bank)) {
            throw new CustomException('Bank not found.', 404);
        }

        return $this->sendSuccess(__('app.request_successful'), 200, $bank);
    }
}
