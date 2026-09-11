<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
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
     * Copy Link opens a modal to customize the slug (the customizable part
     * of route('invitations.show'), /i/{slug}) and copies the resulting
     * link on submit — merged from two previously separate actions (a
     * read-only Copy Link and a slug-editing Customize Link) per explicit
     * request to make them one. Driven through Filament's table-action
     * testing helpers rather than static HTML, since the URL is no longer
     * embedded directly in the page markup (it's built after the slug is
     * saved, inside the ->action() closure) the way the old alpineClickHandler
     * version baked it in statically.
     *
     * Regression guard carried over from the previous version of this
     * test: route('invitations.show', $record) (passing the model
     * directly) silently embeds the model's route key (id) instead of its
     * slug — /i/{slug}'s parameter isn't named "invitation", so Laravel's
     * URL generator has no binding field to consult. Asserted here by
     * actually requesting the copied URL and confirming it resolves.
     */
    public function test_the_copy_link_action_saves_the_slug_and_copies_the_link(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $invitation = Invitation::factory()->published()->create(['slug' => 'old-slug']);

        Livewire::actingAs($admin)
            ->test(ListInvitations::class)
            ->mountTableAction('copyLink', $invitation)
            ->assertTableActionDataSet(['slug' => 'old-slug'])
            ->setTableActionData(['slug' => 'amara-reyhan'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertJs('window.navigator.clipboard.writeText('.Js::from(route('invitations.show', ['slug' => 'amara-reyhan']))->toHtml().')');

        $invitation->refresh();
        $this->assertSame('amara-reyhan', $invitation->slug);

        $this->get(route('invitations.show', ['slug' => $invitation->slug]))->assertOk();
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

    /**
     * event_date was dropped as a list column (still editable in the form's
     * Details section) — the label Filament auto-generates for it, per
     * Column::getLabel(), is "Event date" (kebab-case name -> spaces ->
     * ucfirst), not "Event Date", so that's the exact string asserted gone.
     */
    public function test_the_event_date_column_is_removed_from_the_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Invitation::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/invitations');

        $response->assertOk();
        $response->assertDontSee('Event date');
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
