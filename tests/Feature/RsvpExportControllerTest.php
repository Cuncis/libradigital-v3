<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RsvpExportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/user/rsvps/export');

        $response->assertRedirect('/user/login');
    }

    public function test_export_only_includes_the_customers_own_guests(): void
    {
        $customer = User::factory()->create();
        $mine = Invitation::factory()->for($customer)->create();
        $someoneElses = Invitation::factory()->create();

        Guest::factory()->for($mine, 'invitation')->create(['name' => 'My Guest']);
        Guest::factory()->for($someoneElses, 'invitation')->create(['name' => "Someone Else's Guest"]);

        $response = $this->actingAs($customer)->get('/user/rsvps/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('My Guest', $content);
        $this->assertStringNotContainsString("Someone Else's Guest", $content);
    }
}
