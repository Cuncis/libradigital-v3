<?php

namespace Tests\Feature;

use App\CustomRequestStatus;
use App\Models\CustomRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\CustomRequestStatusChanged;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomRequestBuildControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $customRequest = CustomRequest::factory()->create();

        $response = $this->actingAs($customer)->get("/admin/custom-requests/{$customRequest->id}/build");

        $response->assertForbidden();
    }

    public function test_creates_an_invitation_owned_by_the_requester_and_links_it(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $requester = User::factory()->create();
        $customRequest = CustomRequest::factory()->create([
            'user_id' => $requester->id,
            'event_type' => 'wedding',
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)->get("/admin/custom-requests/{$customRequest->id}/build");

        $customRequest->refresh();
        $invitation = Invitation::query()->findOrFail($customRequest->invitation_id);

        $this->assertSame($requester->id, $invitation->user_id);
        $this->assertTrue($invitation->is_custom_build);
        $this->assertSame(CustomRequestStatus::InReview, $customRequest->status);
        $response->assertRedirect(route('filament.admin.resources.invitations.edit', ['record' => $invitation]));
        Notification::assertSentTo($requester, CustomRequestStatusChanged::class);
    }

    public function test_reopening_an_already_built_request_reuses_the_same_invitation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $requester = User::factory()->create();
        $invitation = Invitation::factory()->for($requester)->create();
        $customRequest = CustomRequest::factory()->create([
            'user_id' => $requester->id,
            'invitation_id' => $invitation->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($admin)->get("/admin/custom-requests/{$customRequest->id}/build");

        $this->assertSame(1, Invitation::query()->where('user_id', $requester->id)->count());
        $this->assertSame(CustomRequestStatus::InProgress, $customRequest->fresh()->status);
    }
}
