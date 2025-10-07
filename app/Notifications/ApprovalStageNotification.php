<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalStageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $stage;
    private $type;
    private $permit_code;
    public function __construct($stage, $type, $reference_code)
    {
        $this->stage = $stage;
        $this->type = $type;
        $this->permit_code = $reference_code;
    }
    // $stage
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
        $frontendUrl = config('app.frontend_url');
        return (new MailMessage)
            ->subject('Permit Approved')
            ->greeting('Hi ' . $notifiable->fname . ',')
            ->line('Thank you for using Online Specila PErmit Application System (OSPAS). Your application for' . $this->type . 'has been APPROVED. To proceed with the next transaction, please click the Proceed to Online Services.')
            ->action(
                'Proceed to Online Serices.',
                $frontendUrl
            )
            ->line('For Further inquiry, please contact the Business Licensing Section at 09513884193 or email us at cbpld@butuan.gov.ph.');
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
