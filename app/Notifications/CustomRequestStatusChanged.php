<?php

namespace App\Notifications;

use App\CustomRequestStatus;
use App\Models\CustomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected CustomRequest $customRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = match ($this->customRequest->status) {
            CustomRequestStatus::New => 'received',
            CustomRequestStatus::InReview => 'in review',
            CustomRequestStatus::InProgress => 'in progress',
            CustomRequestStatus::Delivered => 'delivered',
            CustomRequestStatus::Cancelled => 'cancelled',
        };

        return (new MailMessage)
            ->subject("Your custom design request is {$label}")
            ->line("Your custom design request is now {$label}.")
            ->when(
                $this->customRequest->status === CustomRequestStatus::Delivered && $this->customRequest->invitation_id,
                fn (MailMessage $mail) => $mail->action('View your invitation', url("/user/invitations/{$this->customRequest->invitation_id}/edit"))
            );
    }
}
