<?php

namespace Tests\Feature;

use App\Models\Invitation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvitationPageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_published_invitation_renders(): void
    {
        $invitation = Invitation::factory()->published()->create([
            'title' => 'Amara & Reyhan',
            'slug' => 'amara-reyhan',
        ]);

        $response = $this->get("/i/{$invitation->slug}");

        $response->assertOk();
        $response->assertSee('Amara & Reyhan');
    }

    /**
     * Guests almost exclusively open invitation links on a phone, so the
     * page is pinned to a phone-width column even on a desktop/tablet
     * browser (layouts/invitation.blade.php), rather than a responsive
     * layout that stretches to fill a wide viewport.
     */
    public function test_the_page_is_pinned_to_a_phone_width_column(): void
    {
        Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('max-w-[430px]', false);
    }

    public function test_draft_invitation_returns_404(): void
    {
        Invitation::factory()->create(['slug' => 'still-drafting', 'status' => 'draft']);

        $response = $this->get('/i/still-drafting');

        $response->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $response = $this->get('/i/does-not-exist');

        $response->assertNotFound();
    }

    public function test_guest_name_banner_shows_when_to_param_present(): void
    {
        $invitation = Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $response = $this->get('/i/amara-reyhan?to=Jane');

        $response->assertOk();
        $response->assertSee('Dear Jane,');
    }

    public function test_guest_name_banner_absent_without_to_param(): void
    {
        $invitation = Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertDontSee('Dear', false);
    }

    public function test_seo_title_reflects_the_invitation(): void
    {
        $invitation = Invitation::factory()->published()->create([
            'slug' => 'amara-reyhan',
            'title' => 'Amara & Reyhan',
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertSee('<title>Amara &amp; Reyhan</title>', false);
    }

    public function test_og_url_points_at_the_real_public_route_not_layups_unused_default(): void
    {
        Invitation::factory()->published()->create(['slug' => 'amara-reyhan']);

        $response = $this->get('/i/amara-reyhan');

        $response->assertSee('og:url" content="'.url('/i/amara-reyhan').'"', false);
        $response->assertDontSee('/pages/amara-reyhan', false);
    }

    public function test_image_widget_resolves_via_the_configured_upload_disk(): void
    {
        // Storage::fake() always returns its own generic "/storage/{path}"
        // testing convention from url(), regardless of the disk's actual
        // configured `url` — so faking can't distinguish "correctly disk-aware"
        // from "still hardcoded to the old broken asset('storage/...') path".
        // Assert the actual call instead: Storage::disk(configured disk)->url(path).
        Storage::shouldReceive('disk')
            ->with('r2')
            ->andReturnSelf();
        Storage::shouldReceive('url')
            ->with('invitations/photo.jpg')
            ->andReturn('https://cdn.example.test/invitations/photo.jpg');

        $invitation = Invitation::factory()->published()->create([
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
                            'type' => 'image',
                            'data' => ['src' => 'invitations/photo.jpg'],
                        ]],
                    ]],
                ]],
            ],
        ]);

        $response = $this->get('/i/amara-reyhan');

        $expectedUrl = Storage::disk('r2')->url('invitations/photo.jpg');

        $response->assertOk();
        $response->assertSee($expectedUrl, false);
        $response->assertDontSee('/storage/invitations/photo.jpg', false);
    }

    public function test_image_widget_passes_through_an_already_absolute_url_unchanged(): void
    {
        Storage::shouldReceive('disk')->never();

        $invitation = Invitation::factory()->published()->create([
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
                            'type' => 'image',
                            'data' => ['src' => 'https://example.test/photo.jpg'],
                        ]],
                    ]],
                ]],
            ],
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('https://example.test/photo.jpg', false);
    }

    /**
     * Regression: Layup's tree-builder has no error handling for a
     * malformed row/column/widget entry (unlike individual widgets, which
     * are already try/caught) — a single non-array entry anywhere in this
     * shape used to throw a hard TypeError and 500 the whole page, even
     * though Layup's own ContentValidator deliberately allows malformed
     * content to be saved (warns, doesn't block). Reproduces the exact
     * shape that caused it in production: `content.rows.0` was a string.
     */
    public function test_a_malformed_row_is_skipped_instead_of_crashing_the_page(): void
    {
        $invitation = Invitation::factory()->published()->create([
            'slug' => 'amara-reyhan',
            'content' => [
                'rows' => [
                    'second', // malformed: a string where a row object belongs
                    [
                        'id' => 'row_1',
                        'settings' => [],
                        'columns' => [[
                            'id' => 'col_1',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [[
                                'id' => 'widget_1',
                                'type' => 'heading',
                                'data' => ['content' => 'Still Renders', 'level' => 'h2'],
                            ]],
                        ]],
                    ],
                ],
            ],
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('Still Renders');
    }

    public function test_a_malformed_column_or_widget_is_also_skipped_instead_of_crashing(): void
    {
        $invitation = Invitation::factory()->published()->create([
            'slug' => 'amara-reyhan',
            'content' => [
                'rows' => [[
                    'id' => 'row_1',
                    'settings' => [],
                    'columns' => [
                        'not-a-column',
                        [
                            'id' => 'col_1',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'settings' => [],
                            'widgets' => [
                                'not-a-widget',
                                ['id' => 'widget_1', 'type' => 'heading', 'data' => ['content' => 'Still Renders', 'level' => 'h2']],
                            ],
                        ],
                    ],
                ]],
            ],
        ]);

        $response = $this->get('/i/amara-reyhan');

        $response->assertOk();
        $response->assertSee('Still Renders');
    }
}
