<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{StoreBalanceRequest, UpdateBalanceRequest};
use App\Models\Balance;

class BalanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $balance = $request->user()
                        ->balance()
                        ->firstOr(function() use ($request) {
                            $balance = $request->user()
                                            ->balance()
                                            ->updateOrCreate([]);

                            return $balance;
                        });

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'amount_earned' => $balance->amount_earned,
            'balance' => $balance->amount_remaining
        ]);
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
    public function store(StoreBalanceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Balance $balance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Balance $balance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBalanceRequest $request, Balance $balance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Balance $balance)
    {
        //
    }
}
