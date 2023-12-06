<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoanCalculatorRequest;
use App\Services\LoanCalculator as LoanCalculatorService;
use App\Models\{Option, Tenure};

class LoanCalculatorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoanCalculatorRequest $request)
    {
        $data = $request->validated();

        // Get the option
        $option = Option::find($data['option_id']);

        // Get the tenure
        $tenure = Tenure::find($data['tenure_id']);

        $loanDetails = app()->make(LoanCalculatorService::class)->schedule($data['amount'], $option, $tenure);

        return $this->sendSuccess(__('app.request_successful'), 200, $loanDetails);
    }
}
