<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for generic messages used by the
    | application.
    |
    */

    'request_successful' => 'Request successful.',
    'invalid_credentials' => 'Invalid credentials.:additional_comments',
    'forbidden' => 'You are not authorized to perform this action.',
    'user_verification_otp_message' => 'Thank you for registering with Japa. Your One-Time Password is :otp.',
    'invalid_otp' => 'Invalid OTP.:additional_comments',
    'otp_expired' => 'The OTP has expired. Initiate the resend OTP request to generate a new one.',
    'too_many_otp_requests' => 'Too many One-Time Password requests. Retry in :duration.',
    'user_already_verified' => 'You have already been verified.',
    'user_verification_locked_by_failed_otp' => 'User verification locked. Retry in :duration.',
    'user_unregistered' => 'This credential does not belong to a registered user.',
    'reset_password' => 'Your One-Time Password is :otp. Use it to reset your password.',
    'password_reset_verification_locked_by_failed_otp' => 'Password reset locked. Retry in :duration.',
    'account_locked' => 'Account locked. Retry in :duration.',
    'bvn_already_verified' => 'Your BVN has already been verified.',
    'bvn_already_linked' => 'This BVN is linked to an existing account.',
    'bvn_no_match' => 'No match for BVN details provided.',
    'bvn_otp_message' => 'Your Japa One-Time Password is :otp. Use this to complete your BVN verification.',
    'user_unverified' => 'Your account has not been verified.',
    'bvn_verification_locked_by_failed_otp' => 'BVN verification locked. Retry in :duration.',
    'bvn_not_tied_to_account' => 'This BVN is not tied to your registered account.',
    'bvn_image_already_verified' => 'Your BVN image has already been verified.',
    'face_matching_failure' => 'Uploaded image could not be face matched.',
    'multiple_faces_detected' => 'Multiple faces were detected while verifying BVN image.',
    'unmatching_faces' => 'The verification was not successful. Please try again.',
    'insufficient_face_match' => 'Uploaded image face match not sufficient. Please upload another image.',
    'bvn_unverified' => 'Your BVN has not been verified.',
    'ongoing_application' => 'You have an application already being processed.',
    'no_pending_application' => 'You have no pending application.',
    'onboarding_payment_successful' => 'Your payment for document verification was successful. Your application is currently in review.',
    'email_unverified' => 'Your email has not been verified.',
    'email_already_verified' => 'Your email has already been verified.',
    'email_verification_failed' => 'Email verification failed. Please click on the link set to your email address.',
    'invalid_account_name' => 'Name on account does not match expected name given.',
    'account_taken' => 'This account has already been added by another user.',
    'add_account_forbidden' => 'You cannot add this account as it does not bear your name.',
    'completed_application' => 'This application has already been completed.',
    'international_passport_no_match' => 'No international passport match with provided details.',
    'payment_successful_and_application_closed' => 'Payment successful. Your Proof of fund application has been closed.',
    'payment_successful_but_application_still_open' => 'Payment successful. You still owe :remaining_amount in your Proof of fund application.'

];
