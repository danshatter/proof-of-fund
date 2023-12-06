<?php

namespace App\Traits\Auth;

use App\Services\Auth\Otp as OtpService;

trait UsesOtp
{
    /**
     * Generate OTP
     */
    public function generateOtp()
    {
        $this->forceFill([
            'verification' => app()->make(OtpService::class)->generate(),
            'verification_expires_at' => now()->addSeconds(config('japa.otp_expiration_time')),
        ])->save();
    }

    /**
     * The format for success responses
     */
    public function markAsVerified()
    {
        $this->forceFill([
            'verification' => null,
            'verification_expires_at' => null,
            'verified_at' => now()
        ])->save();
    }

    /**
     * Check if a user is already verified
     */
    public function isVerified()
    {
        return isset($this->verified_at);
    }

    /**
     * Check if the OTP has expired
     */
    public function hasExpiredOtp()
    {
        return (isset($this->verification_expires_at)) && ($this->verification_expires_at < now());
    }

    /**
     * Reset OTP validation
     */
    public function unlockOtpValidation()
    {
        $this->forceFill([
            'failed_verification_attempts' => null,
            'locked_due_to_failed_verification_at' => null
        ])->save();
    }
}
