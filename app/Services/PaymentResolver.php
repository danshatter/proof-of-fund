<?php

namespace App\Services;

use App\Models\Application;

class PaymentResolver
{
    /**
     * Process the payment on an application
     */
    public function process($application, $amountPaid)
    {
        // Order the loan details by the loan number
        $applicationDetails = collect($application->details)->sortBy('loan_number');

        // The new application details
        $newApplicationDetails = [];

        // The deductions
        $deductions = [
            'amount_remaining' => 0
        ];

        // The active loan details
        $activeLoan = [
            'number' => null,
            'status' => null
        ];

        $applicationDetails->reduce(function($accumulator, $loanDetail) use (&$newApplicationDetails, &$deductions) {
            // Check if the installment has been paid for or closed
            if ($loanDetail['status'] === Application::INSTALLMENT_CLOSED) {
                array_push($newApplicationDetails, $loanDetail);

                return $accumulator;
            }

            // Check if the current iteration is the active loan, then set it in the accumulator
            if (in_array($loanDetail['status'], [
                Application::INSTALLMENT_OPEN,
                Application::INSTALLMENT_OVERDUE
            ])) {
                $this->setInstallmentAsActive($loanDetail, $accumulator);
            }

            /**
             * Activate loan installment if it is the active loan
             */
            if ($loanDetail['loan_number'] === $accumulator['active_loan']['number']) {
                // Mark loan as active loan
                $this->activateLoanInstallment($loanDetail, $accumulator);
            }

            // Check if there is any amount remaining balance left after deducting preceeding "OPEN" loans
            if ($accumulator <= 0) {
                array_push($newApplicationDetails, $loanDetail);

                return $accumulator;
            }

            // Process the amount remaining
            $this->processAmountRemainingDeduction($accumulator, $loanDetail, $deductions);

            // Process the closure and marking the installment as paid if everything is as it should be
            $this->processInstallmentClosure($loanDetail, $accumulator);

            // Store the new loan offer details after processing penalties and amount remaining
            array_push($newApplicationDetails, $loanDetail);

            return $accumulator;
        }, [
            'active_loan' => $activeLoan,
            'amount_paid' => $amountPaid
        ]);

        // Get the active installment
        $activeInstallment = collect($newApplicationDetails)->whereIn('status', [
            Application::INSTALLMENT_OPEN,
            Application::INSTALLMENT_OVERDUE
        ])->first();

        // Save the new loan details
        $application->forceFill([
            'details' => $newApplicationDetails,
            'active_installment' => $activeInstallment
        ])->save();

        // Decrement the associated amount remaining
        $application->decrement('amount_remaining', $deductions['amount_remaining']);

        // Check if the application should be marked as completed based on the amount remaining
        if ($application->amount_remaining <= 0) {
            // Mark the application as completed
            $application->forceFill([
                'status' => Application::COMPLETED
            ])->save();
        }
    }

    /**
     * Mark the loan installment as the active loan
     */
    private function activateLoanInstallment(&$loanDetail, &$accumulator)
    {
        if ($loanDetail['status'] === Application::INSTALLMENT_PENDING) {
            // Mark loan installment as "OPEN"
            $loanDetail['status'] = Application::INSTALLMENT_OPEN;

            $this->setInstallmentAsActive($loanDetail, $accumulator);
        }
    }

    /**
     * Set a loan installment as the active loan
     */
    private function setInstallmentAsActive(&$loanDetail, &$accumulator)
    {
        // Set the active loan number
        $accumulator['active_loan']['number'] = $loanDetail['loan_number'];
    }

    /**
     * Process the amount remaining deduction
     */
    private function processAmountRemainingDeduction(&$accumulator, &$loanInstallment, &$deductions)
    {
        // Check if the amount is greater than 0
        if ($accumulator['amount_paid'] > 0) {
            /**
             * Check if there is actually any amount remaining to be paid. We don't need this as the preceeding
             * checks gives us the notion that loan installment is still "UNPAID" but we put it just in case
             */
            if ($loanInstallment['amount_remaining'] > 0) {
                // We store the initial amount paid value
                $initial = $accumulator['amount_paid'];

                // Deduct from the amount remaining in the loan installment
                $accumulator['amount_paid'] -= $loanInstallment['amount_remaining'];

                // Check if the amount was enough to deduct the amount remaining
                if ($accumulator['amount_paid'] >= 0) {
                    // Add full amount remaining to total amount remaining
                    $deductions['amount_remaining'] += $loanInstallment['amount_remaining'];

                    // Amount remaining was fully paid
                    $loanInstallment['amount_remaining'] = 0;
                } else {
                    // Add the amount remaining to the total amount remaining
                    $deductions['amount_remaining'] += $initial;
                    
                    // Set the amount remaining to the amount left
                    $loanInstallment['amount_remaining'] = abs($accumulator['amount_paid']);

                    // Amount paid is negative, thereby exhausted
                    $accumulator['amount_paid'] = 0;
                }
            }
        }
    }

    /**
     * Process the closure of an installment
     */
    private function processInstallmentClosure(&$loanDetail, &$accumulator)
    {
        /**
         * We close the loan installment if the penalty remaining and the amount remaining is 0
         */
        if ($loanDetail['amount_remaining'] <= 0) {
            // Set the installment status as closed
            $loanDetail['status'] = Application::INSTALLMENT_CLOSED;

            // Set the closed at date
            $loanDetail['closed_at'] = now()->format('Y-m-d');

            // Set the next loan number as the next active installment
            $accumulator['active_loan']['number']++;
        }
    }
}