<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisapprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $reason;
    private $application_type;
    public function __construct($reason, $permit_type)
    {
        $this->application_type = $permit_type;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Hi ' . $notifiable->fname)
            ->line('Thank you for using the Online Special Permit Application System (OSPAS). Your application for' . $this->application_type . ' has been disapproved.')
            ->line($this->reason)
            ->line('To proceed, pleas submit the required documents indicated above. For Further inquiry, please contact the Business Licencing Section at 09513884193 or email us at cbpld@butuan.gov.ph.');
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
