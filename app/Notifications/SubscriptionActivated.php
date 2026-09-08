<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Subscription $subscription) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan;

        return (new MailMessage)
            ->subject("Your {$plan->name} subscription is active")
            ->line("Your {$plan->name} subscription is active.")
            ->when(
                $this->subscription->current_period_end,
                fn (MailMessage $mail) => $mail->line('Renews on: '.$this->subscription->current_period_end->format('d M Y'))
            )
            ->action('Manage subscription', url('/user/billing'));
    }
}
