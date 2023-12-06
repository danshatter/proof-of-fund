<?php

namespace App\Exceptions;

use Exception;
use App\Traits\Responses\Application as ApplicationResponse;

class AccountLockedException extends Exception
{
    use ApplicationResponse;

    /**
     * Create an instance
     */
    public function __construct($user)
    {
        $message = __('app.account_locked', [
            'duration' => $user->locked_due_to_failed_login_attempts_at->addSeconds(config('japa.login_attempts_lock_time'))->diffForHumans()
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
        return $this->sendErrorMessage($this->getMessage(), 403);
    }
}
