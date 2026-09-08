<?php

namespace App\Notifications;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRsvpReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Guest $guest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invitation = $this->guest->invitation;

        return (new MailMessage)
            ->subject("New RSVP for {$invitation->title}")
            ->line("{$this->guest->name} responded to your invitation \"{$invitation->title}\".")
            ->line('Status: '.match ($this->guest->attending->value) {
                'attending' => 'Attending',
                'not_attending' => 'Not attending',
                'maybe' => 'Maybe',
            })
            ->line("Party size: {$this->guest->party_size}")
            ->when($this->guest->message, fn (MailMessage $mail) => $mail->line("Message: \"{$this->guest->message}\""))
            ->action('View RSVPs', url('/user/rsvps'));
    }
}
