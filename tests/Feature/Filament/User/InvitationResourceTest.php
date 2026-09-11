<?php

namespace Tests\Feature\Filament\User;

use App\Filament\User\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\User\Resources\Invitations\Pages\EditInvitation;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Theme;
use App\Models\User;
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

        // Livewire::test() doesn't traverse the /user/* route, so Filament
        // doesn't infer the panel from the URL — set it explicitly, otherwise
        // resource pages resolve routes/policies against the default (admin) panel.
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    protected function subscribedCustomer(): User
    {
        $customer = User::factory()->create();
        $plan = Plan::factory()->create(['invitation_limit' => 1]);
        Subscription::factory()->active()->create(['user_id' => $customer->id, 'plan_id' => $plan->id]);

        return $customer;
    }

    public function test_customer_only_sees_their_own_invitations_in_the_list(): void
    {
        $customer = $this->subscribedCustomer();
        $mine = Invitation::factory()->for($customer)->create(['title' => 'My Invitation']);
        $someoneElses = Invitation::factory()->create(['title' => "Someone Else's Invitation"]);

        $response = $this->actingAs($customer)->get('/user/invitations');

        $response->assertOk();
        $response->assertSee('My Invitation');
        $response->assertDontSee("Someone Else's Invitation");
    }

    public function test_customer_cannot_open_another_customers_invitation(): void
    {
        $customer = $this->subscribedCustomer();
        $someoneElses = Invitation::factory()->create();

        $response = $this->actingAs($customer)->get("/user/invitations/{$someoneElses->id}/edit");

        $response->assertNotFound();
    }

    public function test_customer_without_an_active_subscription_cannot_create_an_invitation(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/user/invitations/create');

        $response->assertForbidden();
    }

    public function test_subscribed_customer_can_create_an_invitation_owned_by_them(): void
    {
        $customer = $this->subscribedCustomer();

        Livewire::actingAs($customer)
            ->test(CreateInvitation::class)
            ->fillForm([
                'title' => 'Amara & Reyhan',
                'slug' => 'amara-reyhan',
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invitation = Invitation::query()->where('slug', 'amara-reyhan')->sole();
        $this->assertSame($customer->id, $invitation->user_id);
        $this->assertFalse($invitation->is_custom_build);
    }

    public function test_creating_from_a_theme_clones_its_content(): void
    {
        $customer = $this->subscribedCustomer();
        $theme = Theme::factory()->create([
            'content' => ['rows' => [['id' => 'row_1', 'settings' => [], 'columns' => []]]],
        ]);

        Livewire::actingAs($customer)
            ->test(CreateInvitation::class)
            ->fillForm(['theme_id' => $theme->id])
            ->fillForm([
                'title' => 'From Theme',
                'slug' => 'from-theme',
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invitation = Invitation::query()->where('slug', 'from-theme')->sole();
        $this->assertSame($theme->id, $invitation->theme_id);
        $this->assertSame('row_1', $invitation->content['rows'][0]['id']);
    }

    public function test_invitation_limit_blocks_creating_past_the_plan_cap(): void
    {
        $customer = $this->subscribedCustomer();
        Invitation::factory()->for($customer)->create();

        $response = $this->actingAs($customer)->get('/user/invitations/create');

        $response->assertForbidden();
    }

    /**
     * Regression: the header "Save changes" button used to reuse
     * getSaveFormAction(), which renders a native type="submit" button
     * wired to the <form>'s wire:submit — that only works while the
     * button lives inside that <form>. Header actions render outside it,
     * so clicking Save silently did nothing. Fixed by calling save()
     * directly via ->action('save') instead. That action has no
     * ->submit()/->requiresConfirmation()/URL, so Filament renders it as a
     * plain wire:click="save" rather than routing through the
     * mounted-action pipeline — confirmed below — so this calls save()
     * directly rather than through ->callAction(), which simulates that
     * different pipeline instead of a real click on this specific button.
     */
    public function test_the_header_save_action_actually_persists_changes(): void
    {
        $customer = $this->subscribedCustomer();
        $invitation = Invitation::factory()->for($customer)->create(['title' => 'Original Title']);

        Livewire::actingAs($customer)
            ->test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm(['title' => 'Updated Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Title', $invitation->fresh()->title);
    }

    public function test_the_save_button_renders_as_a_direct_livewire_click_handler(): void
    {
        $customer = $this->subscribedCustomer();
        $invitation = Invitation::factory()->for($customer)->create();

        $response = $this->actingAs($customer)->get("/user/invitations/{$invitation->id}/edit");

        $response->assertOk();
        $response->assertSee('wire:click="save"', false);
    }

    /**
     * The editor is a full-screen takeover — no sidebar, no topbar — the
     * same way Elementor's own editor replaces wp-admin's chrome entirely
     * rather than living inside it (EditInvitation::$layout, pointed at
     * resources/views/filament/layouts/full-screen-editor.blade.php).
     * id="fi-main-sidebar" / class="fi-topbar-ctn" are those Livewire
     * components' own wrapper markup — not a resource label, since
     * Filament's global search embeds resource names into a JS payload
     * loaded on every page regardless of whether the sidebar renders.
     */
    public function test_the_edit_page_is_a_full_screen_takeover_without_the_sidebar(): void
    {
        $customer = $this->subscribedCustomer();
        $invitation = Invitation::factory()->for($customer)->create();

        $listResponse = $this->actingAs($customer)->get('/user/invitations');
        $listResponse->assertOk();
        $listResponse->assertSee('id="fi-main-sidebar"', false);
        $listResponse->assertSee('class="fi-topbar-ctn"', false);

        $editResponse = $this->actingAs($customer)->get("/user/invitations/{$invitation->id}/edit");
        $editResponse->assertOk();
        $editResponse->assertDontSee('id="fi-main-sidebar"', false);
        $editResponse->assertDontSee('class="fi-topbar-ctn"', false);

        $editResponse->assertSee('Preview');
        $editResponse->assertSee('Save changes');
    }

    /**
     * The real guest-facing link (route('invitations.show'), /i/{slug}) —
     * distinct from "Preview", which opens a separate auth-gated route.
     * Checked on both the list row action and the edit page header
     * action, since InvitationResource::copyLinkAction() is shared by both.
     */
    public function test_the_copy_link_action_embeds_the_real_public_url(): void
    {
        $customer = $this->subscribedCustomer();
        $invitation = Invitation::factory()->for($customer)->published()->create(['slug' => 'amara-reyhan']);
        $publicUrlJs = Js::from(route('invitations.show', $invitation))->toHtml();

        $listResponse = $this->actingAs($customer)->get('/user/invitations');
        $listResponse->assertOk();
        $listResponse->assertSee('Copy Link');
        $listResponse->assertSee('navigator.clipboard.writeText', false);
        $listResponse->assertSee($publicUrlJs, false);

        $editResponse = $this->actingAs($customer)->get("/user/invitations/{$invitation->id}/edit");
        $editResponse->assertOk();
        $editResponse->assertSee('Copy Link');
        $editResponse->assertSee($publicUrlJs, false);
    }
}
