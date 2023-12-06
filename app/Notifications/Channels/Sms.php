<?php
 
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\ThirdParty\Termii as TermiiService;

class Sms
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $details = $notification->toSms($notifiable);

        // Send the SMS
        $data = app()->make(TermiiService::class)->sendSms($details['phone'], $details['message'], false);

        info($data);
    }
}