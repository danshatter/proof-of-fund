<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{StoreAccountRequest, UpdateAccountRequest, VerifyAccountRequest};
use App\Models\Account;
use App\Services\ThirdParty\Paystack as PaystackService;
use App\Exceptions\{AccountTakenException, AddAccountForbiddenException, InvalidAccountNameException};

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request)
    {
        $data = $request->validated();

        // Get the account tied to the bank code and account number
        $accountDetails = app()->make(PaystackService::class)->nubanVerify($data['account_number'], $data['bank_code']);

        // Check if the account name matches
        if (strtolower($data['account_name']) !== strtolower($accountDetails['data']['account_name'])) {
            throw new InvalidAccountNameException;
        }

        // Check if the account has been added by another user
        if (Account::where('name', $data['account_name'])
                ->where('number', $data['account_number'])
                ->where('bank_code', $data['bank_code'])
                ->where('user_id', '!=', $request->user()->id)
                ->exists()) {
            throw new AccountTakenException;
        }

        /**
         * We check if the user can add the account number based on their name. We sanitize the names to remove
         * any special characters just in case
         */
        $sanitized = ["'", '-', ' '];

        $sanitizedAccountName = str_replace($sanitized, '', $data['account_name']);
        $sanitizedFirstName = str_replace($sanitized, '', $request->user()->first_name);
        $sanitizedLastName = str_replace($sanitized, '', $request->user()->last_name);

        // We check if the user can add the account based on their name being present in the account
        if (!str_contains(strtolower($sanitizedAccountName), strtolower($sanitizedFirstName)) ||
            !str_contains(strtolower($sanitizedAccountName), strtolower($sanitizedLastName))) {
            throw new AddAccountForbiddenException;
        }

        // Create or update the account
        $account = $request->user()
                        ->account()
                        ->updateOrCreate([

                        ], [
                            'name' => $accountDetails['data']['account_name'],
                            'number' => $data['account_number'],
                            'bank_code' => $data['bank_code']
                        ]);

        return $this->sendSuccess('Account details updated successfully.', 200, $account);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        //
    }

    /**
     * Verify a bank account
     */
    public function verify(VerifyAccountRequest $request)
    {
        $data = $request->validated();

        // Get the account tied to the bank code and account number
        $account = app()->make(PaystackService::class)->nubanVerify($data['account_number'], $data['bank_code']);

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'account_name' => $account['data']['account_name']
        ]);
    }

    /**
     * Get the accounts of a user
     */
    public function userIndex(Request $request)
    {
        $accounts = $request->user()
                            ->account()
                            ->get();

        return $this->sendSuccess(__('app.request_successful'), 200, $accounts);
    }

    /**
     * Get an account of a user
     */
    public function userShow(Request $request, $accountId)
    {
        $account = $request->user()
                        ->account()
                        ->findOrFail($accountId);

        return $this->sendSuccess(__('app.request_successful'), 200, $account);
    }
}
