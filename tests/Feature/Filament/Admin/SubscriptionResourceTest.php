<?php

namespace Tests\Feature\Filament\Admin;

use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SubscriptionResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_all_subscriptions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $subscription = Subscription::factory()->active()->create();

        $response = $this->actingAs($admin)->get('/admin/subscriptions');

        $response->assertOk();
        $response->assertSee($subscription->user->name);
    }

    public function test_customer_cannot_access_subscriptions(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/subscriptions');

        $response->assertForbidden();
    }
}
