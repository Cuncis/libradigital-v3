<?php

namespace Tests\Feature\Filament\User;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RsvpsPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_only_sees_rsvps_for_their_own_invitations(): void
    {
        $customer = User::factory()->create();
        $mine = Invitation::factory()->for($customer)->create();
        $someoneElses = Invitation::factory()->create();

        Guest::factory()->for($mine, 'invitation')->create(['name' => 'My Guest']);
        Guest::factory()->for($someoneElses, 'invitation')->create(['name' => "Someone Else's Guest"]);

        $response = $this->actingAs($customer)->get('/user/rsvps');

        $response->assertOk();
        $response->assertSee('My Guest');
        $response->assertDontSee("Someone Else's Guest");
    }

    public function test_attending_count_sums_party_size_of_attending_guests_only(): void
    {
        $customer = User::factory()->create();
        $invitation = Invitation::factory()->for($customer)->create();

        Guest::factory()->for($invitation, 'invitation')->create(['attending' => 'attending', 'party_size' => 2]);
        Guest::factory()->for($invitation, 'invitation')->create(['attending' => 'attending', 'party_size' => 3]);
        Guest::factory()->for($invitation, 'invitation')->create(['attending' => 'not_attending', 'party_size' => 5]);

        $response = $this->actingAs($customer)->get('/user/rsvps');

        $response->assertOk();
        $response->assertSeeText('5');
    }
}
