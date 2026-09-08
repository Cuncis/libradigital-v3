<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Notifications\SubscriptionActivated;
use App\Notifications\SubscriptionEnded;
use App\SubscriptionStatus;

class SubscriptionObserver
{
    public function updated(Subscription $subscription): void
    {
        if ($subscription->wasChanged('status')) {
            match ($subscription->status) {
                SubscriptionStatus::Active => $subscription->user->notify(new SubscriptionActivated($subscription)),
                SubscriptionStatus::Expired, SubscriptionStatus::Cancelled => $subscription->user->notify(new SubscriptionEnded($subscription)),
                SubscriptionStatus::Pending => null,
            };

            return;
        }

        // Renewal: status stays "active" but the period end moves forward —
        // wasChanged('status') alone would miss this.
        if ($subscription->status === SubscriptionStatus::Active && $subscription->wasChanged('current_period_end')) {
            $subscription->user->notify(new SubscriptionActivated($subscription));
        }
    }
}
