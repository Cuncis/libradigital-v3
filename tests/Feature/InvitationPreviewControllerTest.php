<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvitationPreviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->get("/invitations/{$invitation->id}/preview");

        $response->assertRedirect('/user/login');
    }

    public function test_owner_can_preview_their_own_draft_invitation(): void
    {
        $owner = User::factory()->create();
        $invitation = Invitation::factory()->for($owner)->create([
            'title' => 'Amara & Reyhan',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($owner)->get("/invitations/{$invitation->id}/preview");

        $response->assertOk();
        $response->assertSee('Amara & Reyhan');
        $response->assertSee("Preview — not published yet. Guests can't see this page.", false);
    }

    public function test_published_invitation_preview_has_no_draft_banner(): void
    {
        $owner = User::factory()->create();
        $invitation = Invitation::factory()->for($owner)->published()->create();

        $response = $this->actingAs($owner)->get("/invitations/{$invitation->id}/preview");

        $response->assertOk();
        $response->assertDontSee('not published yet');
    }

    public function test_admin_can_preview_any_invitation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($admin)->get("/invitations/{$invitation->id}/preview");

        $response->assertOk();
    }

    public function test_a_different_customer_cannot_preview_it(): void
    {
        $stranger = User::factory()->create();
        $invitation = Invitation::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($stranger)->get("/invitations/{$invitation->id}/preview");

        $response->assertForbidden();
    }
}
