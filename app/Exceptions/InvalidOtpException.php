<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Str;
use App\Traits\Responses\Application as ApplicationResponse;

class InvalidOtpException extends Exception
{
    use ApplicationResponse;

    /**
     * Create an instance
     */
    public function __construct($retriesLeft = null)
    {
        $message = __('app.invalid_otp', [
            'additional_comments' => isset($retriesLeft) ? ' You have '.$retriesLeft.' '.Str::plural('retry', $retriesLeft).' left.' : null
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
        return $this->sendErrorMessage($this->getMessage(), 400);
    }
}
