<?php

namespace Tests\Feature;

use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CustomRequestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_submission_creates_a_user_account_and_a_custom_request(): void
    {
        $response = $this->post(route('custom-requests.store'), [
            'name' => 'Amara',
            'email' => 'amara@example.com',
            'phone' => '+6281234567890',
            'event_type' => 'wedding',
            'event_date' => now()->addMonths(2)->toDateString(),
            'style_notes' => 'Something garden-themed.',
            'budget' => 5_000_000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('custom_request_submitted', true);

        $user = User::query()->where('email', 'amara@example.com')->sole();
        $this->assertSame('Amara', $user->name);

        $customRequest = CustomRequest::query()->where('user_id', $user->id)->sole();
        $this->assertSame('wedding', $customRequest->event_type);
        $this->assertSame('new', $customRequest->status->value);
    }

    public function test_guest_submission_reuses_an_existing_account_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'amara@example.com']);

        $this->post(route('custom-requests.store'), [
            'name' => 'Amara',
            'email' => 'amara@example.com',
            'event_type' => 'birthday',
        ]);

        $this->assertSame(1, User::query()->where('email', 'amara@example.com')->count());
        $this->assertSame($existing->id, CustomRequest::query()->sole()->user_id);
    }

    public function test_authenticated_user_does_not_need_to_provide_contact_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('custom-requests.store'), [
            'event_type' => 'corporate',
        ]);

        $response->assertRedirect();
        $this->assertSame($user->id, CustomRequest::query()->sole()->user_id);
    }

    public function test_missing_event_type_fails_validation(): void
    {
        $response = $this->post(route('custom-requests.store'), [
            'name' => 'Amara',
            'email' => 'amara@example.com',
        ]);

        $response->assertSessionHasErrors('event_type');
        $this->assertSame(0, CustomRequest::query()->count());
    }

    public function test_guest_missing_email_fails_validation(): void
    {
        $response = $this->post(route('custom-requests.store'), [
            'name' => 'Amara',
            'event_type' => 'wedding',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
