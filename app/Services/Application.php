<?php

namespace App\Services;

use Illuminate\Support\Str;

class Application
{
    /**
     * Generate the referral code of the user
     */
    public function generateReferralCode($user)
    {
        // Get the first three letter of the first name
        $firstNamePrefix = substr($user->first_name, 0, 3);

        // Generate a random five digit string
        $randomSuffix = Str::random(5);

        // Add some extra characters to unsure uniqueness
        return strtoupper("{$firstNamePrefix}{$randomSuffix}");
    }

    /**
     * Get the higher denomination amount
     */
    public function higherDenominationAmount($amount)
    {
        return $amount / 100;
    }

    /**
     * Get the higher denomination money format
     */
    public function moneyFormat($amount)
    {
        // Get the higher denomination amount
        $higherDenominationAmount = $this->higherDenominationAmount($amount);

        return number_format($higherDenominationAmount, 2);
    }

    /**
     * Get the higher denomination money display
     */
    public function moneyDisplay($amount)
    {
        return 'NGN'.' '.$this->moneyFormat($amount);
    }
}