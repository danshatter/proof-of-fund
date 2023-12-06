<?php

namespace App\Exceptions;

use Exception;
use App\Traits\Responses\Application as ApplicationResponse;

class ForbiddenException extends Exception
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
        return $this->sendErrorMessage(__('app.forbidden'), 403);
    }
}
