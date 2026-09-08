<?php

namespace Tests\Feature;

use App\Filament\User\Resources\Invitations\Pages\CreateInvitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ties together pieces that are each unit/feature-tested separately
 * (checkout, webhook activation, entitlement, invitation creation) as one
 * continuous flow, to catch integration seams the piecewise tests can't.
 */
class SubscribeCheckoutEntitlementFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_subscribing_then_a_webhook_activation_unlocks_invitation_creation_up_to_the_plan_limit(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/memberships/members/create' => Http::response([
                'data' => ['membershipCustomer' => ['memberId' => 'MBR1', 'id' => 'uuid-1']],
            ], 201),
            '*/memberships/members/MBR1/invoice/create' => Http::response([
                'data' => ['id' => 'inv-1', 'membershipBillUrl' => 'https://mayar.test/pl/abc'],
            ], 200),
        ]);
        config(['services.mayar.webhook_token' => 'test-webhook-token']);

        $customer = User::factory()->create(['phone' => '+6281234567890']);
        $plan = Plan::factory()->create(['mayar_tier_id' => 'tier-1', 'invitation_limit' => 1]);

        // Not subscribed yet.
        $this->assertFalse($customer->fresh()->canCreateInvitation());

        // 1. Checkout — creates a pending Subscription, redirects to Mayar.
        $checkoutResponse = $this->actingAs($customer)->post(route('subscribe', $plan));
        $checkoutResponse->assertRedirect('https://mayar.test/pl/abc');

        $subscription = Subscription::query()->where('user_id', $customer->id)->sole();
        $this->assertSame('pending', $subscription->status->value);
        $this->assertFalse($customer->fresh()->canCreateInvitation());

        // 2. Mayar's webhook is the source of truth, not the redirect.
        $this->postJson(route('webhooks.mayar', ['token' => 'test-webhook-token']), [
            'event' => 'payment.received',
            'data' => [
                'id' => 'inv-1',
                'status' => 'SUCCESS',
                'membershipCustomer' => ['id' => 'MBR1', 'expiredAt' => now()->addMonth()->toIso8601String()],
            ],
        ])->assertOk();

        $this->assertSame('active', $subscription->fresh()->status->value);
        $this->assertTrue($customer->fresh()->canCreateInvitation());

        // 3. Entitlement now allows creating an invitation, through the real form.
        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::actingAs($customer)
            ->test(CreateInvitation::class)
            ->fillForm(['title' => 'Amara & Reyhan', 'slug' => 'amara-reyhan', 'status' => 'draft'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, $customer->invitations()->count());

        // 4. Plan limit (1) is now reached — creating a second is blocked.
        $this->assertFalse($customer->fresh()->canCreateInvitation());
        $this->actingAs($customer)->get('/user/invitations/create')->assertForbidden();
    }
}
