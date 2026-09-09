<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Models\Invitation;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Regression: the header "Save changes" button used to reuse
     * getSaveFormAction(), which renders a native type="submit" button
     * wired to the <form>'s wire:submit — that only works while the
     * button lives inside that <form>. Header actions render outside it,
     * so clicking Save silently did nothing. Fixed by calling save()
     * directly via ->action('save') instead, like every other header
     * action already does regardless of where it renders.
     *
     * Because this action has no ->submit()/->requiresConfirmation()/URL,
     * Filament renders it as a plain `wire:click="save"` (confirmed by
     * reading Action::getLivewireClickHandler() — a bare string ->action()
     * skips the mounted-action pipeline entirely), so the test calls
     * save() directly rather than through ->callAction(), which simulates
     * that different (mount + confirm) pipeline instead of a real click
     * on this specific button.
     */
    public function test_the_header_save_action_actually_persists_changes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->create(['title' => 'Original Title']);

        Livewire::actingAs($admin)
            ->test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm(['title' => 'Updated Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Title', $invitation->fresh()->title);
    }

    /**
     * Confirms the rendered button really is the direct wire:click="save"
     * described above — not a mounted-action button that a real click
     * would resolve differently than the test above exercises.
     */
    public function test_the_save_button_renders_as_a_direct_livewire_click_handler(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->create();

        $response = $this->actingAs($admin)->get("/admin/invitations/{$invitation->id}/edit");

        $response->assertOk();
        $response->assertSee('wire:click="save"', false);
    }

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
