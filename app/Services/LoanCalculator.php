<?php

namespace App\Services;

use Illuminate\Support\{Str, Carbon};

class LoanCalculator
{
    /**
     * Calculate the payment schedule of a tenure and loan option
     */
    public function schedule($amount, $option, $tenure)
    {
        return [
            'amount' => (int) $amount,
            'interest' => $this->loanInterest($option),
            'duration' => $this->duration($tenure),
            'payment_details' => $this->paymentDetails($amount, $option, $tenure),
            'total_payment_amount' => $this->totalPaymentAmount($amount, $option, $tenure)
        ];
    }

    /**
     * The duration of the loan
     */
    public function duration($tenure)
    {
        return $tenure->months.' '.Str::of('month')->upper()->plural($tenure->months);
    }

    /**
     * The interest on the loan
     */
    private function loanInterest($option)
    {
        return $option->interest;
    }

    /**
     * Get the payment details of the loan
     */
    private function paymentDetails($amount, $option, $tenure)
    {
        $paymentDetails = [];

        for ($i = 0; $i < $this->numberOfPayments($tenure); $i++) { 
            array_push($paymentDetails, [
                'loan_number' => $i + 1,
                'amount' => $this->installmentAmount($amount, $option),
                'payment_date' => $this->paymentDate($tenure, $i + 1)
            ]);
        }

        return $paymentDetails;
    }

    /**
     * Get the number of payments
     */
    private function numberOfPayments($tenure)
    {
        return $tenure->months;
    }

    /**
     * Get the tenure duration
     */
    private function tenureDuration($tenure)
    {
        return 30;
    }

    /**
     * Get the interest amount to be paid for a loan amount
     */
    private function interest($interest, $amount)
    {
        return ($interest / 100) * $amount;
    }

    /**
     * Get the total amount that will be paid for each loan installment
     */
    private function installmentAmount($amount, $option)
    {
        return ceil($this->interest($option->interest, $amount));
    }

    /**
     * Get the payment dates
     */
    private function paymentDate($tenure, $multiplier)
    {
        return Carbon::parse(now(config('japa.local_timezone'))->toDateTimeString())
                    ->addDays($this->tenureDuration($tenure) * $multiplier)
                    ->format('Y-m-d');
    }

    /**
     * Get the total payment amount
     */
    private function totalPaymentAmount($amount, $option, $tenure)
    {
        return $this->installmentAmount($amount, $option) * $this->numberOfPayments($tenure);
    }
}