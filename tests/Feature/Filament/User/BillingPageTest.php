<?php

namespace Tests\Feature\Filament\User;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/user/billing');

        $response->assertRedirect('/user/login');
    }

    public function test_admin_cannot_access_the_user_panel_billing_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/user/billing');

        $response->assertForbidden();
    }

    public function test_customer_sees_their_plans_and_active_subscription(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $plan = Plan::factory()->create(['tier' => 'plus', 'name' => 'Plus Monthly']);
        Subscription::factory()->create([
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($customer)->get('/user/billing');

        $response->assertOk();
        $response->assertSee('Plus Monthly');
    }
}
