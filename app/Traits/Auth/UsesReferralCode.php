<?php

namespace App\Traits\Auth;

use App\Models\{User, Role};
use App\Services\Application as ApplicationService;

trait UsesReferralCode
{
    /**
     * Generate referral code
     */
    public function generateReferralCode()
    {
        if (!isset($this->referral_code)) {
            // Attempt to create a referral code
            do {
                // Generate the referral code
                $referralCode = app()->make(ApplicationService::class)->generateReferralCode($this);
            } while (User::where('referral_code', $referralCode)->exists());

            $this->forceFill([
                'referral_code' => $referralCode
            ])->save();
        }

        return $this->referral_code;
    }

}
