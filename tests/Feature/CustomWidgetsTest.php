<?php

namespace Tests\Feature;

use App\Models\Invitation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomWidgetsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function invitationWithWidget(string $type, array $data): Invitation
    {
        return Invitation::factory()->published()->create([
            'slug' => 'amara-reyhan',
            'content' => [
                'rows' => [[
                    'id' => 'row_1',
                    'settings' => [],
                    'columns' => [[
                        'id' => 'col_1',
                        'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                        'settings' => [],
                        'widgets' => [[
                            'id' => 'widget_1',
                            'type' => $type,
                            'data' => $data,
                        ]],
                    ]],
                ]],
            ],
        ]);
    }

    public function test_gift_info_widget_renders_account_details_and_digital_link(): void
    {
        $this->invitationWithWidget('gift-info', [
            'title' => 'Send a Gift',
            'accounts' => [
                ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Amara'],
            ],
            'digital_link' => 'https://gift.example.com/amara',
            'digital_link_label' => 'Send Digitally',
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('BCA');
        $response->assertSee('1234567890');
        $response->assertSee('Amara');
        $response->assertSee('Send Digitally');
        $response->assertSee('https://gift.example.com/amara', false);
    }

    public function test_gift_info_widget_skips_accounts_with_no_number(): void
    {
        $this->invitationWithWidget('gift-info', [
            'accounts' => [
                ['bank_name' => 'BCA', 'account_number' => '', 'account_holder' => 'Amara'],
            ],
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertDontSee('BCA');
    }

    public function test_music_player_resolves_audio_via_the_configured_upload_disk(): void
    {
        Storage::shouldReceive('disk')->with('r2')->andReturnSelf();
        Storage::shouldReceive('url')->with('invitations/song.mp3')->andReturn('https://cdn.example.test/invitations/song.mp3');

        $this->invitationWithWidget('music-player', [
            'audio_file' => 'invitations/song.mp3',
            'autoplay' => false,
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('https://cdn.example.test/invitations/song.mp3', false);
    }

    public function test_music_player_renders_nothing_without_an_audio_file(): void
    {
        $this->invitationWithWidget('music-player', ['audio_file' => '']);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertDontSee('<audio', false);
    }

    public function test_love_story_timeline_renders_the_provided_events(): void
    {
        $this->invitationWithWidget('love-story-timeline', [
            'events' => [
                ['date' => '2022', 'title' => 'How We Met', 'description' => 'At a coffee shop.'],
                ['date' => '2024', 'title' => 'The Proposal', 'description' => 'On the beach.'],
            ],
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('How We Met');
        $response->assertSee('At a coffee shop.');
        $response->assertSee('The Proposal');
    }
}
