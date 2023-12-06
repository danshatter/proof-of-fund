<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Models\{Application, User, Role, Transaction};
use App\Notifications\{OnboardingPaymentSuccessfulNotification, PaymentSuccessfulNotification};
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Application onboarding was successful
     */
    public function test_application_onboarding_was_successful()
    {
        Notification::fake();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'status' => Application::PENDING
                                ]);
        $signature = 'SIG_SwxDY6bhoFZimiScc3tL';
        $reference = 'sd2fkk9oev';
        $amount = config('japa.document_verification_and_service_fee');
        $customerCode = 'CUS_qzmjdavnbyvtaxm';
        $currency = 'NGN';
        $channel = 'card';
        $paystackChargeSuccess = [
            'event' => 'charge.success',
            'data' => [
                'id' => 2110132872,
                'domain' => 'test',
                'status' => 'success',
                'reference' => $reference,
                'amount' => $amount,
                'message' => null,
                'gateway_response' => 'Successful',
                'paid_at' => '2022-09-17T10:46:08.000Z',
                'created_at' => '2022-09-17T10:45:59.000Z',
                'channel' => $channel,
                'currency' => $currency,
                'ip_address' => '197.211.58.181',
                'metadata' => [
                    'application_id' => $application->id,
                    'type' => Transaction::ONBOARDING,
                ],
                'fees_breakdown' => null,
                'log' => null,
                'fees' => 75,
                'fees_split' => null,
                'authorization' => [
                    'authorization_code' => 'AUTH_n42pnjpqkd',
                    'bin' => '408408',
                    'last4' => '4081',
                    'exp_month' => '12',
                    'exp_year' => '2030',
                    'channel' => 'card',
                    'card_type' => 'visa ',
                    'bank' => 'TEST BANK',
                    'country_code' => 'NG',
                    'brand' => 'visa',
                    'reusable' => true,
                    'signature' => $signature,
                    'account_name' => null,
                    'receiver_bank_account_number' => null,
                    'receiver_bank' => null,
                ],
                'customer' => [
                    'id' => 73359895,
                    'first_name' => null,
                    'last_name' => null,
                    'email' => 'customer@email.com',
                    'customer_code' => $customerCode,
                    'phone' => null,
                    'metadata' => null,
                    'risk_action' => 'default',
                    'international_format_phone' => null,
                ],
                'plan' => [],
                'subaccount' => [],
                'split' => [],
                'order_id' => null,
                'paidAt' => '2022-09-17T10:46:08.000Z',
                'requested_amount' => 5000,
                'pos_transaction_data' => null,
                'source' => [
                    'type' => 'api',
                    'source' => 'merchant_api',
                    'entry_point' => 'transaction_initialize',
                    'identifier' => null,
                ],
            ],
        ]; 

        $response = $this->postJson(route('paystack-webhook'), $paystackChargeSuccess, [
            'X-Paystack-Signature' => hash_hmac('sha512', json_encode($paystackChargeSuccess), config('services.paystack.secret_key'))
        ]);
        $application->refresh();

        $response->assertOk();
        $this->assertDatabaseHas('cards', [
            'user_id' => $user->id,
            'signature' => $signature
        ]);
        $this->assertDatabaseHas('transactions', [
            'application_id' => $application->id,
            'amount' => $amount,
            'reference' => $reference,
            'type' => Transaction::ONBOARDING,
            'customer_code' => $customerCode,
            'currency' => $currency,
            'channel' => $channel
        ]);
        $this->assertSame(Application::IN_REVIEW, $application->status);
        Notification::assertSentTo($user, OnboardingPaymentSuccessfulNotification::class);
    }

    /**
     * Payment successful and application is still open
     */
    public function test_payment_successful_but_application_is_still_open()
    {
        Notification::fake();
        $amountPaid = 20000000;
        $amountRemaining = 23000000;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'amount_remaining' => $amountRemaining,
                                    'status' => Application::OPEN,
                                    'details' => [
                                        [
                                            'loan_number' => 1,
                                            'amount' => $amountRemaining,
                                            'payment_date' => now()->addMonth()->format('Y-m-d'),
                                            'amount_remaining' => $amountRemaining,
                                            'status' => Application::INSTALLMENT_PENDING,
                                            'closed_at' => null
                                        ]
                                    ]
                                ]);
        $reference = 'sd2fkk9oev';
        $customerCode = 'CUS_qzmjdavnbyvtaxm';
        $currency = 'NGN';
        $channel = 'card';
        $paystackChargeSuccess = [
            'event' => 'charge.success',
            'data' => [
                'id' => 2110132872,
                'domain' => 'test',
                'status' => 'success',
                'reference' => $reference,
                'amount' => $amountPaid,
                'message' => null,
                'gateway_response' => 'Successful',
                'paid_at' => '2022-09-17T10:46:08.000Z',
                'created_at' => '2022-09-17T10:45:59.000Z',
                'channel' => $channel,
                'currency' => $currency,
                'ip_address' => '197.211.58.181',
                'metadata' => [
                    'application_id' => $application->id,
                    'type' => Transaction::PAYMENT,
                ],
                'fees_breakdown' => null,
                'log' => null,
                'fees' => 75,
                'fees_split' => null,
                'authorization' => [
                    'authorization_code' => 'AUTH_n42pnjpqkd',
                    'bin' => '408408',
                    'last4' => '4081',
                    'exp_month' => '12',
                    'exp_year' => '2030',
                    'channel' => 'card',
                    'card_type' => 'visa ',
                    'bank' => 'TEST BANK',
                    'country_code' => 'NG',
                    'brand' => 'visa',
                    'reusable' => true,
                    'signature' => 'SIG_SwxDY6bhoFZimiScc3tL',
                    'account_name' => null,
                    'receiver_bank_account_number' => null,
                    'receiver_bank' => null,
                ],
                'customer' => [
                    'id' => 73359895,
                    'first_name' => null,
                    'last_name' => null,
                    'email' => 'customer@email.com',
                    'customer_code' => $customerCode,
                    'phone' => null,
                    'metadata' => null,
                    'risk_action' => 'default',
                    'international_format_phone' => null,
                ],
                'plan' => [],
                'subaccount' => [],
                'split' => [],
                'order_id' => null,
                'paidAt' => '2022-09-17T10:46:08.000Z',
                'requested_amount' => 5000,
                'pos_transaction_data' => null,
                'source' => [
                    'type' => 'api',
                    'source' => 'merchant_api',
                    'entry_point' => 'transaction_initialize',
                    'identifier' => null,
                ],
            ],
        ]; 

        $response = $this->postJson(route('paystack-webhook'), $paystackChargeSuccess, [
            'X-Paystack-Signature' => hash_hmac('sha512', json_encode($paystackChargeSuccess), config('services.paystack.secret_key'))
        ]);
        $application->refresh();

        $response->assertOk();
        $this->assertDatabaseHas('transactions', [
            'application_id' => $application->id,
            'amount' => $amountPaid,
            'reference' => $reference,
            'type' => Transaction::PAYMENT,
            'customer_code' => $customerCode,
            'currency' => $currency,
            'channel' => $channel
        ]);
        $this->assertSame(Application::OPEN, $application->status);
        $this->assertSame($amountRemaining - $amountPaid, $application->amount_remaining);
        $this->assertSame($amountRemaining - $amountPaid, $application->details[0]['amount_remaining']);
        Notification::assertSentTo($user, PaymentSuccessfulNotification::class);
    }

    /**
     * Payment successful and application is closed
     */
    public function test_payment_successful_and_application_is_closed()
    {
        Notification::fake();
        $amountPaid = 20000000;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'amount_remaining' => $amountPaid,
                                    'status' => Application::OPEN,
                                    'details' => [
                                        [
                                            'loan_number' => 1,
                                            'amount' => $amountPaid,
                                            'payment_date' => now()->addMonth()->format('Y-m-d'),
                                            'amount_remaining' => $amountPaid,
                                            'status' => Application::INSTALLMENT_PENDING,
                                            'closed_at' => null
                                        ]
                                    ]
                                ]);
        $reference = 'sd2fkk9oev';
        $customerCode = 'CUS_qzmjdavnbyvtaxm';
        $currency = 'NGN';
        $channel = 'card';
        $paystackChargeSuccess = [
            'event' => 'charge.success',
            'data' => [
                'id' => 2110132872,
                'domain' => 'test',
                'status' => 'success',
                'reference' => $reference,
                'amount' => $amountPaid,
                'message' => null,
                'gateway_response' => 'Successful',
                'paid_at' => '2022-09-17T10:46:08.000Z',
                'created_at' => '2022-09-17T10:45:59.000Z',
                'channel' => $channel,
                'currency' => $currency,
                'ip_address' => '197.211.58.181',
                'metadata' => [
                    'application_id' => $application->id,
                    'type' => Transaction::PAYMENT,
                ],
                'fees_breakdown' => null,
                'log' => null,
                'fees' => 75,
                'fees_split' => null,
                'authorization' => [
                    'authorization_code' => 'AUTH_n42pnjpqkd',
                    'bin' => '408408',
                    'last4' => '4081',
                    'exp_month' => '12',
                    'exp_year' => '2030',
                    'channel' => 'card',
                    'card_type' => 'visa ',
                    'bank' => 'TEST BANK',
                    'country_code' => 'NG',
                    'brand' => 'visa',
                    'reusable' => true,
                    'signature' => 'SIG_SwxDY6bhoFZimiScc3tL',
                    'account_name' => null,
                    'receiver_bank_account_number' => null,
                    'receiver_bank' => null,
                ],
                'customer' => [
                    'id' => 73359895,
                    'first_name' => null,
                    'last_name' => null,
                    'email' => 'customer@email.com',
                    'customer_code' => $customerCode,
                    'phone' => null,
                    'metadata' => null,
                    'risk_action' => 'default',
                    'international_format_phone' => null,
                ],
                'plan' => [],
                'subaccount' => [],
                'split' => [],
                'order_id' => null,
                'paidAt' => '2022-09-17T10:46:08.000Z',
                'requested_amount' => 5000,
                'pos_transaction_data' => null,
                'source' => [
                    'type' => 'api',
                    'source' => 'merchant_api',
                    'entry_point' => 'transaction_initialize',
                    'identifier' => null,
                ],
            ],
        ]; 

        $response = $this->postJson(route('paystack-webhook'), $paystackChargeSuccess, [
            'X-Paystack-Signature' => hash_hmac('sha512', json_encode($paystackChargeSuccess), config('services.paystack.secret_key'))
        ]);
        $application->refresh();

        $response->assertOk();
        $this->assertDatabaseHas('transactions', [
            'application_id' => $application->id,
            'amount' => $amountPaid,
            'reference' => $reference,
            'type' => Transaction::PAYMENT,
            'customer_code' => $customerCode,
            'currency' => $currency,
            'channel' => $channel
        ]);
        $this->assertSame(Application::COMPLETED, $application->status);
        $this->assertSame(0, $application->amount_remaining);
        $this->assertSame(0, $application->details[0]['amount_remaining']);
        Notification::assertSentTo($user, PaymentSuccessfulNotification::class);
    }

    /**
     * Refund was successful
     */
    public function test_refund_was_successful()
    {
        $refund = [
            'event' => 'refund.processed',
            'data' => [
                'status' => 'processed',
                'transaction_reference' => 'T2154954_412829_3be32076_6lcg3',
                'refund_reference' => '132013318360',
                'amount' => '5000',
                'currency' => 'NGN',
                'processor' => 'mpgs_zen',
                'customer' => [
                    'first_name' => 'Damilola',
                    'last_name' => 'Kwabena',
                    'email' => 'damilola@email.com',
                ],
                'integration' => 412829,
                'domain' => 'live',
            ],
        ]; 

        $response = $this->postJson(route('paystack-webhook'), $refund, [
            'X-Paystack-Signature' => hash_hmac('sha512', json_encode($refund), config('services.paystack.secret_key'))
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('transactions', 1);
    }
}
