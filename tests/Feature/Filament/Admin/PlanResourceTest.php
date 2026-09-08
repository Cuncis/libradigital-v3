<?php

namespace Tests\Feature\Filament\Admin;

use App\Models\Plan;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PlanResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_the_plan_catalog(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Plan::factory()->create(['name' => 'Starter Monthly']);

        $response = $this->actingAs($admin)->get('/admin/plans');

        $response->assertOk();
        $response->assertSee('Starter Monthly');
    }

    public function test_customer_cannot_access_the_plan_catalog(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/plans');

        $response->assertForbidden();
    }
}
