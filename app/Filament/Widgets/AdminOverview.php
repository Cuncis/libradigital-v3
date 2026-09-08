<?php

namespace App\Filament\Widgets;

use App\CustomRequestStatus;
use App\Models\CustomRequest;
use App\Models\Invitation;
use App\Models\Subscription;
use App\SubscriptionStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeSubscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->with('plan')
            ->get();

        // Yearly plans normalized to a monthly-equivalent estimate.
        $estimatedMrr = $activeSubscriptions->sum(fn (Subscription $subscription) => match ($subscription->plan->billing_interval) {
            'yearly' => (int) round($subscription->plan->price / 12),
            default => $subscription->plan->price,
        });

        $backlog = CustomRequest::query()
            ->whereIn('status', [
                CustomRequestStatus::New,
                CustomRequestStatus::InReview,
                CustomRequestStatus::InProgress,
            ])
            ->count();

        $publishedThisWeek = Invitation::query()
            ->where('status', 'published')
            ->whereBetween('published_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return [
            Stat::make('Active subscriptions', (string) $activeSubscriptions->count())
                ->description('Estimated MRR: Rp '.number_format($estimatedMrr, 0, ',', '.')),

            Stat::make('Custom request backlog', (string) $backlog)
                ->description('New, in review, or in progress'),

            Stat::make('Published this week', (string) $publishedThisWeek)
                ->description('Invitations published, current week'),
        ];
    }
}
