<?php

namespace App\Traits\ThirdParty;

use Illuminate\Support\Facades\DB;
use App\Models\{Transaction, Application};
use App\Notifications\{OnboardingPaymentSuccessfulNotification, PaymentSuccessfulNotification};
use App\Services\{
    PaymentResolver,
    Application as ApplicationService
};

trait Paystack
{
    /**
     * For the onboarding of a user
     */
    protected function onboarding($data)
    {
        // Get the application tied to the transaction
        $application = Application::with(['user'])
                                ->find($data['metadata']['application_id']);

        // Update all necessary things regarding payment onboarding
        DB::transaction(function() use ($application, $data) {
            // Create the record of the transaction
            $transaction = $application->transactions()
                                    ->updateOrCreate([
                                        'reference' => $data['reference'],
                                    ], [
                                        'type' => Transaction::ONBOARDING,
                                        'message' => $data['gateway_response'] ?? $data['status'],
                                        'amount' => $data['amount'],
                                        'customer_code' => $data['customer']['customer_code'],
                                        'currency' => $data['currency'],
                                        'channel' => $data['channel']
                                    ]);

            // Create the record of the card
            $card = $application->user->cards()
                                    ->updateOrCreate([
                                        'signature' => $data['authorization']['signature']
                                    ], [
                                        'email' => $data['customer']['email'],
                                        'authorization' => $data['authorization']
                                    ]);

            // Update the status of the application to in review
            $application->forceFill([
                'status' => Application::IN_REVIEW
            ])->save();
        });

        // Notify the user
        $application->user->notify(new OnboardingPaymentSuccessfulNotification);
    }

    /**
     * For payments initiated
     */
    protected function payments($data)
    {
        // Get the application tied to the transaction
        $application = Application::with(['user'])
                                ->find($data['metadata']['application_id']);

        // Update all necessary things regarding payment onboarding
        DB::transaction(function() use ($application, $data) {
            // Create the record of the transaction
            $transaction = $application->transactions()
                                    ->updateOrCreate([
                                        'reference' => $data['reference'],
                                    ], [
                                        'type' => Transaction::PAYMENT,
                                        'message' => $data['gateway_response'] ?? $data['status'],
                                        'amount' => $data['amount'],
                                        'customer_code' => $data['customer']['customer_code'],
                                        'currency' => $data['currency'],
                                        'channel' => $data['channel']
                                    ]);

            // Process the payment
            app()->make(PaymentResolver::class)->process($application, $data['amount']);
        });

        // Check if the application is completed
        if ($application->status === Application::COMPLETED) {
            $message = __('app.payment_successful_and_application_closed');
        } else {
            $message = __('app.payment_successful_but_application_still_open', [
                'remaining_amount' => app()->make(ApplicationService::class)->moneyDisplay($application->amount_remaining)
            ]);
        }

        // Notify the user
        $application->user->notify(new PaymentSuccessfulNotification($message));
    }

    /**
     * For refunds
     */
    protected function refund($data)
    {
        // Create the record of the transaction
        $transaction = Transaction::create([
            'reference' => $data['transaction_reference'],
            'type' => Transaction::REFUND,
            'message' => $data['status'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
        ]);
    }
}
