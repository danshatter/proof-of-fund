<?php

namespace App\Exceptions;

use Exception;
use App\Traits\Responses\Application as ApplicationResponse;

class EmailAlreadyVerifiedException extends Exception
{
    use ApplicationResponse;

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
        return $this->sendSuccess(__('app.email_already_verified'));
    }
}
