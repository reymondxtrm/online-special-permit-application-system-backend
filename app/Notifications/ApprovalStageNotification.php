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
            ->subject('Permit Approved')->view('approved-application', ['user' => $notifiable, 'permit_type' => $this->type, 'frontendURL' => $frontendUrl]);
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
