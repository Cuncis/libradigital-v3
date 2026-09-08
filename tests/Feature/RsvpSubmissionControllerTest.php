<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Notifications\NewRsvpReceived;
use App\RsvpStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RsvpSubmissionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_submission_creates_a_guest_record(): void
    {
        Notification::fake();

        $invitation = Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $response = $this->post('/i/amara-reyhan/rsvp', [
            'name' => 'Budi',
            'attending' => 'attending',
            'party_size' => 2,
            'message' => 'Excited!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('rsvp_submitted', true);

        $guest = $invitation->guests()->sole();
        $this->assertSame('Budi', $guest->name);
        $this->assertSame(RsvpStatus::Attending, $guest->attending);
        $this->assertSame(2, $guest->party_size);
        $this->assertNotNull($guest->responded_at);

        Notification::assertSentTo($invitation->user, NewRsvpReceived::class);
    }

    public function test_missing_name_fails_validation(): void
    {
        $invitation = Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $response = $this->post('/i/amara-reyhan/rsvp', ['attending' => 'attending']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, $invitation->guests()->count());
    }

    public function test_cannot_rsvp_to_an_unpublished_invitation(): void
    {
        $invitation = Invitation::factory()->create(['slug' => 'still-drafting', 'status' => 'draft']);

        $response = $this->post('/i/still-drafting/rsvp', [
            'name' => 'Budi',
            'attending' => 'attending',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, $invitation->guests()->count());
    }

    public function test_party_size_defaults_to_one_when_omitted(): void
    {
        $invitation = Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $this->post('/i/amara-reyhan/rsvp', [
            'name' => 'Budi',
            'attending' => 'maybe',
        ]);

        $this->assertSame(1, $invitation->guests()->sole()->party_size);
    }
}
