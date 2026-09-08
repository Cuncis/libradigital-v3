<?php

namespace App\Services;

use App\Exceptions\MayarCheckoutException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Mayar\MayarClient;

class SubscriptionCheckoutService
{
    public function __construct(protected MayarClient $mayar) {}

    /**
     * Start a Mayar checkout for the given plan and return the hosted bill URL
     * to redirect the user to. The Subscription is created "pending" — only a
     * confirmed webhook flips it to "active" (redirect is UX, webhook is truth).
     */
    public function checkout(User $user, Plan $plan): string
    {
        if (! $user->phone) {
            throw new MayarCheckoutException('A phone number is required before subscribing.');
        }

        if (! $plan->mayar_tier_id) {
            throw new MayarCheckoutException("Plan [{$plan->name}] has no Mayar tier configured yet.");
        }

        $member = $this->mayar->createMember(
            membershipTierId: $plan->mayar_tier_id,
            customerName: $user->name,
            customerEmail: $user->email,
            customerMobile: $user->phone,
            monthlyPeriod: $plan->billing_interval === 'yearly' ? 12 : 1,
        );

        // Mayar's path param is `{memberId}`, matching the short `memberId` code
        // in the response (e.g. "MBR8X2QK"), not the internal `id` UUID.
        $memberId = $member['memberId'] ?? $member['id'] ?? null;

        if (! $memberId) {
            throw new MayarCheckoutException('Mayar did not return a member id.');
        }

        $invoice = $this->mayar->createInvoice($memberId);

        $billUrl = $invoice['membershipBillUrl'] ?? null;

        if (! $billUrl) {
            throw new MayarCheckoutException('Mayar did not return a checkout URL.');
        }

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'mayar_member_id' => $memberId,
            'mayar_invoice_id' => $invoice['id'] ?? null,
        ]);

        return $billUrl;
    }
}
