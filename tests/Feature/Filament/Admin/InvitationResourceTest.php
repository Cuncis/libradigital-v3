<?php

namespace Tests\Feature\Filament\Admin;

use App\Models\Invitation;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvitationResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_sees_invitations_from_every_owner(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Invitation::factory()->create(['title' => "Customer A's Invitation"]);
        Invitation::factory()->create(['title' => "Customer B's Invitation"]);

        $response = $this->actingAs($admin)->get('/admin/invitations');

        $response->assertOk();
        $response->assertSee("Customer A's Invitation");
        $response->assertSee("Customer B's Invitation");
    }

    public function test_customer_cannot_access_the_admin_invitation_list(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/invitations');

        $response->assertForbidden();
    }
}
