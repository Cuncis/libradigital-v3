<?php

namespace Tests\Feature;

use App\Models\MayarWebhookEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\SubscriptionActivated;
use App\Notifications\SubscriptionEnded;
use App\Notifications\SubscriptionPaymentReminder;
use App\SubscriptionStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MayarWebhookControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mayar.webhook_token' => 'test-webhook-token']);
    }

    protected function webhookUrl(): string
    {
        return route('webhooks.mayar', ['token' => 'test-webhook-token']);
    }

    public function test_missing_token_returns_403(): void
    {
        $response = $this->postJson(route('webhooks.mayar'), ['event' => 'payment.received']);

        $response->assertForbidden();
    }

    public function test_wrong_token_returns_403(): void
    {
        $response = $this->postJson(route('webhooks.mayar', ['token' => 'wrong']), ['event' => 'payment.received']);

        $response->assertForbidden();
    }

    public function test_payment_received_activates_pending_subscription(): void
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'status' => 'pending',
            'mayar_member_id' => 'MBR8X2QK',
            'mayar_invoice_id' => 'inv-123',
        ]);

        $response = $this->postJson($this->webhookUrl(), [
            'event' => 'payment.received',
            'data' => [
                'id' => 'inv-123',
                'status' => 'SUCCESS',
                'membershipCustomer' => [
                    'id' => 'MBR8X2QK',
                    'expiredAt' => '2027-01-01T00:00:00.000Z',
                ],
            ],
        ]);

        $response->assertOk();
        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('2027-01-01 00:00:00', $subscription->current_period_end->format('Y-m-d H:i:s'));
        Notification::assertSentTo($subscription->user, SubscriptionActivated::class);
    }

    public function test_payment_received_on_an_already_active_subscription_notifies_as_a_renewal(): void
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'mayar_member_id' => 'MBR8X2QK',
            'mayar_invoice_id' => 'inv-123',
            'current_period_end' => now()->addDay(),
        ]);

        $response = $this->postJson($this->webhookUrl(), [
            'event' => 'payment.received',
            'data' => [
                'id' => 'inv-123',
                'status' => 'SUCCESS',
                'membershipCustomer' => [
                    'id' => 'MBR8X2QK',
                    'expiredAt' => '2027-06-01T00:00:00.000Z',
                ],
            ],
        ]);

        $response->assertOk();
        // status didn't change (already active) — only current_period_end moved,
        // which is exactly the case wasChanged('status') alone would miss.
        Notification::assertSentTo($subscription->user, SubscriptionActivated::class);
    }

    public function test_payment_reminder_notifies_the_customer(): void
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'status' => 'pending',
            'mayar_member_id' => 'MBR8X2QK',
        ]);

        $response = $this->postJson($this->webhookUrl(), [
            'event' => 'payment.reminder',
            'data' => ['membershipCustomer' => ['id' => 'MBR8X2QK']],
        ]);

        $response->assertOk();
        Notification::assertSentTo($subscription->user, SubscriptionPaymentReminder::class);
    }

    public function test_payment_received_with_unmatched_subscription_does_not_error(): void
    {
        $response = $this->postJson($this->webhookUrl(), [
            'event' => 'payment.received',
            'data' => [
                'id' => 'unknown-invoice',
                'status' => 'SUCCESS',
            ],
        ]);

        $response->assertOk();
    }

    public function test_duplicate_delivery_is_processed_only_once(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'pending',
            'mayar_member_id' => 'MBR8X2QK',
            'mayar_invoice_id' => 'inv-123',
        ]);

        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'inv-123',
                'status' => 'SUCCESS',
                'membershipCustomer' => ['id' => 'MBR8X2QK', 'expiredAt' => '2027-01-01T00:00:00.000Z'],
            ],
        ];

        $this->postJson($this->webhookUrl(), $payload)->assertOk();
        $this->postJson($this->webhookUrl(), $payload)->assertOk();

        $this->assertSame(1, MayarWebhookEvent::query()->count());
    }

    public function test_membership_expired_sets_subscription_status_expired(): void
    {
        Notification::fake();

        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'mayar_member_id' => 'MBR8X2QK',
        ]);

        $response = $this->postJson($this->webhookUrl(), [
            'event' => 'membership.memberExpired',
            'data' => ['membershipCustomer' => ['id' => 'MBR8X2QK']],
        ]);

        $response->assertOk();
        $this->assertSame(SubscriptionStatus::Expired, $subscription->refresh()->status);
        Notification::assertSentTo($subscription->user, SubscriptionEnded::class);
    }

    public function test_tier_change_swaps_the_linked_plan(): void
    {
        $newPlan = Plan::factory()->create(['mayar_tier_id' => 'tier-new']);
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'mayar_member_id' => 'MBR8X2QK',
        ]);

        $response = $this->postJson($this->webhookUrl(), [
            'event' => 'membership.changeTierMemberRegistered',
            'data' => [
                'membershipTierId' => 'tier-new',
                'membershipCustomer' => ['id' => 'MBR8X2QK'],
            ],
        ]);

        $response->assertOk();
        $this->assertSame($newPlan->id, $subscription->refresh()->plan_id);
    }
}
