<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Expiration Time
    |--------------------------------------------------------------------------
    |
    | The number of seconds before the expiration of an OTP
    |
    */

    'otp_expiration_time' => 600,

    /*
    |--------------------------------------------------------------------------
    | OTP Digits
    |--------------------------------------------------------------------------
    |
    | The number of digits for an OTP
    |
    */

    'otp_digits' => 6,

    /*
    |--------------------------------------------------------------------------
    | OTP Attempts Lock Time
    |--------------------------------------------------------------------------
    |
    | The number of seconds to lock the OTP verification engine when maximum
    | attempts has been exceeded
    |
    */

    'otp_attempts_lock_time' => 172800,

    /*
    |--------------------------------------------------------------------------
    | Maximum Times To Resend OTP
    |--------------------------------------------------------------------------
    |
    | The maximum number of times to resend OTP
    |
    */

    'maximum_times_to_resend_otp' => 3,

    /*
    |--------------------------------------------------------------------------
    | Maximum Times To Retry OTP Verification
    |--------------------------------------------------------------------------
    |
    | The maximum number of times to retry a failed OTP verification
    |
    */

    'maximum_times_to_retry_otp_verification' => 3,

    /*
    |--------------------------------------------------------------------------
    | Maximum Login Attempts
    |--------------------------------------------------------------------------
    |
    | The maximum login attempts before the account is blocked
    |
    */

    'maximum_login_attempts' => 4,

    /*
    |--------------------------------------------------------------------------
    | Login Attempts Lock Time
    |--------------------------------------------------------------------------
    |
    | The number of seconds to lock the account when maximum login attempts
    | has been exceeded
    |
    */

    'login_attempts_lock_time' => 86400,

    /*
    |--------------------------------------------------------------------------
    | BVN Hash Secret
    |--------------------------------------------------------------------------
    |
    | The secret used to hash the BVN
    |
    */
    'bvn_hash_secret' => env('BVN_HASH_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Face Match Minimum Percentage
    |--------------------------------------------------------------------------
    |
    | The minimum percentage for passing face matching
    |
    */

    'face_match_minimum_percentage' => 90,

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone used by the application
    |
    */

    'local_timezone' => 'Africa/Lagos',

    /*
    |--------------------------------------------------------------------------
    | Document Verification And Service Fee
    |--------------------------------------------------------------------------
    |
    | The document verification and sevice fee
    |
    */

    'document_verification_and_service_fee' => 350000,

    /*
    |--------------------------------------------------------------------------
    | Per Page Result Set
    |--------------------------------------------------------------------------
    |
    | The default number of items return in pagination results
    |
    */

    'per_page' => 20,

        /*
    |--------------------------------------------------------------------------
    | Currency Code
    |--------------------------------------------------------------------------
    |
    | The currency code to be used by the application
    |
    */

    'currency_code' => 'NGN',

    /*
    |--------------------------------------------------------------------------
    | Email Verification Hash
    |--------------------------------------------------------------------------
    |
    | The hash to use for email verification
    |
    */

    'email_verification_hash' => env('EMAIL_VERIFICATION_HASH'),

];
