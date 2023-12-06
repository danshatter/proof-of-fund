<?php

namespace App\Services\Auth;

class Otp
{
    /**
     * Generate an OTP
     */
    public function generate()
    {
        return $this->build(config('japa.otp_digits'));
    }

    /**
     * Build the OTP
     */
    private function build($verificationLength)
    {
        // The maximum number of digits that can be generated
        $maximum = implode('', array_fill(0, $verificationLength, '9'));

        // The generated OTP 
        $otp = rand(1, (int) $maximum);

        // If the length of the OTP is less that the number of digits of the OTP, add zeros as the prefix
        if (strlen($otp) < $verificationLength) {
            $length = strlen($otp);

            $otp = implode('', array_fill(0, $verificationLength - $length, '0')).$otp;

            return (string) $otp;
        }

        return (string) $otp;
    }
}