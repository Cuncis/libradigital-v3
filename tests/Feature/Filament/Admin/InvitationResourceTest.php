<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Models\Invitation;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Js;
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

    /**
     * The editor is a full-screen takeover — no sidebar, no topbar — the
     * same way Elementor's own editor replaces wp-admin's chrome entirely
     * rather than living inside it (EditInvitation::$layout, pointed at
     * resources/views/filament/layouts/full-screen-editor.blade.php).
     * Confirmed against the *list* page, which is unaffected and still has
     * the normal panel chrome — proves this is really about the edit
     * page's own layout override, not something broken/missing globally
     * (e.g. an empty navigation array would make both pages fail the same
     * way and this test wouldn't catch it).
     */
    public function test_the_edit_page_is_a_full_screen_takeover_without_the_sidebar(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->create();

        // id="fi-main-sidebar" / class="fi-topbar-ctn" are the sidebar and
        // topbar Livewire components' own wrapper markup — not a resource
        // label like "Users", which (confirmed while writing this test)
        // Filament's global search embeds into a JS payload loaded on
        // every page regardless of whether the sidebar itself renders, so
        // it isn't reliable evidence either way.
        $listResponse = $this->actingAs($admin)->get('/admin/invitations');
        $listResponse->assertOk();
        $listResponse->assertSee('id="fi-main-sidebar"', false);
        $listResponse->assertSee('class="fi-topbar-ctn"', false);

        $editResponse = $this->actingAs($admin)->get("/admin/invitations/{$invitation->id}/edit");
        $editResponse->assertOk();
        $editResponse->assertDontSee('id="fi-main-sidebar"', false);
        $editResponse->assertDontSee('class="fi-topbar-ctn"', false);

        // The page's own header (breadcrumbs + Preview/Save/Delete
        // actions) is untouched — only the surrounding chrome is gone.
        $editResponse->assertSee('Preview');
        $editResponse->assertSee('Save changes');
    }

    /**
     * The real guest-facing link (route('invitations.show'), /i/{slug}) —
     * distinct from "Preview", which opens a separate auth-gated route.
     * Checked on both the list row action and the edit page header
     * action, since InvitationResource::copyLinkAction() is shared by both.
     *
     * Regression: route('invitations.show', $record) (passing the model
     * directly) silently embedded the model's route key (id) instead of
     * its slug — /i/{slug}'s parameter isn't named "invitation", so
     * Laravel's URL generator has no binding field to consult and just
     * calls $record->getRouteKey() (id). That 404'd for guests. Asserting
     * only that the embedded JS matched route('invitations.show', $record)
     * (as this test used to) doesn't catch that, since the same wrong call
     * appears on both sides — so this also hits the embedded URL directly
     * and asserts it actually resolves to the invitation's page.
     */
    public function test_the_copy_link_action_embeds_the_real_public_url(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);
        $publicUrl = route('invitations.show', ['slug' => $invitation->slug]);
        $publicUrlJs = Js::from($publicUrl)->toHtml();

        $listResponse = $this->actingAs($admin)->get('/admin/invitations');
        $listResponse->assertOk();
        $listResponse->assertSee('Copy Link');
        $listResponse->assertSee('navigator.clipboard.writeText', false);
        $listResponse->assertSee($publicUrlJs, false);

        $editResponse = $this->actingAs($admin)->get("/admin/invitations/{$invitation->id}/edit");
        $editResponse->assertOk();
        $editResponse->assertSee('Copy Link');
        $editResponse->assertSee($publicUrlJs, false);

        $this->get($publicUrl)->assertOk();
    }

    /**
     * The 4 row actions (Preview, Copy Link, Edit, Delete) render icon-only
     * — fi-icon-btn is the class Filament's ->iconButton() view uses,
     * distinct from a labeled button's fi-btn. The action's own ->label()
     * is untouched (asserted above) — iconButton() only changes which view
     * renders it, keeping the label as the button's accessible tooltip.
     */
    public function test_the_row_actions_render_as_icon_buttons_not_labeled_buttons(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Invitation::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/invitations');

        $response->assertOk();
        $response->assertSee('fi-icon-btn', false);
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
