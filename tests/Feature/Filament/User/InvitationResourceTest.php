<?php

namespace Tests\Feature\Filament\User;

use App\Filament\User\Resources\Invitations\Pages\CreateInvitation;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Theme;
use App\Models\User;
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
}
