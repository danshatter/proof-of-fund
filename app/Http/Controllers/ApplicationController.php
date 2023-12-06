<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\{ApplicationPaymentRequest, StoreApplicationRequest, UpdateApplicationRequest, UpdateApplicationStatusRequest};
use App\Models\{Application, Option, Tenure, Transaction};
use App\Services\LoanCalculator as LoanCalculatorService;
use App\Services\Phone\Nigeria as NigerianPhone;
use App\Services\ThirdParty\{
    Paystack as PaystackService,
    QoreId as QoreIdService
};
use App\Exceptions\{CompletedApplicationException, InternationalPassportNoMatchException, NoPendingApplicationException, OngoingApplicationException};

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $applications = Application::with(['user'])
                                ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $applications);
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
    public function store(StoreApplicationRequest $request)
    {
        // Check if the user already has an ongoing application
        if ($request->user()
                    ->applications()
                    ->whereIn('status', [
                        Application::PENDING,
                        Application::IN_REVIEW,
                        Application::ACCEPTED,
                        Application::OPEN,
                    ])
                    ->exists()) {
            throw new OngoingApplicationException;
        }

        $data = $request->validated();

        // Get the option
        $option = Option::find($data['option_id']);

        // Get the tenure
        $tenure = Tenure::find($data['tenure_id']);

        $loanDetails = app()->make(LoanCalculatorService::class)->schedule($data['amount'], $option, $tenure);

        // The disk to use for storage
        $imageDriver = config('filesystems.default');

        /**
         * Store the image for the proof of residence and associated variables
         */
        $proofOfResidenceImage = $data['proof_of_residence_image']->store('proof-of-residences', $imageDriver);

        // Get the URL for the proof of residence image
        $proofOfResidenceImageUrl =  Storage::disk($imageDriver)
                                            ->url($proofOfResidenceImage);

        /**
         * Store the image for the international passport and associated variables
         */
        $internationalPassportImage = $data['international_passport_image']->store('international-passports', $imageDriver);
        
        // Get the URL for the international passport image
        $internationalPassportImageUrl = Storage::disk($imageDriver)
                                                ->url($internationalPassportImage);

        // Create the Proof of Fund application
        $application = $request->user()
                            ->applications()
                            ->create([
                                'amount' => $data['amount'],
                                'amount_remaining' => $loanDetails['total_payment_amount'],
                                'tenure' => $loanDetails['duration'],
                                'type' => $option->type,
                                'interest' => $loanDetails['interest'],
                                'state_of_origin' => $data['state_of_origin'],
                                'residential_address' => $data['residential_address'],
                                'state_of_residence' => $data['state_of_residence'],
                                'travel_purpose' => $data['travel_purpose'],
                                'travel_destination' => $data['travel_destination'],
                                'proof_of_residence_image' => $proofOfResidenceImage,
                                'proof_of_residence_image_url' => $proofOfResidenceImageUrl,
                                'proof_of_residence_image_driver' => $imageDriver,
                                'international_passport_number' => $data['international_passport_number'],
                                'international_passport_expiry_date' => $data['international_passport_expiry_date'],
                                'international_passport_image' => $internationalPassportImage,
                                'international_passport_image_url' => $internationalPassportImageUrl,
                                'international_passport_image_driver' => $imageDriver,
                                'guarantor' => [
                                    'first_name' => $data['guarantor_first_name'],
                                    'last_name' => $data['guarantor_last_name'],
                                    'phone' => app()->make(NigerianPhone::class)->convert($data['guarantor_phone']),
                                    'email' => $data['guarantor_email'] ?? null
                                ],
                                'travel_sponsor' => (isset($data['travel_sponsor_first_name']) ||
                                                    isset($data['travel_sponsor_last_name']) ||
                                                    isset($data['travel_sponsor_phone']) ||
                                                    isset($data['travel_sponsor_email'])
                                ) ? [
                                    'first_name' => $data['travel_sponsor_first_name'] ?? null,
                                    'last_name' => $data['travel_sponsor_last_name'] ?? null,
                                    'phone' => isset($data['travel_sponsor_phone']) ? app()->make(NigerianPhone::class)->convert($data['travel_sponsor_phone']) : null,
                                    'email' => $data['travel_sponsor_email'] ?? null
                                ] : null,
                                'details' => collect($loanDetails['payment_details'])->map(fn($paymentDetail) => array_merge($paymentDetail, [
                                    'amount_remaining' => $paymentDetail['amount'],
                                    'status' => Application::INSTALLMENT_PENDING,
                                    'closed_at' => null
                                ]))
                            ]);

        return $this->sendSuccess('Application was successfully created.', 201, $application);
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application)
    {
        $application->load(['user']);

        return $this->sendSuccess(__('app.request_successful'), 200, $application);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Application $application)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateApplicationRequest $request, Application $application)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application)
    {
        //
    }

    /**
     * Get the applications of a user
     */
    public function userIndex(Request $request)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $applications = $request->user()
                                ->applications()
                                ->latest()
                                ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $applications);
    }

    /**
     * Get an application of a user
     */
    public function userShow(Request $request, $applicationId)
    {
        $application = $request->user()
                            ->applications()
                            ->findOrFail($applicationId);

        return $this->sendSuccess(__('app.request_successful'), 200, $application);
    }

    /**
     * Make a payment for the onboarding of a user
     */
    public function onboardingPayment(Request $request)
    {
        // We get a pending application the user has
        $pendingApplication = $request->user()
                                    ->applications()
                                    ->where('status', Application::PENDING)
                                    ->first();
        
        // Check if the user has a pending application
        if (!isset($pendingApplication)) {
            throw new NoPendingApplicationException;
        }

        // Initialize a transaction
        $initializeTransaction = app()->make(PaystackService::class)->initializeTransaction([
            'amount' => config('japa.document_verification_and_service_fee'),
            'email' => $request->user()->email,
            'metadata' => [
                'application_id' => $pendingApplication->id,
                'type' => Transaction::ONBOARDING
            ],
            'channels' => [
                'card'
            ],
        ]);

        return $this->sendSuccess('Transaction initialized successfully.', 200, $initializeTransaction['data']);
    }

    /**
     * Get the transactions of an application
     */
    public function userTransactions(Request $request, $applicationId)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $application = $request->user()
                            ->applications()
                            ->findOrFail($applicationId);

        $transactions = $application->transactions()
                                    ->with(['application'])
                                    ->latest()
                                    ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $transactions);
    }

    /**
     * Get a transaction of an application
     */
    public function userTransaction(Request $request, $applicationId, $transactionId)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $application = $request->user()
                            ->applications()
                            ->findOrFail($applicationId);

        $transaction = $application->transactions()
                                ->with(['application'])
                                ->findOrFail($transactionId);

        return $this->sendSuccess(__('app.request_successful'), 200, $transaction);
    }

    /**
     * Update the status of an application
     */
    public function status(UpdateApplicationStatusRequest $request, Application $application)
    {
        $data = $request->validated();

        $application->forceFill([
            'status' => $data['status']
        ])->save();

        return $this->sendSuccess('Application status updated successfully.', 200, [
            'status' => $application->status
        ]);
    }

    /**
     * Initiate the payment of an application
     */
    public function payment(ApplicationPaymentRequest $request, $applicationId)
    {
        $application = $request->user()
                            ->applications()
                            ->whereIn('status', [
                                Application::OPEN,
                                Application::COMPLETED
                            ])
                            ->findOrFail($applicationId);

        if ($application->status === Application::COMPLETED) {
            throw new CompletedApplicationException;
        }

        // Check if the application is not open or overdue
        $data = $request->validated();

        // Initialize the payment
        $initializeTransaction = app()->make(PaystackService::class)->initializeTransaction([
            'amount' => $data['amount'],
            'email' => $request->user()->email,
            'metadata' => [
                'application_id' => $application->id,
                'type' => Transaction::PAYMENT
            ],
        ]);

        return $this->sendSuccess('Payment initialized successfully.', 200, $initializeTransaction);
    }

    /**
     * Get the passport details tied to a Proof of Fund application 
     */
    public function passportDetails(Application $application, Request $request)
    {
        $application->load(['user']);

        // Get the passport details
        $passportDetails = app()->make(QoreIdService::class)->passportDetails($application->international_passport_number, [
            'firstname' => $application->user->first_name,
            'lastname' => $application->user->last_name
        ]);

        // Check if there is a result
        if (!isset($passportDetails['passport_ng'])) {
            throw new InternationalPassportNoMatchException;
        }

        return $this->sendSuccess(__('app.request_successful'), 200, [
            'first_name' => $passportDetails['passport_ng']['firstname'],
            'last_name' => $passportDetails['passport_ng']['lastname'],
            'middle_name' => $passportDetails['passport_ng']['middlename'],
            'date_of_birth' => Carbon::parse($passportDetails['passport_ng']['birthdate'])->format('Y-m-d'),
            'photo' => $passportDetails['passport_ng']['photo'],
            'gender' => strtoupper($passportDetails['passport_ng']['gender']),
            'issued_at' => strtoupper($passportDetails['passport_ng']['issuedAt']),
            'issued_date' => Carbon::parse($passportDetails['passport_ng']['issuedDate'])->format('Y-m-d'),
            'expiry_date' => Carbon::parse($passportDetails['passport_ng']['expiryDate'])->format('Y-m-d'),
            'passport_number' => $passportDetails['passport_ng']['passportNo']
        ]);
    }

    /**
     * Get the transactions
     */
    public function transactions(Request $request, Application $application)
    {
        $perPage = $request->query('per_page') ?? config('japa.per_page');

        $transactions = $application->transactions()
                                    ->latest()
                                    ->paginate($perPage);

        return $this->sendSuccess(__('app.request_successful'), 200, $transactions);
    }

    /**
     * Get a transaction
     */
    public function transaction(Request $request, Application $application, Transaction $transaction)
    {
        return $this->sendSuccess(__('app.request_successful'), 200, $transaction);
    }
}
