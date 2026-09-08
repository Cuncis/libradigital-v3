<?php

namespace App\Notifications;

use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ?Plan $plan) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->plan?->name ?? 'your plan';

        return (new MailMessage)
            ->subject('Payment reminder')
            ->line("We haven't received payment for {$planName} yet.")
            ->line('Complete your payment to keep your subscription active.')
            ->action('Complete payment', url('/user/billing'));
    }
}
