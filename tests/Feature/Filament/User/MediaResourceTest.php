<?php

namespace Tests\Feature\Filament\User;

use App\Models\Media;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

/**
 * The Media Library (awcodes/filament-curator) in the customer-facing /user
 * panel: unlike admins, customers are always hard-scoped to their own
 * uploads — App\Filament\Resources\Media\MediaResource never shows them the
 * All/Self filter at all, since it would have nothing to narrow.
 */
class MediaResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mediaAttributes(int $userId, string $name): array
    {
        return [
            'user_id' => $userId,
            'disk' => 'public',
            'visibility' => 'public',
            'name' => $name,
            'path' => "media/{$name}.jpg",
            'type' => 'image/jpeg',
            'ext' => 'jpg',
        ];
    }

    public function test_customer_only_sees_their_own_media(): void
    {
        $customer = User::factory()->create();
        $someoneElse = User::factory()->create();

        Media::query()->create($this->mediaAttributes($customer->id, 'my-photo'));
        Media::query()->create($this->mediaAttributes($someoneElse->id, 'their-photo'));

        $response = $this->actingAs($customer)->get('/user/media');

        $response->assertOk();
        $response->assertSee('my-photo');
        $response->assertDontSee('their-photo');
    }

    public function test_customer_cannot_open_another_customers_media(): void
    {
        $customer = User::factory()->create();
        $someoneElse = User::factory()->create();

        $theirs = Media::query()->create($this->mediaAttributes($someoneElse->id, 'their-photo'));

        $response = $this->actingAs($customer)->get("/user/media/{$theirs->id}/edit");

        $response->assertNotFound();
    }

    public function test_the_copy_link_action_embeds_the_medias_actual_url(): void
    {
        $customer = User::factory()->create();
        $media = Media::query()->create($this->mediaAttributes($customer->id, 'my-photo'));

        $response = $this->actingAs($customer)->get('/user/media');

        $response->assertOk();
        $response->assertSee('Copy Link');
        $response->assertSee('navigator.clipboard.writeText', false);
        $response->assertSee(Js::from($media->url)->toHtml(), false);
    }

    public function test_customer_does_not_see_the_admin_only_scope_filter(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/user/media');

        $response->assertOk();
        $response->assertDontSee('All users\' media', false);
        $response->assertDontSee('Just my own', false);
    }
}
