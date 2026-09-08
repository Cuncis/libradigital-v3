<?php

namespace Tests\Feature\Policies;

use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Direct policy assertions, independent of InvitationResource's own query
 * scoping (getEloquentQuery()). An HTTP test proves access was denied
 * somehow; it can't prove the policy itself is what did it — if the query
 * scope were ever weakened, only this would still catch it.
 */
class InvitationPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_view_update_and_delete_their_own_invitation(): void
    {
        $owner = User::factory()->create();
        $invitation = Invitation::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $invitation));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $invitation));
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $invitation));
        $this->assertTrue(Gate::forUser($owner)->allows('restore', $invitation));
    }

    public function test_a_different_customer_cannot_view_update_or_delete_it(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $invitation = Invitation::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($stranger)->denies('view', $invitation));
        $this->assertTrue(Gate::forUser($stranger)->denies('update', $invitation));
        $this->assertTrue(Gate::forUser($stranger)->denies('delete', $invitation));
        $this->assertTrue(Gate::forUser($stranger)->denies('restore', $invitation));
        $this->assertTrue(Gate::forUser($stranger)->denies('forceDelete', $invitation));
    }

    public function test_admin_can_view_update_delete_and_force_delete_any_invitation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $invitation));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $invitation));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $invitation));
        $this->assertTrue(Gate::forUser($admin)->allows('forceDelete', $invitation));
    }

    public function test_only_admin_can_force_delete(): void
    {
        $owner = User::factory()->create();
        $invitation = Invitation::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($owner)->denies('forceDelete', $invitation));
    }

    public function test_create_is_allowed_for_a_subscribed_customer_and_denied_without_a_subscription(): void
    {
        $subscribed = User::factory()->create();
        $plan = Plan::factory()->create(['invitation_limit' => 5]);
        Subscription::factory()->active()->create(['user_id' => $subscribed->id, 'plan_id' => $plan->id]);

        $unsubscribed = User::factory()->create();

        $this->assertTrue(Gate::forUser($subscribed)->allows('create', Invitation::class));
        $this->assertTrue(Gate::forUser($unsubscribed)->denies('create', Invitation::class));
    }

    public function test_admin_can_always_create_regardless_of_subscription(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue(Gate::forUser($admin)->allows('create', Invitation::class));
    }
}
