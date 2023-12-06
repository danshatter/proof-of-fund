<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\Sms;

class BvnVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The camouflage object
     */
    private $camouflage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($camouflage)
    {
        $this->camouflage = $camouflage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [Sms::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toSms($notifiable)
    {
        return [
            'phone' => $this->camouflage->phone,
            'message' => __('app.bvn_otp_message', [
                'otp' => $this->camouflage->verification
            ])
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
