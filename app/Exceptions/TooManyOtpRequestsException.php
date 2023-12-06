<?php

namespace App\Exceptions;

use Exception;
use App\Traits\Responses\Application as ApplicationResponse;

class TooManyOtpRequestsException extends Exception
{
    use ApplicationResponse;

    /**
     * Create an instance
     */
    public function __construct($availableIn)
    {
        $message = __('app.too_many_otp_requests', [
            'duration' => now()->addSeconds($availableIn)->diffForHumans()
        ]);
        
        parent::__construct($message);
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return $this->sendErrorMessage($this->getMessage(), 429);
    }
}
