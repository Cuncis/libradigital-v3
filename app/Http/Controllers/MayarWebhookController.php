<?php

namespace App\Http\Controllers;

use App\Models\MayarWebhookEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\SubscriptionPaymentReminder;
use App\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles Mayar membership/payment webhooks. Mayar's docs are explicit that
 * the browser redirect after checkout is UX only — this endpoint is the
 * source of truth for whether a Subscription actually becomes active.
 *
 * Verification: Mayar's public docs don't document a signature header for
 * this event, so the registered webhook URL carries a shared token as a
 * query param (?token=...) instead. Re-verify this against Mayar's dashboard
 * or support before relying on it in production.
 */
class MayarWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        if (! hash_equals((string) config('services.mayar.webhook_token'), (string) $request->query('token'))) {
            abort(403);
        }

        $payload = $request->all();
        $event = (string) ($payload['event'] ?? 'unknown');

        Log::info('Mayar webhook received', ['event' => $event, 'payload' => $payload]);

        $eventKey = $this->eventKey($event, $payload);

        if (MayarWebhookEvent::query()->where('event_key', $eventKey)->exists()) {
            return response('', 200);
        }

        MayarWebhookEvent::query()->create([
            'event_key' => $eventKey,
            'event' => $event,
            'payload' => $payload,
            'created_at' => now(),
        ]);

        match ($event) {
            'payment.received' => $this->handlePaymentReceived($payload),
            'payment.reminder' => $this->handlePaymentReminder($payload),
            'membership.memberExpired' => $this->updateSubscriptionStatus($payload, SubscriptionStatus::Expired),
            'membership.memberUnsubscribed' => $this->updateSubscriptionStatus($payload, SubscriptionStatus::Cancelled),
            'membership.changeTierMemberRegistered' => $this->handleTierChange($payload),
            default => Log::info("Mayar webhook: unhandled event [{$event}]"),
        };

        return response('', 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function eventKey(string $event, array $payload): string
    {
        $data = $payload['data'] ?? [];
        $id = $data['id'] ?? $data['transactionId'] ?? $data['membershipCustomer']['id'] ?? null;

        return $id ? "{$event}:{$id}" : "{$event}:".md5(json_encode($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handlePaymentReceived(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $status = strtoupper((string) ($data['status'] ?? ''));

        if ($status !== 'SUCCESS') {
            return;
        }

        $subscription = $this->resolveSubscription($payload);

        if (! $subscription) {
            Log::warning('Mayar payment.received: no matching subscription', ['payload' => $payload]);

            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'current_period_end' => $data['membershipCustomer']['expiredAt'] ?? $data['expiredAt'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handlePaymentReminder(array $payload): void
    {
        $subscription = $this->resolveSubscription($payload);

        if (! $subscription) {
            return;
        }

        $subscription->user->notify(new SubscriptionPaymentReminder($subscription->plan));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function updateSubscriptionStatus(array $payload, SubscriptionStatus $status): void
    {
        $subscription = $this->resolveSubscription($payload);

        if (! $subscription) {
            Log::warning("Mayar webhook: no matching subscription for status [{$status->value}]", ['payload' => $payload]);

            return;
        }

        $subscription->update(['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleTierChange(array $payload): void
    {
        $subscription = $this->resolveSubscription($payload);

        if (! $subscription) {
            return;
        }

        $newTierId = $payload['data']['membershipTierId'] ?? null;
        $plan = $newTierId ? Plan::query()->where('mayar_tier_id', $newTierId)->first() : null;

        if ($plan) {
            $subscription->update(['plan_id' => $plan->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveSubscription(array $payload): ?Subscription
    {
        $data = $payload['data'] ?? [];
        $memberId = $data['membershipCustomer']['id']
            ?? $data['membershipCustomer']['memberId']
            ?? $data['memberId']
            ?? null;
        $invoiceId = $data['id'] ?? $data['transactionId'] ?? null;

        if (! $memberId && ! $invoiceId) {
            return null;
        }

        return Subscription::query()
            ->where(function ($query) use ($memberId, $invoiceId): void {
                if ($memberId) {
                    $query->orWhere('mayar_member_id', $memberId);
                }

                if ($invoiceId) {
                    $query->orWhere('mayar_invoice_id', $invoiceId);
                }
            })
            ->latest('id')
            ->first();
    }
}
