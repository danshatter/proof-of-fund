<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Hash};
use Aws\Laravel\AwsFacade;
use Aws\Exception\AwsException;
use App\Models\{Activity, User, Role};
use App\Notifications\{AgentVerificationNotification, BvnVerificationNotification, UserVerificationNotification, ResetPasswordNotification};
use App\Services\Phone\Nigeria as NigerianPhone;
use App\Exceptions\{AccountLockedException, BvnAlreadyVerifiedException, BvnImageAlreadyVerifiedException, BvnLinkedToExistingAccountException, BvnNoMatchException, BvnNotTiedToAccountException, BvnVerificationLockedByFailedOtpException, CustomException, EmailAlreadyVerifiedException, FaceMatchingFailureException, InsufficientFaceMatchException, InvalidCredentialsException, InvalidOtpException, MultipleFacesDetectedException, OtpExpiredException, PasswordResetLockedByFailedOtpException, UnmatchingFacesException, UserAlreadyVerifiedException, UserUnregisteredException, UserVerificationLockedByFailedOtpException};
use App\Http\Requests\{AdminLoginRequest, AdminRegisterRequest, ChangePasswordRequest, ConfirmBvnImageRequest, ConfirmBvnRequest, ForgotPasswordRequest, LoginRequest, AgentRegisterRequest, VerifyUserRequest, RegisterRequest, ResendBvnOtpRequest, ResendVerificationEmailRequest, ResendUserOtpRequest, ResetPasswordRequest, VerifyBvnRequest};
use App\Http\Resources\{AdminResource, AgencyResource, IndividualAgentResource, UserResource};
use App\Services\ThirdParty\QoreId as QoreIdService;

class AuthController extends Controller
{
    /**
     * Register a user
     */
    public function register(RegisterRequest $registerRequest)
    {
        $data = $registerRequest->validated();

        $user = DB::transaction(function() use ($data) {
            // Create the user
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => app()->make(NigerianPhone::class)->convert($data['phone']),
                'date_of_birth' => Carbon::parse($data['date_of_birth'])->format('Y-m-d'),
                'password' => Hash::make($data['password'])
            ]);

            // Generate the OTP for the user
            $user->generateOtp();

            // Check if the user signed up using a referral code
            if (isset($data['referral_code'])) {
                $referrer = User::whereIn('role_id', [
                                    Role::INDIVIDUAL_AGENT,
                                    Role::AGENCY
                                ])
                                ->where('referral_code', $data['referral_code'])
                                ->first();
                
                // Set the user to the referrer
                $user->forceFill([
                    'referred_by' => $referrer->id
                ])->save();

                // Log the sign up activity to the referrer
                $referrer->activities()
                        ->create([
                            'message' => 'A new user just signed up using your referral code',
                            'type' => Activity::SIGN_UP,
                            'metadata' => [
                                'user_id' => $user->id
                            ]
                        ]);
            }

            return $user;
        });

        // Send user verification notification
        $user->notify(new UserVerificationNotification);

        return $this->sendSuccess('User registration successful. Use the OTP sent to your phone number to verify your account.', 201);
    }

    /**
     * Register as an agent
     */
    public function registerAgent(AgentRegisterRequest $agentRegisterRequest)
    {
        $data = $agentRegisterRequest->validated();

        $agent = DB::transaction(function() use ($data) {
            switch ($data['register_as']) {
                case 'INDIVIDUAL':
                    // Create the individual agent
                    $agent = User::create([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'phone' => app()->make(NigerianPhone::class)->convert($data['phone']),
                        'date_of_birth' => Carbon::parse($data['date_of_birth'])->format('Y-m-d'),
                        'address' => $data['address'],
                        'request_message' => $data['request_message'],
                        'password' => Hash::make($data['password'])
                    ]);

                    // Change the user role to an individual agent
                    $agent->forceFill([
                        'role_id' => Role::INDIVIDUAL_AGENT
                    ])->save();
                break;
    
                case 'AGENCY':
                    // Create the agency agent
                    $agent = User::create([
                        'business_name' => $data['business_name'],
                        'business_website' => $data['business_website'],
                        'business_state' => $data['business_state'],
                        'email' => $data['email'],
                        'phone' => app()->make(NigerianPhone::class)->convert($data['phone']),
                        'address' => $data['address'],
                        'request_message' => $data['request_message'],
                        'password' => Hash::make($data['password'])
                    ]);

                    // Change the user role to an agency agent
                    $agent->forceFill([
                        'role_id' => Role::AGENCY
                    ])->save();
                break;
                
                default:
                    throw new CustomException('Invalid agent registration type', 503);
                break;
            }

            // Create the verification hash
            $agent->forceFill([
                'email_verification' => hash_hmac('sha512', "{$agent->email}-{$agent->id}", config('japa.email_verification_hash'))
            ])->save();

            // Create the balance of the agent
            $agent->balance()->updateOrCreate([]);

            // Generate referral code for the agent
            $agent->generateReferralCode();

            return $agent;
        });

        // Send agent verification notification
        $agent->notify(new AgentVerificationNotification);

        return $this->sendSuccess('Agent registration successful. Check your email to verify your account.', 201);
    }

    /**
     * Login as a user
     */
    public function login(LoginRequest $loginRequest)
    {
        $data = $loginRequest->validated();

        // Get the user
        $user = User::with(['camouflage', 'role'])
                    ->whereIn('role_id', [
                        Role::USER,
                        Role::INDIVIDUAL_AGENT,
                        Role::AGENCY
                    ])
                    ->where(fn($query) => $query->where('phone', app()->make(NigerianPhone::class)->convert($data['username']))
                                                ->orWhere('email', $data['username']))
                    ->first();

        // Check if the user exists
        if (!isset($user)) {
            throw new InvalidCredentialsException;
        }

        /**
         * We check if they the user been locked from their account previously due to the maximum login
         * attempts being exceeded
         */
        if (isset($user->locked_due_to_failed_login_attempts_at)) {
            /**
             * We check if the user is still in the lock out period
             */
            if ($user->locked_due_to_failed_login_attempts_at->addSeconds(config('japa.login_attempts_lock_time')) > now()) {
                throw new AccountLockedException($user);
            }

            // Lock out time exceeded. We reset the user's login attempt
            $user->forceFill([
                'failed_login_attempts' => null,
                'locked_due_to_failed_login_attempts_at' => null
            ])->save();
        }

        // Check if the password matches
        if (!Hash::check($data['password'], $user->password)) {
            DB::transaction(function() use ($user) {
                $failedAttempts = $user->failed_login_attempts ?? 0;

                // Increase the failed attempts
                $user->forceFill([
                    'failed_login_attempts' => $failedAttempts + 1,
                ])->save();
    
                if ($user->failed_login_attempts >= config('japa.maximum_login_attempts')) {
                    $user->forceFill([
                        'locked_due_to_failed_login_attempts_at' => now()
                    ])->save();
                }
            });

            throw new InvalidCredentialsException(config('japa.maximum_login_attempts') - $user->failed_login_attempts);
        }

        // Login the user by creating a token
        $token = $user->createToken('token-'.$user->id.'-'.uniqid())->plainTextToken;

        // Reset login attempts
        $user->forceFill([
            'failed_login_attempts' => null,
            'locked_due_to_failed_login_attempts_at' => null
        ])->save();

        switch ($user->role_id) {
            case Role::USER:
                $resource = new UserResource($user);
            break;

            case Role::INDIVIDUAL_AGENT:
                $resource = new IndividualAgentResource($user);
            break;

            case Role::AGENCY:
                $resource = new AgencyResource($user);
            break;
            
            default:
                throw new CustomException('Invalid agent registration type', 503);
            break;
        }

        return $this->sendSuccess('Login successful.', 200, [
            'user' => $resource,
            'token' => $token
        ]);
    }

    /**
     * Verify a user
     */
    public function verifyUser(VerifyUserRequest $verifyUserRequest)
    {
        $data = $verifyUserRequest->validated();

        // Get the user
        $user = User::users()
                    ->where('phone', app()->make(NigerianPhone::class)->convert($data['phone']))
                    ->first();
        
        // Check if the user exists
        if (!isset($user)) {
            throw new InvalidOtpException;
        }

        // Check if the user is already verified
        if ($user->isVerified()) {
            throw new UserAlreadyVerifiedException;
        }

        /**
         * We check if the user has completed the lock out period during failed OTP verification
         */
        if (isset($user->locked_due_to_failed_verification_at)) {
            /**
             * We check if the user is still in the lock out period
             */
            if ($user->locked_due_to_failed_verification_at->addSeconds(config('japa.otp_attempts_lock_time')) > now()) {
                throw new UserVerificationLockedByFailedOtpException($user);
            }

            // Lock out time exceeded. We unlock for OTP validation
            $user->unlockOtpValidation();
        }

        // Check if the OTP has expired
        if ($user->hasExpiredOtp()) {
            throw new OtpExpiredException;
        }

        // Check if the OTP matches
        if ($data['otp'] !== $user->verification) {
            DB::transaction(function() use ($user) {
                $failedAttempts = $user->failed_verification_attempts ?? 0;

                // Increase the failed attempts
                $user->forceFill([
                    'failed_verification_attempts' => $failedAttempts + 1,
                ])->save();
    
                if ($user->failed_verification_attempts >= config('japa.maximum_times_to_retry_otp_verification')) {
                    $user->forceFill([
                        'locked_due_to_failed_verification_at' => now()
                    ])->save();
                }
            });

            throw new InvalidOtpException(config('japa.maximum_times_to_retry_otp_verification') - $user->failed_verification_attempts);
        }

        // Verify the user
        $user->markAsVerified();

        // Clear the lockout for OTP validation
        $user->unlockOtpValidation();

        return $this->sendSuccess('User verification successful.');
    }

    /**
     * Resend OTP for password resets and User verification
     */
    public function resendUserOtp(ResendUserOtpRequest $resendUserOtpRequest)
    {
        $data = $resendUserOtpRequest->validated();

        // Get the user
        $user = User::users()
                    ->where('phone', app()->make(NigerianPhone::class)->convert($data['phone']))
                    ->first();

        if (!isset($user)) {
            throw new UserUnregisteredException;
        }

        // Check if the user is already verified
        if ($user->isVerified()) {
            throw new UserAlreadyVerifiedException;
        }

        // Generate an OTP for the user
        $user->generateOtp();

        // Send OTP verification
        $user->notify(new UserVerificationNotification);

        return $this->sendSuccess('OTP sent to "'.$user->phone.'".');
    }

    /**
     * Resend email for agent verification
     */
    public function resendVerificationEmail(ResendVerificationEmailRequest $resendVerificationEmailRequest)
    {
        $data = $resendVerificationEmailRequest->validated();

        // Get the agent
        $agent = User::whereIn('role_id', [
                        Role::INDIVIDUAL_AGENT,
                        Role::AGENCY
                    ])
                    ->where('email', $data['email'])
                    ->first();

        if (!isset($agent)) {
            throw new UserUnregisteredException;
        }

        // Check if the user is already verified
        if ($agent->hasVerifiedEmail()) {
            throw new EmailAlreadyVerifiedException;
        }

        if (!isset($agent->email_verification)) {
            // Create the verification hash
            $agent->forceFill([
                'email_verification' => hash_hmac('sha512', "{$agent->email}-{$agent->id}", config('japa.email_verification_hash'))
            ])->save();
        }

        // Send agent verification notification
        $agent->notify(new AgentVerificationNotification);

        return $this->sendSuccess('Please check your mail to verify your account.');
    }

    /**
     * Initiate forgot password process
     */
    public function forgotPassword(ForgotPasswordRequest $forgotPasswordRequest)
    {
        $data = $forgotPasswordRequest->validated();

        // Get the user
        $user = User::whereIn('role_id', [
                        Role::USER,
                        Role::INDIVIDUAL_AGENT,
                        Role::AGENCY
                    ])
                    ->where('phone', app()->make(NigerianPhone::class)->convert($data['phone']))
                    ->first();

        if (!isset($user)) {
            throw new UserUnregisteredException;
        }

        // Generate an OTP for the user
        $user->generateOtp();

        // Send OTP verification
        $user->notify(new ResetPasswordNotification);

        return $this->sendSuccess('Use the OTP sent to "'.$user->phone.'" to reset your password.');
    }

    /**
     * Reset the password of a user
     */
    public function resetPassword(ResetPasswordRequest $resetPasswordRequest)
    {
        $data = $resetPasswordRequest->validated();

        // Get the user
        $user = User::whereIn('role_id', [
                        Role::USER,
                        Role::INDIVIDUAL_AGENT,
                        Role::AGENCY
                    ])
                    ->where('phone', app()->make(NigerianPhone::class)->convert($data['phone']))
                    ->first();

        // Check if the user exists
        if (!isset($user)) {
            throw new InvalidOtpException;
        }

        /**
         * We check if the user has completed the lock out period during failed OTP verification
         */
        if (isset($user->locked_due_to_failed_verification_at)) {
            /**
             * We check if the user is still in the lock out period
             */
            if ($user->locked_due_to_failed_verification_at->addSeconds(config('japa.otp_attempts_lock_time')) > now()) {
                throw new PasswordResetLockedByFailedOtpException($user);
            }

            // Lock out time exceeded. We unlock for OTP validation
            $user->unlockOtpValidation();
        }

        // Check if the OTP has expired
        if ($user->hasExpiredOtp()) {
            throw new OtpExpiredException;
        }

        // Check if the OTP matches
        if ($data['otp'] !== $user->verification) {
            DB::transaction(function() use ($user) {
                $failedAttempts = $user->failed_verification_attempts ?? 0;

                // Increase the failed attempts
                $user->forceFill([
                    'failed_verification_attempts' => $failedAttempts + 1,
                ])->save();
    
                if ($user->failed_verification_attempts >= config('japa.maximum_times_to_retry_otp_verification')) {
                    $user->forceFill([
                        'locked_due_to_failed_verification_at' => now()
                    ])->save();
                }
            });

            throw new InvalidOtpException(config('japa.maximum_times_to_retry_otp_verification') - $user->failed_verification_attempts);
        }

        // Reset the password of the user
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'verification' => null,
            'verification_expires_at' => null
        ])->save();

        // Clear the lockout for OTP validation
        $user->unlockOtpValidation();

        return $this->sendSuccess('Password reset successfully.');
    }

    /**
     * Confirm the BVN of a user
     */
    public function confirmBvn(ConfirmBvnRequest $confirmBvnRequest)
    {
        $data = $confirmBvnRequest->validated();

        // Load the BVN relationship
        $confirmBvnRequest->user()->load(['camouflage']);

        // Check if the BVN is already verified
        if (isset($confirmBvnRequest->user()->camouflage) && $confirmBvnRequest->user()->camouflage->isVerified()) {
            throw new BvnAlreadyVerifiedException;
        }

        // Check if a user already has a verified account using this BVN
        if (User::users()
                ->whereHas('camouflage', fn($query) => $query->where('confidential_hash', hash_hmac('sha256', $data['bvn'], config('japa.bvn_hash_secret')))
                                                            ->whereNotNull('verified_at'))
                ->exists()) {
            throw new BvnLinkedToExistingAccountException;
        }

        // Make the request to verify the BVN
        $body = app()->make(QoreIdService::class)->bvnVerification($data['bvn'], [
            'firstname' => $confirmBvnRequest->user()->first_name,
            'lastname' => $confirmBvnRequest->user()->last_name,
        ]);

        // Check if the BVN is verified with the details provided
        if (!isset($body['bvn'])) {
            throw new BvnNoMatchException;
        }

        // Get the formatted phone number
        $formattedPhoneNumber = app()->make(NigerianPhone::class)->convert($body['bvn']['phone']);

        /**
         * We check if the phone number used to register in the application is the same as the
         * phone number registered on the BVN
         */
        if ($confirmBvnRequest->user()->phone === $formattedPhoneNumber) {
            // Create the record of the BVN
            $camouflage = DB::transaction(function() use ($confirmBvnRequest, $body, $formattedPhoneNumber) {
                $camouflage = $confirmBvnRequest->user()->camouflage()->updateOrCreate([
                    
                ], [
                    'first_name' => $body['bvn']['firstname'],
                    'middle_name' => $body['bvn']['middlename'] ?? null,
                    'last_name' => $body['bvn']['lastname'],
                    'phone' => $formattedPhoneNumber,
                    'gender' => strtoupper($body['bvn']['gender']),
                    'date_of_birth' => isset($body['bvn']['birthdate']) ? Carbon::parse($body['bvn']['birthdate'])->format('Y-m-d') : null,
                    'nationality' => $body['bvn']['nationality'] ?? null,
                    'confidential' => $body['bvn']['bvn'],
                    'confidential_hash' => hash_hmac('sha256', $body['bvn']['bvn'], config('japa.bvn_hash_secret')),
                    'image' => $body['bvn']['photo']
                ]);

                // Verify the BVN
                $camouflage->markAsVerified();

                return $camouflage;
            });

            return $this->sendSuccess('BVN verification successful.', 200, $camouflage);
        } else {
            // Create the record of the BVN
            $camouflage = DB::transaction(function() use ($confirmBvnRequest, $body, $formattedPhoneNumber) {
                $camouflage = $confirmBvnRequest->user()->camouflage()->updateOrCreate([
                    
                ], [
                    'first_name' => $body['bvn']['firstname'],
                    'middle_name' => $body['bvn']['middlename'] ?? null,
                    'last_name' => $body['bvn']['lastname'],
                    'phone' => $formattedPhoneNumber,
                    'gender' => strtoupper($body['bvn']['gender']),
                    'date_of_birth' => isset($body['bvn']['birthdate']) ? Carbon::parse($body['bvn']['birthdate'])->format('Y-m-d') : null,
                    'nationality' => $body['bvn']['nationality'] ?? null,
                    'confidential' => $body['bvn']['bvn'],
                    'confidential_hash' => hash_hmac('sha256', $body['bvn']['bvn'], config('japa.bvn_hash_secret')),
                    'image' => $body['bvn']['photo']
                ]);

                // Generate an OTP for BVN verification
                $camouflage->generateOtp();

                return $camouflage;
            });

            // Send an OTP to the number belonging to the BVN
            $confirmBvnRequest->user()->notify(new BvnVerificationNotification($camouflage));

            return $this->sendSuccess('OTP sent to ****'.substr($camouflage->phone, -4).'. Use it to verify your BVN.');
        }
    }

    /**
     * Confirm the BVN image of a user
     */
    public function confirmBvnImage(ConfirmBvnImageRequest $request)
    {
        $data = $request->validated();

        // Load the camouflage relationship
        $request->user()->load(['camouflage']);

        if (!isset($request->user()->camouflage)) {
            throw new CustomException('BVN details for user does not exist.', 400);
        }

        // Check if the BVN image has already been verified
        if (isset($request->user()->camouflage->image_verified_at)) {
            throw new BvnImageAlreadyVerifiedException;
        }

        /**
         * Due to the fact that some users might not have BVN images, we make the BVN request again to store
         * the images
         */
        if (!isset($request->user()->camouflage->image)) {
            // Make the request to verify the BVN
            $body = app()->make(QoreIdService::class)->bvnVerification($request->user()->camouflage->confidential, [
                'firstname' => $request->user()->camouflage->first_name,
                'lastname' => $request->user()->camouflage->last_name,
            ]);

            // Check if the BVN is verified with the details provided. This should always pass
            if (!isset($body['bvn'])) {
                throw new BvnNoMatchException;
            }

            // Store the BVN image
            $request->user()->camouflage->forceFill([
                'image' => $body['bvn']['photo']
            ])->save();
        }

        // Store the uploaded image in base64 for the user
        $request->user()->forceFill([
            'image' => base64_encode($data['image']->getContent())
        ])->save();

        // Compare the BVN image with the uploaded image
        $rekognitionClient = AwsFacade::createClient('rekognition', [
            'version' => 'latest'
        ]);

        try {
            $result = $rekognitionClient->compareFaces([
                'SourceImage' => [
                    'Bytes' => base64_decode($request->user()->camouflage->image),
                    
                ],
                'TargetImage' => [
                    'Bytes' => base64_decode($request->user()->image),
                ],
            ]);
            
            $faceMatches = $result->get('FaceMatches');

            // Check if there are no face matches
            if (empty($faceMatches)) {
                throw new UnmatchingFacesException;
            }

            // We count the number of images to make sure that we only have one image
            if (count($faceMatches) > 1) {
                throw new MultipleFacesDetectedException;
            }

            // Get the first instance of the face matching
            $faceMatch = $faceMatches[0];

            // We check if the faces match sufficiently
            if ($faceMatch['Similarity'] < config('japa.face_match_minimum_percentage')) {
                throw new InsufficientFaceMatchException;
            }

            // If the BVN is not verified, we verify the BVN
            if (!$request->user()->camouflage->isVerified()) {
                $request->user()->camouflage->markAsVerified();
            }

            // Verify the image
            $request->user()->camouflage->forceFill([
                'image_verified_at' => now()
            ])->save();

            return $this->sendSuccess('BVN image verification successful.');
        } catch (AwsException $e) {
            info('AWS rekognition error: '.$e->getMessage());
            
            // Request failed
            throw new FaceMatchingFailureException;
        }
    }

    /**
     * Verify the BVN of a user
     */
    public function verifyBvn(VerifyBvnRequest $verifyBvnRequest)
    {
        $data = $verifyBvnRequest->validated();

        // Get the BVN record of the user based on the input
        $camouflage = $verifyBvnRequest->user()
                                    ->camouflage()
                                    ->firstWhere('phone', app()->make(NigerianPhone::class)->convert($data['phone']));

        // Check if the BVN record exists
        if (!isset($camouflage)) {
            throw new InvalidOtpException;
        }

        // Check if the BVN is already verified
        if ($camouflage->isVerified()) {
            throw new BvnAlreadyVerifiedException;
        }

        // We check for the unlikely case that the BVN is linked to a user other than the authenticated user
        if (User::users()
                ->whereHas('camouflage', fn($query) => $query->where('phone', app()->make(NigerianPhone::class)->convert($data['phone']))
                                                            ->whereNotNull('verified_at'))
                ->exists()) {
            throw new BvnLinkedToExistingAccountException;
        }

        /**
         * We check if the camouflage has completed the lock out period during failed OTP verification
         */
        if (isset($camouflage->locked_due_to_failed_verification_at)) {
            /**
             * We check if the camouflage is still in the lock out period
             */
            if ($camouflage->locked_due_to_failed_verification_at->addSeconds(config('japa.otp_attempts_lock_time')) > now()) {
                throw new BvnVerificationLockedByFailedOtpException($camouflage);
            }

            // Lock out time exceeded. We unlock for OTP validation
            $camouflage->unlockOtpValidation();
        }

        // Check if the OTP has expired
        if ($camouflage->hasExpiredOtp()) {
            throw new OtpExpiredException;
        }

        // Check if the OTP matches
        if ($data['otp'] !== $camouflage->verification) {
            DB::transaction(function() use ($camouflage) {
                $failedAttempts = $camouflage->failed_verification_attempts ?? 0;

                // Increase the failed attempts
                $camouflage->forceFill([
                    'failed_verification_attempts' => $failedAttempts + 1,
                ])->save();
    
                if ($camouflage->failed_verification_attempts >= config('japa.maximum_times_to_retry_otp_verification')) {
                    $camouflage->forceFill([
                        'locked_due_to_failed_verification_at' => now()
                    ])->save();
                }
            });

            throw new InvalidOtpException(config('japa.maximum_times_to_retry_otp_verification') - $camouflage->failed_verification_attempts);
        }

        // Verify the BVN
        $camouflage->markAsVerified();

        // Clear the lockout for OTP validation
        $camouflage->unlockOtpValidation();

        return $this->sendSuccess('BVN verification successful.', 200, $camouflage);
    }

    /**
     * Resend OTP for BVN verification
     */
    public function resendBvnOtp(ResendBvnOtpRequest $resendBvnOtpRequest)
    {
        $data = $resendBvnOtpRequest->validated();

        // Get the BVN record
        $camouflage = $resendBvnOtpRequest->user()
                                        ->camouflage()
                                        ->firstWhere('confidential_hash', hash_hmac('sha256', $data['bvn'], config('japa.bvn_hash_secret')));

        // Check if the record does not exist
        if (!isset($camouflage)) {
            throw new BvnNotTiedToAccountException;
        }

        // Check if the BVN is already verified
        if ($camouflage->isVerified()) {
            throw new BvnAlreadyVerifiedException;
        }

        // We check for the unlikely case that the BVN is linked to a user other than the authenticated user
        if (User::users()
                ->whereHas('camouflage', fn($query) => $query->where('confidential_hash', hash_hmac('sha256', $data['bvn'], config('japa.bvn_hash_secret')))
                                                            ->whereNotNull('verified_at'))
                ->exists()) {
            throw new BvnLinkedToExistingAccountException;
        }

        $camouflage->generateOtp();

        // Send an OTP to the number belonging to the BVN
        $resendBvnOtpRequest->user()->notify(new BvnVerificationNotification($camouflage));

        return $this->sendSuccess('OTP sent to ****'.substr($camouflage->phone, -4).'. Use it to verify your BVN.');
    }

    /**
     * Logout a user
     */
    public function logout(Request $request)
    {
        // Logout the authenticated user
        $request->user()->tokens()->delete();

        return $this->sendSuccess('Logout successful.');
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();

        // Change the password
        $request->user()->update([
            'password' => Hash::make($data['password'])
        ]);

        return $this->sendSuccess('Password changed successfully.');
    }

    /**
     * Verify the email of a user
     */
    public function verifyEmail(Request $request)
    {
        // Get the token from the request
        $token = $request->input('token');

        // Check if there is a token
        if (!isset($token)) {
            return __('app.email_verification_failed');
        }

        // Get the user based on the token
        $user = User::whereIn('role_id', [
                        Role::INDIVIDUAL_AGENT,
                        Role::AGENCY
                    ])
                    ->where('email_verification', $token)
                    ->first();

        // Check if there is a user with the verification
        if (!isset($user)) {
            return __('app.email_verification_failed');
        }

        // Check if the email has been verified
        if ($user->hasVerifiedEmail()) {
            return __('app.email_already_verified');
        }

        // Verify the email of the user
        $user->markEmailAsVerified();

        return 'Email verification successful.';
    }

    /**
     * Register an administrator
     */
    public function adminRegister(AdminRegisterRequest $adminRegisterRequest)
    {
        $data = $adminRegisterRequest->validated();

        $admin = DB::transaction(function() use ($data) {
            // Create the admin
            $admin = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => app()->make(NigerianPhone::class)->convert($data['phone']),
                'password' => Hash::make($data['password'])
            ]);

            // Elevate the user to an administrator
            $admin->forceFill([
                'role_id' => Role::ADMINISTRATOR
            ])->save();

            // Verify the email of the administrator
            $admin->markEmailAsVerified();

            return $admin;
        });

        return $this->sendSuccess('Administrator registration successful.', 201);
    }

    /**
     * Login as an administrator
     */
    public function adminLogin(AdminLoginRequest $adminLoginRequest)
    {
        $data = $adminLoginRequest->validated();

        // Get the administrator
        $admin = User::with(['camouflage', 'role'])
                    ->administrators()
                    ->where(fn($query) => $query->where('phone', app()->make(NigerianPhone::class)->convert($data['username']))
                                                ->orWhere('email', $data['username']))
                    ->first();

        // Check if the administrator exists
        if (!isset($admin)) {
            throw new InvalidCredentialsException;
        }

        /**
         * We check if they the user been locked from their account previously due to the maximum login
         * attempts being exceeded
         */
        if (isset($admin->locked_due_to_failed_login_attempts_at)) {
            /**
             * We check if the user is still in the lock out period
             */
            if ($admin->locked_due_to_failed_login_attempts_at->addSeconds(config('japa.login_attempts_lock_time')) > now()) {
                throw new AccountLockedException($admin);
            }

            // Lock out time exceeded. We reset the user's login attempt
            $admin->forceFill([
                'failed_login_attempts' => null,
                'locked_due_to_failed_login_attempts_at' => null
            ])->save();
        }

        // Check if the password matches
        if (!Hash::check($data['password'], $admin->password)) {
            DB::transaction(function() use ($admin) {
                $failedAttempts = $admin->failed_login_attempts ?? 0;

                // Increase the failed attempts
                $admin->forceFill([
                    'failed_login_attempts' => $failedAttempts + 1,
                ])->save();
    
                if ($admin->failed_login_attempts >= config('japa.maximum_login_attempts')) {
                    $admin->forceFill([
                        'locked_due_to_failed_login_attempts_at' => now()
                    ])->save();
                }
            });

            throw new InvalidCredentialsException(config('japa.maximum_login_attempts') - $admin->failed_login_attempts);
        }

        // Login the user by creating a token
        $token = $admin->createToken('token-'.$admin->id.'-'.uniqid())->plainTextToken;

        // Reset login attempts
        $admin->forceFill([
            'failed_login_attempts' => null,
            'locked_due_to_failed_login_attempts_at' => null
        ])->save();

        // If email of the administrator is not verified, we verify the email
        if (!$admin->hasVerifiedEmail()) {
            // Mark email as verified
            $admin->markEmailAsVerified();
        }

        return $this->sendSuccess('Login successful.', 200, [
            'user' => new AdminResource($admin),
            'token' => $token
        ]);
    }
}
