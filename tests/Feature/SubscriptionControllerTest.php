<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->post(route('subscribe', $plan));

        $response->assertRedirect('/user/login');
    }

    public function test_authenticated_user_is_redirected_to_mayar_checkout(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/memberships/members/create' => Http::response([
                'data' => ['membershipCustomer' => ['memberId' => 'MBR123', 'id' => 'uuid-1']],
            ], 201),
            '*/memberships/members/*/invoice/create' => Http::response([
                'data' => ['id' => 'inv-1', 'membershipBillUrl' => 'https://mayar.test/pl/abc'],
            ], 200),
        ]);

        $user = User::factory()->create(['phone' => '+6281234567890']);
        $plan = Plan::factory()->create(['mayar_tier_id' => 'tier-1']);

        $response = $this->actingAs($user)->post(route('subscribe', $plan));

        $response->assertRedirect('https://mayar.test/pl/abc');
    }

    public function test_missing_phone_is_saved_from_the_request_before_checkout(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/memberships/members/create' => Http::response([
                'data' => ['membershipCustomer' => ['memberId' => 'MBR123']],
            ], 201),
            '*/memberships/members/*/invoice/create' => Http::response([
                'data' => ['id' => 'inv-1', 'membershipBillUrl' => 'https://mayar.test/pl/abc'],
            ], 200),
        ]);

        $user = User::factory()->create(['phone' => null]);
        $plan = Plan::factory()->create(['mayar_tier_id' => 'tier-1']);

        $this->actingAs($user)->post(route('subscribe', $plan), ['phone' => '+6281234567890']);

        $this->assertSame('+6281234567890', $user->refresh()->phone);
    }
}
