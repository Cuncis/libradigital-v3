<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Theme;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_sees_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('landing');
    }

    public function test_landing_page_lists_only_active_themes(): void
    {
        Theme::factory()->create(['name' => 'Visible Theme', 'is_active' => true]);
        Theme::factory()->inactive()->create(['name' => 'Hidden Theme']);

        $response = $this->get('/');

        $response->assertSee('Visible Theme');
        $response->assertDontSee('Hidden Theme');
    }

    public function test_landing_page_lists_plans_grouped_by_tier(): void
    {
        Plan::factory()->create(['tier' => 'starter', 'billing_interval' => 'monthly', 'name' => 'Starter Monthly']);
        Plan::factory()->create(['tier' => 'starter', 'billing_interval' => 'yearly', 'name' => 'Starter Yearly']);

        $response = $this->get('/');

        $response->assertSee('starter');
    }

    public function test_authenticated_customer_is_redirected_to_the_user_panel(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/');

        $response->assertRedirect('/user');
    }

    public function test_authenticated_admin_is_redirected_to_the_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect('/admin');
    }
}
