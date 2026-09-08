<?php

namespace Tests\Feature\Policies;

use App\Models\CustomRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * No customer-facing route currently reaches CustomRequest view/edit (a
 * customer can only create one, via the public landing-page form) — so
 * this policy has no HTTP path exercising it yet. Testing it directly here
 * means it's still verified now, and already protected the moment any
 * future UI (e.g. a customer-facing "my requests" page) reaches it.
 */
class CustomRequestPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requester_can_view_and_update_their_own_request(): void
    {
        $requester = User::factory()->create();
        $customRequest = CustomRequest::factory()->for($requester)->create();

        $this->assertTrue(Gate::forUser($requester)->allows('view', $customRequest));
        $this->assertTrue(Gate::forUser($requester)->allows('update', $customRequest));
        $this->assertTrue(Gate::forUser($requester)->allows('delete', $customRequest));
    }

    public function test_a_different_customer_cannot_view_update_or_delete_it(): void
    {
        $requester = User::factory()->create();
        $stranger = User::factory()->create();
        $customRequest = CustomRequest::factory()->for($requester)->create();

        $this->assertTrue(Gate::forUser($stranger)->denies('view', $customRequest));
        $this->assertTrue(Gate::forUser($stranger)->denies('update', $customRequest));
        $this->assertTrue(Gate::forUser($stranger)->denies('delete', $customRequest));
        $this->assertTrue(Gate::forUser($stranger)->denies('restore', $customRequest));
        $this->assertTrue(Gate::forUser($stranger)->denies('forceDelete', $customRequest));
    }

    public function test_admin_staff_can_view_update_and_delete_any_request(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customRequest = CustomRequest::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $customRequest));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $customRequest));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $customRequest));
        $this->assertTrue(Gate::forUser($admin)->allows('restore', $customRequest));
        $this->assertTrue(Gate::forUser($admin)->allows('forceDelete', $customRequest));
    }

    public function test_only_admin_can_restore_or_force_delete(): void
    {
        $requester = User::factory()->create();
        $customRequest = CustomRequest::factory()->for($requester)->create();

        $this->assertTrue(Gate::forUser($requester)->denies('restore', $customRequest));
        $this->assertTrue(Gate::forUser($requester)->denies('forceDelete', $customRequest));
    }
}
