<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\SubscriptionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionEnded extends Notification implements ShouldQueue
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
        $reason = $this->subscription->status === SubscriptionStatus::Cancelled ? 'cancelled' : 'expired';

        return (new MailMessage)
            ->subject("Your {$plan->name} subscription has {$reason}")
            ->line("Your {$plan->name} subscription has {$reason}.")
            ->line('Your existing invitations aren\'t affected, but you\'ll need an active subscription to create a new one.')
            ->action('Resubscribe', url('/user/billing'));
    }
}
