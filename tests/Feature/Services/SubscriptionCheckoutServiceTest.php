<?php

namespace Tests\Feature\Services;

use App\Exceptions\MayarCheckoutException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionCheckoutServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mayar.is_production' => false,
            'services.mayar.base_url' => null,
            'services.mayar.product_id' => 'prod-1',
        ]);
    }

    public function test_checkout_creates_pending_subscription_with_mayar_ids_and_returns_bill_url(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/memberships/members/create' => Http::response([
                'data' => ['membershipCustomer' => ['memberId' => 'MBR8X2QK', 'id' => 'uuid-1']],
            ], 201),
            '*/memberships/members/MBR8X2QK/invoice/create' => Http::response([
                'data' => ['id' => 'inv-1', 'membershipBillUrl' => 'https://mayar.test/pl/abc'],
            ], 200),
        ]);

        $user = User::factory()->create(['phone' => '+6281234567890']);
        $plan = Plan::factory()->create(['mayar_tier_id' => 'tier-1', 'billing_interval' => 'yearly']);

        $billUrl = app(SubscriptionCheckoutService::class)->checkout($user, $plan);

        $this->assertSame('https://mayar.test/pl/abc', $billUrl);

        $subscription = Subscription::query()->where('user_id', $user->id)->sole();
        $this->assertSame('pending', $subscription->status->value);
        $this->assertSame('MBR8X2QK', $subscription->mayar_member_id);
        $this->assertSame('inv-1', $subscription->mayar_invoice_id);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mayar.club/hl/v2/memberships/members/create'
            && $request['membershipMonthlyPeriod'] === 12);
    }

    public function test_checkout_throws_when_user_has_no_phone(): void
    {
        $user = User::factory()->create(['phone' => null]);
        $plan = Plan::factory()->create(['mayar_tier_id' => 'tier-1']);

        $this->expectException(MayarCheckoutException::class);

        app(SubscriptionCheckoutService::class)->checkout($user, $plan);
    }

    public function test_checkout_throws_when_plan_has_no_mayar_tier_configured(): void
    {
        $user = User::factory()->create(['phone' => '+6281234567890']);
        $plan = Plan::factory()->create(['mayar_tier_id' => null]);

        $this->expectException(MayarCheckoutException::class);

        app(SubscriptionCheckoutService::class)->checkout($user, $plan);
    }
}
